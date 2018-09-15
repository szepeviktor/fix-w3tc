<?php
/**
 * Class Minify_JS_ClosureCompiler
 * @package Minify
 */

/**
 * Minify Javascript using Google's Closure Compiler API
 *
 * @link http://code.google.com/closure/compiler/
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 *
 * @todo can use a stream wrapper to unit test this?
 */
class Minify_JS_ClosureCompiler {
    const URL = 'https://closure-compiler.appspot.com/compile';

    /**
     * Minify Javascript code via HTTP request to the Closure Compiler API
     *
     * @param string $js input code
     * @param array $options unused at this point
     * @return string
     */
    public static function minify($js, array $options = array())
    {
        $obj = new self($options);
        return $obj->min($js);
    }

    /**
     *
     * @param array $options
     *
     * fallbackFunc : default array($this, 'fallback');
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
        $this->_fallbackFunc = isset($options['fallbackMinifier'])
            ? $options['fallbackMinifier']
            : array($this, '_fallback');
    }

    public function min($js)
    {
        if (trim($js) === '')
            return $js;

        $postBody = $this->_buildPostBody($js);
        $bytes = (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2))
            ? mb_strlen($postBody, '8bit')
            : strlen($postBody);
        if ($bytes > 200000)
            return $this->fail($js, 
                'File size is larger than Closure Compiler API limit (200000 bytes)');

        $response = $this->_getResponse($postBody);
        if (preg_match('/^Error\(\d\d?\):/', $response))
            return $this->fail($response, 
                "Received errors from Closure Compiler API:\n$response");

        return $response;
    }

    private function fail($js, $errorMessage) {
        Minify0_Minify::$recoverableError = $errorMessage;
        $response = "/* " . $errorMessage . "\n(Using fallback minifier)\n*/\n";
        if (is_callable($this->_fallbackFunc))
            $response .= call_user_func($this->_fallbackFunc, $js);
        else
            $response .= $js;
        
        return $response;
    }

    protected $_fallbackFunc = null;
    protected $_options = array();

    protected function _getResponse($postBody)
    {
        $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
        if ($allowUrlFopen) {
            $contents = file_get_contents(self::URL, false, stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\nConnection: close\r\n",
                    'content' => $postBody,
                    'max_redirects' => 0,
                    'timeout' => 15,
                )
            )));
        } elseif (defined('CURLOPT_POST')) {
            $ch = curl_init(self::URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            $contents = curl_exec($ch);
            curl_close($ch);
        } else {
            throw new Minify_JS_ClosureCompiler_Exception(
               "Could not make HTTP request: allow_url_open is false and cURL not available"
            );
        }
        if (false === $contents) {
            throw new Minify_JS_ClosureCompiler_Exception(
               "No HTTP response from server"
            );
        }
        return trim($contents);
    }

    protected function _buildPostBody($js, $returnErrors = false)
    {
        $a = array(
            'js_code' => $js,
            'output_info' => ($returnErrors ? 'errors' : 'compiled_code'),
            'output_format' => 'text',
            'compilation_level' => 
                (isset($this->options['compilation_level']) ? 
                    $this->options['compilation_level'] : 
                    'SIMPLE_OPTIMIZATIONS')
        );
        if (isset($this->options['formatting']) && !empty($this->options['formatting']))
            $a['formatting'] = $this->options['formatting'];

        return http_build_query($a, null, '&');
    }

    /**
     * Default fallback function if CC API fails
     * @param string $js
     * @return string
     */
    protected function _fallback($js)
    {
        return Minify0_JSMin::minify($js);
    }

    public static function test(&$error) {
        try {
            self::minify('alert("ok");');
            $error = 'OK';

            return true;
        } catch (Exception $exception) {
            $error = $exception->getMessage();

            return false;
        }
    }
}

class Minify_JS_ClosureCompiler_Exception extends Exception {}
