<?php

/**
 * W3 BrowserCache plugin
 */
if (!defined('W3TC')) {
    die();
}

w3_require_once(W3TC_INC_DIR . '/functions/rule.php');
w3_require_once(W3TC_LIB_W3_DIR . '/Plugin.php');

/**
 * Class W3_Plugin_BrowserCacheAdmin
 */
class W3_Plugin_BrowserCacheAdmin extends W3_Plugin {
    /**
     * Activate plugin action
     */
    function activate() {
        if ($this->_config->get_boolean('browsercache.enabled')) {
            if (w3_can_modify_rules(w3_get_browsercache_rules_cache_path())) {
                try {
                    $this->write_rules_cache();
                } catch (Exception $e)
                {}
            }

            if ($this->_config->get_boolean('browsercache.no404wp') && w3_can_modify_rules(w3_get_browsercache_rules_no404wp_path())) {
                try {
                    $this->write_rules_no404wp();
                } catch (Exception $e)
                {}
            }
        }
    }

    /**
     * Deactivate plugin action
     */
    function deactivate() {
        $errors = array('errors' => array(), 'errors_short_form' => array(), 'ftp_form' => null);
        $results = array();

        if (w3_can_modify_rules(w3_get_browsercache_rules_no404wp_path())) {
            $results[] = $this->remove_rules_no404wp_with_message();
        }

        if (w3_can_modify_rules(w3_get_browsercache_rules_cache_path())) {
            $results[] = $this->remove_rules_cache_with_message();
        }
        if ($results) {
            foreach ($results as $result) {
                if ($result['errors']) {
                    $errors['errors'] = array_merge($errors['errors'], $result['errors']);
                    $errors['errors_short_form'] = array_merge($errors['errors_short_form'], $result['errors_short_form']);
                    if (!isset($errors['ftp_form']))
                        $errors['ftp_form'] = $result['ftp_form'];
                }
            }
        }
        return $errors;
    }

    /**
     * Returns CSS/JS mime types
     *
     * @return array
     */
    function _get_cssjs_types() {
        $mime_types = include W3TC_INC_DIR . '/mime/cssjs.php';

        return $mime_types;
    }

    /**
     * Returns HTML mime types
     *
     * @return array
     */
    function _get_html_types() {
        $mime_types = include W3TC_INC_DIR . '/mime/html.php';

        return $mime_types;
    }

    /**
     * Returns other mime types
     *
     * @return array
     */
    function _get_other_types() {
        $mime_types = include W3TC_INC_DIR . '/mime/other.php';

        return $mime_types;
    }

    /**
     * Returns cache rules
     *
     * @return string
     */
    function generate_rules_cache($cdnftp = false) {
        switch (true) {
            case w3_is_apache():
            case w3_is_litespeed():
                return $this->generate_rules_cache_apache();

            case w3_is_nginx():
                return $this->generate_rules_cache_nginx($cdnftp);
        }

        return false;
    }

    /**
     * Returns cache rules
     *
     * @return string
     */
    function generate_rules_cache_apache() {
        $cssjs_types = $this->_get_cssjs_types();
        $html_types = $this->_get_html_types();
        $other_types = $this->_get_other_types();

        $cssjs_expires = $this->_config->get_boolean('browsercache.cssjs.expires');
        $html_expires = $this->_config->get_boolean('browsercache.html.expires');
        $other_expires = $this->_config->get_boolean('browsercache.other.expires');

        $cssjs_lifetime = $this->_config->get_integer('browsercache.cssjs.lifetime');
        $html_lifetime = $this->_config->get_integer('browsercache.html.lifetime');
        $other_lifetime = $this->_config->get_integer('browsercache.other.lifetime');
        $compatibility = $this->_config->get_boolean('pgcache.compatibility');

        $mime_types = array();

        if ($cssjs_expires && $cssjs_lifetime) {
            $mime_types = array_merge($mime_types, $cssjs_types);
        }

        if ($html_expires && $html_lifetime) {
            $mime_types = array_merge($mime_types, $html_types);
        }

        if ($other_expires && $other_lifetime) {
            $mime_types = array_merge($mime_types, $other_types);
        }

        $rules = '';
        $rules .= W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE . "\n";

        if (count($mime_types)) {
            $rules .= "<IfModule mod_mime.c>\n";

            foreach ($mime_types as $ext => $mime_type) {
                $extensions = explode('|', $ext);

                $rules .= "    AddType " . $mime_type;

                foreach ($extensions as $extension) {
                    $rules .= " ." . $extension;
                }

                $rules .= "\n";
            }

            $rules .= "</IfModule>\n";

            $rules .= "<IfModule mod_expires.c>\n";
            $rules .= "    ExpiresActive On\n";

            if ($cssjs_expires && $cssjs_lifetime) {
                foreach ($cssjs_types as $mime_type) {
                    $rules .= "    ExpiresByType " . $mime_type . " A" . $cssjs_lifetime . "\n";
                }
            }

            if ($html_expires && $html_lifetime) {
                foreach ($html_types as $mime_type) {
                    $rules .= "    ExpiresByType " . $mime_type . " A" . $html_lifetime . "\n";
                }
            }

            if ($other_expires && $other_lifetime) {
                foreach ($other_types as $mime_type) {
                    $rules .= "    ExpiresByType " . $mime_type . " A" . $other_lifetime . "\n";
                }
            }

            $rules .= "</IfModule>\n";
        }

        $cssjs_compression = $this->_config->get_boolean('browsercache.cssjs.compression');
        $html_compression = $this->_config->get_boolean('browsercache.html.compression');
        $other_compression = $this->_config->get_boolean('browsercache.other.compression');

        if ($cssjs_compression || $html_compression || $other_compression) {
            $compression_types = array();

            if ($cssjs_compression) {
                $compression_types = array_merge($compression_types, $cssjs_types);
            }

            if ($html_compression) {
                $compression_types = array_merge($compression_types, $html_types);
            }

            if ($other_compression) {
                $compression_types = array_merge($compression_types, array(
                    //for some reason the 'other' types are not loaded from the 'other' file
                    'ico' => 'image/x-icon',
                    'json' => 'application/json'
                ));
            }

            $rules .= "<IfModule mod_deflate.c>\n";
            if ($compatibility) {
                $rules .= "    <IfModule mod_setenvif.c>\n";
                $rules .= "        BrowserMatch ^Mozilla/4 gzip-only-text/html\n";
                $rules .= "        BrowserMatch ^Mozilla/4\\.0[678] no-gzip\n";
                $rules .= "        BrowserMatch \\bMSIE !no-gzip !gzip-only-text/html\n";
                $rules .= "        BrowserMatch \\bMSI[E] !no-gzip !gzip-only-text/html\n";
                $rules .= "    </IfModule>\n";
            }
            $rules .= "    <IfModule mod_headers.c>\n";
            $rules .= "        Header append Vary User-Agent env=!dont-vary\n";
            $rules .= "    </IfModule>\n";
            if (version_compare($this->_get_server_version(), '2.3.7', '>=')) {
                $rules .= "    <IfModule mod_filter.c>\n";
            }
            $rules .= "        AddOutputFilterByType DEFLATE " . implode(' ', $compression_types) . "\n";
            $rules .= "    <IfModule mod_mime.c>\n";
            $rules .= "        # DEFLATE by extension\n";
            $rules .= "        AddOutputFilter DEFLATE js css htm html xml\n";
            $rules .= "    </IfModule>\n";

            if (version_compare($this->_get_server_version(), '2.3.7', '>=')) {
                $rules .= "    </IfModule>\n";
            }
            $rules .= "</IfModule>\n";
        }

        $rules .= $this->_generate_rules_cache_apache($cssjs_types, 'cssjs');
        $rules .= $this->_generate_rules_cache_apache($html_types, 'html');
        $rules .= $this->_generate_rules_cache_apache($other_types, 'other');

        $rules .= W3TC_MARKER_END_BROWSERCACHE_CACHE . "\n";

        return $rules;
    }

    /**
     * Returns the apache, nginx version
     * @return string
     */
    private function _get_server_version() {
        $sig= explode('/', $_SERVER['SERVER_SOFTWARE']);
        $temp = isset($sig[1]) ? explode(' ', $sig[1]) : array('0');
        $version = $temp[0];
        return $version;
    }

    /**
     * Writes cache rules
     *
     * @param string $rules
     * @param array $mime_types
     * @param string $section
     * @return void
     */
    function _generate_rules_cache_apache($mime_types, $section) {
        $is_disc_enhanced = $this->_config->get_boolean('pgcache.enabled') &&
                            $this->_config->get_string('pgcache.engine') == 'file_generic';
        $cache_control = $this->_config->get_boolean('browsercache.' . $section . '.cache.control');
        $etag = $this->_config->get_boolean('browsercache.' . $section . '.etag');
        $w3tc = $this->_config->get_boolean('browsercache.' . $section . '.w3tc');
        $unset_setcookie = $this->_config->get_boolean('browsercache.' . $section . '.nocookies');
        $set_last_modified = $this->_config->get_boolean('browsercache.' . $section . '.last_modified');
        $compatibility = $this->_config->get_boolean('pgcache.compatibility');

        $extensions = array_keys($mime_types);

        // Remove ext from filesmatch if its the same as permalink extension
        $pext = strtolower(pathinfo(get_option('permalink_structure'), PATHINFO_EXTENSION));
        if ($pext) {
            $extensions = $this->_remove_extension_from_list($extensions, $pext);
        }

        $extensions_lowercase = array_map('strtolower', $extensions);
        $extensions_uppercase = array_map('strtoupper', $extensions);

        $rules = '';
        $headers_rules = '';

        if ($cache_control) {
            $cache_policy = $this->_config->get_string('browsercache.' . $section . '.cache.policy');

            switch ($cache_policy) {
                case 'cache':
                    $headers_rules .= "        Header set Pragma \"public\"\n";
                    $headers_rules .= "        Header set Cache-Control \"public\"\n";
                    break;

                case 'cache_public_maxage':
                    $expires = $this->_config->get_boolean('browsercache.' . $section . '.expires');
                    $lifetime = $this->_config->get_integer('browsercache.' . $section . '.lifetime');

                    $headers_rules .= "        Header set Pragma \"public\"\n";

                    if ($expires)
                        $headers_rules .= "        Header append Cache-Control \"public\"\n";
                    else
                        $headers_rules .= "        Header set Cache-Control \"max-age=" . $lifetime . ", public\"\n";

                    break;

                case 'cache_validation':
                    $headers_rules .= "        Header set Pragma \"public\"\n";
                    $headers_rules .= "        Header set Cache-Control \"public, must-revalidate, proxy-revalidate\"\n";
                    break;

                case 'cache_noproxy':
                    $headers_rules .= "        Header set Pragma \"public\"\n";
                    $headers_rules .= "        Header set Cache-Control \"public, must-revalidate\"\n";
                    break;

                case 'cache_maxage':
                    $expires = $this->_config->get_boolean('browsercache.' . $section . '.expires');
                    $lifetime = $this->_config->get_integer('browsercache.' . $section . '.lifetime');

                    $headers_rules .= "        Header set Pragma \"public\"\n";

                    if ($expires)
                        $headers_rules .= "        Header append Cache-Control \"public, must-revalidate, proxy-revalidate\"\n";
                    else
                        $headers_rules .= "        Header set Cache-Control \"max-age=" . $lifetime . ", public, must-revalidate, proxy-revalidate\"\n";

                    break;

                case 'no_cache':
                    $headers_rules .= "        Header set Pragma \"no-cache\"\n";
                    $headers_rules .= "        Header set Cache-Control \"max-age=0, private, no-store, no-cache, must-revalidate\"\n";
                    break;
            }
        }

        if ($etag) {
            $rules .= "    FileETag MTime Size\n";
        } else {
            if ($compatibility) {
                $rules .= "    FileETag None\n";
                $headers_rules .= "         Header unset ETag\n";
            }
        }

        if ($unset_setcookie)
            $headers_rules .= "         Header unset Set-Cookie\n";

        if (!$set_last_modified)
            $headers_rules .= "         Header unset Last-Modified\n";

        if ($w3tc)
            $headers_rules .= "         Header set X-Powered-By \"" . W3TC_POWERED_BY . "\"\n";

        if (strlen($headers_rules) > 0) {
            $rules .= "    <IfModule mod_headers.c>\n";
            $rules .= $headers_rules;
            $rules .= "    </IfModule>\n";
        }

        if (strlen($rules) > 0) {
            $rules = "<FilesMatch \"\\.(" . implode('|', array_merge($extensions_lowercase, $extensions_uppercase)) . ")$\">\n" . $rules;
            $rules .= "</FilesMatch>\n";
        }

        return $rules;
    }

    /**
     * Takes an array of extensions single per row and/or extensions delimited by |
     * @param $extensions
     * @param $ext
     * @return array
     */
    private function _remove_extension_from_list($extensions, $ext) {
        for ($i = 0; $i < sizeof($extensions); $i++) {
            if ($extensions[$i] == $ext) {
                unset($extensions[$i]);
                return $extensions;
            } elseif (strpos($extensions[$i], $ext) !== false && strpos($extensions[$i], '|') !== false) {
                $exts = explode('|', $extensions[$i]);
                $key = array_search($ext, $exts);
                unset($exts[$key]);
                $extensions[$i] = implode('|', $exts);
                return $extensions;
            }
        }
        return $extensions;
    }

    /**
     * Returns cache rules
     *
     * @return string
     */
    function generate_rules_cache_nginx($cdnftp = false) {
        $cssjs_types = $this->_get_cssjs_types();
        $html_types = $this->_get_html_types();
        $other_types = $this->_get_other_types();

        $rules = '';
        $rules .= W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE . "\n";

        $cssjs_compression = $this->_config->get_boolean('browsercache.cssjs.compression');
        $html_compression = $this->_config->get_boolean('browsercache.html.compression');
        $other_compression = $this->_config->get_boolean('browsercache.other.compression');

        if ($cssjs_compression || $html_compression || $other_compression) {
            $compression_types = array();

            if ($cssjs_compression) {
                $compression_types = array_merge($compression_types, $cssjs_types);
            }

            if ($html_compression) {
                $compression_types = array_merge($compression_types, $html_types);
            }

            if ($other_compression) {
                $compression_types = array_merge($compression_types, array(
                    'ico' => 'image/x-icon'
                ));
            }

            unset($compression_types['html|htm']);

            $rules .= "gzip on;\n";
            $rules .= "gzip_types " . implode(' ', $compression_types) . ";\n";
        }

        $this->_generate_rules_cache_nginx($rules, $cssjs_types, 'cssjs');
        $this->_generate_rules_cache_nginx($rules, $html_types, 'html', true);
        $this->_generate_rules_cache_nginx($rules, $other_types, 'other', false, $cdnftp);

        $rules .= W3TC_MARKER_END_BROWSERCACHE_CACHE . "\n";

        return $rules;
    }

    /**
     * Writes cache rules
     *
     * @param string $rules
     * @param array $mime_types
     * @param string $section
     * @param boolean write_location
     * @return void
     */
    function _generate_rules_cache_nginx(&$rules, $mime_types, $section, $write_location = false, $cdnftp = false) {
        $expires = $this->_config->get_boolean('browsercache.' . $section . '.expires');
        $cache_control = $this->_config->get_boolean('browsercache.' . $section . '.cache.control');
        $w3tc = $this->_config->get_boolean('browsercache.' . $section . '.w3tc');

        if ($expires || $cache_control || $w3tc) {
            $lifetime = $this->_config->get_integer('browsercache.' . $section . '.lifetime');

            $extensions = array_keys($mime_types);

            // Remove ext from filesmatch if its the same as permalink extension
            $pext = strtolower(pathinfo(get_option('permalink_structure'), PATHINFO_EXTENSION));
            if ($pext) {
                $extensions = $this->_remove_extension_from_list($extensions, $pext);
            }

            $rules .= "location ~ \\.(" . implode('|', $extensions) . ")$ {\n";

            if ($expires) {
                $rules .= "    expires " . $lifetime . "s;\n";
            }

            if ($cache_control) {
                $cache_policy = $this->_config->get_string('browsercache.cssjs.cache.policy');

                switch ($cache_policy) {
                    case 'cache':
                        $rules .= "    add_header Pragma \"public\";\n";
                        $rules .= "    add_header Cache-Control \"public\";\n";
                        break;

                    case 'cache_public_maxage':
                        $rules .= "    add_header Pragma \"public\";\n";
                        $rules .= "    add_header Cache-Control \"max-age=" . $lifetime . ", public\";\n";
                        break;

                    case 'cache_validation':
                        $rules .= "    add_header Pragma \"public\";\n";
                        $rules .= "    add_header Cache-Control \"public, must-revalidate, proxy-revalidate\";\n";
                        break;

                    case 'cache_noproxy':
                        $rules .= "    add_header Pragma \"public\";\n";
                        $rules .= "    add_header Cache-Control \"public, must-revalidate\";\n";
                        break;

                    case 'cache_maxage':
                        $rules .= "    add_header Pragma \"public\";\n";
                        $rules .= "    add_header Cache-Control \"max-age=" . $lifetime . ", public, must-revalidate, proxy-revalidate\";\n";
                        break;

                    case 'no_cache':
                        $rules .= "    add_header Pragma \"no-cache\";\n";
                        $rules .= "    add_header Cache-Control \"max-age=0, private, no-store, no-cache, must-revalidate\";\n";
                        break;
                }
            }

            // nginx can't handle multiple matching locations so need to add CDN canonical rule here when BC enabled
            if ($section == 'other') {
                $w3_dispatcher = w3_instance('W3_Dispatcher');
                $rules .= $w3_dispatcher->on_browsercache_nginx_generation($cdnftp);
            }

            if ($w3tc) {
                $rules .= "    add_header X-Powered-By \"" . W3TC_POWERED_BY . "\";\n";
            }
            if ($write_location) {
                $rules .= '    try_files $uri $uri/ $uri.html /index.php?$args;' . "\n";
            }
            $rules .= "}\n";
        }
    }

    /**
     * Generate rules related to prevent for media 404 error by WP
     *
     * @return string
     */
    function generate_rules_no404wp() {
        switch (true) {
            case w3_is_apache():
            case w3_is_litespeed():
                return $this->generate_rules_no404wp_apache();

            case w3_is_nginx():
                return $this->generate_rules_no404wp_nginx();
        }

        return false;
    }

    /**
     * Generate rules related to prevent for media 404 error by WP
     *
     * @return string
     */
    function generate_rules_no404wp_apache() {
        $cssjs_types = $this->_get_cssjs_types();
        $html_types = $this->_get_html_types();
        $other_types = $this->_get_other_types();

        $extensions = array_merge(array_keys($cssjs_types), array_keys($html_types), array_keys($other_types));

        $permalink_structure = get_option('permalink_structure');
        $permalink_structure_ext = ltrim(strrchr($permalink_structure, '.'), '.');

        if ($permalink_structure_ext != '') {
            foreach ($extensions as $index => $extension) {
                if (strstr($extension, $permalink_structure_ext) !== false) {
                    $extensions[$index] = preg_replace('~\|?' . w3_preg_quote($permalink_structure_ext) . '\|?~', '', $extension);
                }
            }
        }

        $exceptions = $this->_config->get_array('browsercache.no404wp.exceptions');

        $rules = '';
        $rules .= W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP . "\n";
        $rules .= "<IfModule mod_rewrite.c>\n";
        $rules .= "    RewriteEngine On\n";
        $rules .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $rules .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";

        if (count($exceptions)) {
            $rules .= "    RewriteCond %{REQUEST_URI} !(" . implode('|', $exceptions) . ")\n";
        }

        $rules .= "    RewriteCond %{REQUEST_FILENAME} \\.(" . implode('|', $extensions) . ")$ [NC]\n";
        $rules .= "    RewriteRule .* - [L]\n";
        $rules .= "</IfModule>\n";
        $rules .= W3TC_MARKER_END_BROWSERCACHE_NO404WP . "\n";

        return $rules;
    }

    /**
     * Generate rules related to prevent for media 404 error by WP
     *
     * @return string
     */
    function generate_rules_no404wp_nginx() {
        $cssjs_types = $this->_get_cssjs_types();
        $html_types = $this->_get_html_types();
        $other_types = $this->_get_other_types();

        $extensions = array_merge(array_keys($cssjs_types), array_keys($html_types), array_keys($other_types));

        $permalink_structure = get_option('permalink_structure');
        $permalink_structure_ext = ltrim(strrchr($permalink_structure, '.'), '.');

        if ($permalink_structure_ext != '') {
            foreach ($extensions as $index => $extension) {
                if (strstr($extension, $permalink_structure_ext) !== false) {
                    $extensions[$index] = preg_replace('~\|?' . w3_preg_quote($permalink_structure_ext) . '\|?~', '', $extension);
                }
            }
        }

        $exceptions = $this->_config->get_array('browsercache.no404wp.exceptions');

        $rules = '';
        $rules .= W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP . "\n";
        $rules .= "if (-f \$request_filename) {\n";
        $rules .= "    break;\n";
        $rules .= "}\n";
        $rules .= "if (-d \$request_filename) {\n";
        $rules .= "    break;\n";
        $rules .= "}\n";

        if (count($exceptions)) {
            $rules .= "if (\$request_uri ~ \"(" . implode('|', $exceptions) . ")\") {\n";
            $rules .= "    break;\n";
            $rules .= "}\n";
        }

        $rules .= "if (\$request_uri ~* \\.(" . implode('|', $extensions) . ")$) {\n";
        $rules .= "    return 404;\n";
        $rules .= "}\n";
        $rules .= W3TC_MARKER_END_BROWSERCACHE_NO404WP . "\n";

        return $rules;
    }

    /**
     * Writes cache rules
     *
     * @throws FilesystemCredentialException with S/FTP form if it can't get the required filesystem credentials
     * @throws FileOperationException
     */
    function write_rules_cache() {
        $path = w3_get_browsercache_rules_cache_path();

        if (file_exists($path)) {
            $data = @file_get_contents($path);

            if ($data === false) {
                return false;
            }
        } else {
            $data = '';
        }

        $replace_start = strpos($data, W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE);
        $replace_end = strpos($data, W3TC_MARKER_END_BROWSERCACHE_CACHE);

        if ($replace_start !== false && $replace_end !== false && $replace_start < $replace_end) {
            $replace_length = $replace_end - $replace_start + strlen(W3TC_MARKER_END_BROWSERCACHE_CACHE) + 1;
        } else {
            $replace_start = false;
            $replace_length = 0;

            $search = array(
                W3TC_MARKER_BEGIN_MINIFY_CORE => 0,
                W3TC_MARKER_BEGIN_PGCACHE_CORE => 0,
                W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP => 0,
                W3TC_MARKER_BEGIN_WORDPRESS => 0,
                W3TC_MARKER_END_PGCACHE_CACHE => strlen(W3TC_MARKER_END_PGCACHE_CACHE) + 1,
                W3TC_MARKER_END_MINIFY_CACHE => strlen(W3TC_MARKER_END_MINIFY_CACHE) + 1
            );

            foreach ($search as $string => $length) {
                $replace_start = strpos($data, $string);

                if ($replace_start !== false) {
                    $replace_start += $length;
                    break;
                }
            }
        }

        $rules = $this->generate_rules_cache();

        if ($replace_start !== false) {
            $data = w3_trim_rules(substr_replace($data, $rules, $replace_start, $replace_length));
        } else {
            $data = w3_trim_rules($data . $rules);
        }

        w3_require_once(W3TC_INC_DIR . '/functions/activation.php');
        w3_wp_write_to_file($path, $data);
    }

    /**
     * Writes no 404 by WP rules
     *
     * @throws FilesystemCredentialException with S/FTP form if it can't get the required filesystem credentials
     * @throws FileOperationException
     */
    function write_rules_no404wp() {
        $path = w3_get_browsercache_rules_no404wp_path();

        if (file_exists($path)) {
            $data = @file_get_contents($path);

            if ($data === false) {
                return false;
            }
        } else {
            $data = '';
        }

        $replace_start = strpos($data, W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP);
        $replace_end = strpos($data, W3TC_MARKER_END_BROWSERCACHE_NO404WP);

        if ($replace_start !== false && $replace_end !== false && $replace_start < $replace_end) {
            $replace_length = $replace_end - $replace_start + strlen(W3TC_MARKER_END_BROWSERCACHE_NO404WP) + 1;
        } else {
            $replace_start = false;
            $replace_length = 0;

            $search = array(
                W3TC_MARKER_BEGIN_WORDPRESS => 0,
                W3TC_MARKER_END_PGCACHE_CORE => strlen(W3TC_MARKER_END_PGCACHE_CORE) + 1,
                W3TC_MARKER_END_MINIFY_CORE => strlen(W3TC_MARKER_END_MINIFY_CORE) + 1,
                W3TC_MARKER_END_BROWSERCACHE_CACHE => strlen(W3TC_MARKER_END_BROWSERCACHE_CACHE) + 1,
                W3TC_MARKER_END_PGCACHE_CACHE => strlen(W3TC_MARKER_END_PGCACHE_CACHE) + 1,
                W3TC_MARKER_END_MINIFY_CACHE => strlen(W3TC_MARKER_END_MINIFY_CACHE) + 1
            );

            foreach ($search as $string => $length) {
                $replace_start = strpos($data, $string);

                if ($replace_start !== false) {
                    $replace_start += $length;
                    break;
                }
            }
        }

        $rules = $this->generate_rules_no404wp();

        if ($replace_start !== false) {
            $data = w3_trim_rules(substr_replace($data, $rules, $replace_start, $replace_length));
        } else {
            $data = w3_trim_rules($data . $rules);
        }

        w3_require_once(W3TC_INC_DIR . '/functions/activation.php');
        w3_wp_write_to_file($path, $data);
    }

    /**
     * Erases cache rules
     *
     * @param string $data
     * @return string
     */
    function erase_rules_cache($data) {
        $data = w3_erase_rules($data, W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE, W3TC_MARKER_END_BROWSERCACHE_CACHE);

        return $data;
    }

    /**
     * Erases no404wp rules
     *
     * @param string $data
     * @return string
     */
    function erase_rules_no404wp($data) {
        $data = w3_erase_rules($data, W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP, W3TC_MARKER_END_BROWSERCACHE_NO404WP);

        return $data;
    }

    /**
     * Removes cache rules
     *
     * @throws FilesystemCredentialException with S/FTP form if it can't get the required filesystem credentials
     * @throws FileOperationException
     */
    function remove_rules_cache() {
        $path = w3_get_browsercache_rules_cache_path();

        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules_cache($data);

                w3_require_once(W3TC_INC_DIR . '/functions/activation.php');
                w3_wp_write_to_file($path, $data);
            }
        }
    }

    /**
     * Removes no404wp rules
     *
     * @throws FilesystemCredentialException with S/FTP form if it can't get the required filesystem credentials
     * @throws FileOperationException
     */
    function remove_rules_no404wp() {
        $path = w3_get_browsercache_rules_no404wp_path();

        if (file_exists($path)) {
            if (($data = @file_get_contents($path)) !== false) {
                $data = $this->erase_rules_no404wp($data);

                w3_require_once(W3TC_INC_DIR . '/functions/activation.php');
                w3_wp_write_to_file($path, $data);
            }
        }
    }

    /**
     * Check cache rules
     *
     * @return boolean
     */
    function check_rules_cache() {
        $path = w3_get_browsercache_rules_cache_path();
        $search = $this->generate_rules_cache();

        return (($data = @file_get_contents($path)) && strstr(w3_clean_rules($data), w3_clean_rules($search)) !== false);
    }

    /**
     * Check no404wp rules
     *
     * @return boolean
     */
    function check_rules_no404wp() {
        $path = w3_get_browsercache_rules_no404wp_path();
        $search = $this->generate_rules_no404wp();

        return (($data = @file_get_contents($path)) && strstr(w3_clean_rules($data), w3_clean_rules($search)) !== false);
    }

    /**
     * Returns required rules for module
     * @return array
     */
    function get_required_rules() {
        $rewrite_rules = array();
        $dispatcher = w3_instance('W3_Dispatcher');
        if ($domain = $dispatcher->should_browsercache_generate_rules_for_cdn()) {
            $domain = $dispatcher->get_cdn_domain();
            $cdn_rules_path = sprintf('ftp://%s/%s', $domain, w3_get_cdn_rules_path());
            $rewrite_rules[] = array('filename' => $cdn_rules_path, 'content' => $this->generate_rules_cache());
        }

        $browsercache_rules_cache_path = w3_get_browsercache_rules_cache_path();
         $rewrite_rules[] = array('filename' => $browsercache_rules_cache_path, 'content' => $this->generate_rules_cache());
         if ($this->_config->get_boolean('browsercache.no404wp')) {
            $browsercache_rules_no404wp_path = w3_get_browsercache_rules_no404wp_path();
            $rewrite_rules[] = array('filename' => $browsercache_rules_no404wp_path, 'content' => $this->generate_rules_no404wp());
        }
        return $rewrite_rules;
    }

    /**
     * @return array
     */
    function remove_rules_cache_with_message() {
        $ftp_form = null;
        $errors = array();
        $errors_short_form = array();

        if ($this->check_rules_cache()) {
            try {
                $this->remove_rules_cache();
            } catch(Exception $e) {
                $errors[] = sprintf('To disable Browser Caching rules need to be removed. To remove them manually, edit the configuration file (<strong>%s</strong>) and remove all lines between and including <strong>%s</strong> and <strong>%s</strong> markers inclusive.'
                    , w3_get_browsercache_rules_cache_path(),
                    W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE, W3TC_MARKER_END_BROWSERCACHE_CACHE);
                $errors_short_form[] = sprintf('Edit file (<strong>%s</strong>) and remove all lines between and including <strong>%s</strong> and <strong>%s</strong> markers.'
                    , w3_get_browsercache_rules_cache_path(),
                    W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE, W3TC_MARKER_END_BROWSERCACHE_CACHE);

                if (!isset($ftp_form) && $e instanceof FilesystemCredentialException)
                    $ftp_form = $e->ftp_form();
            }
        }
        return array('errors' => $errors, 'ftp_form' => $ftp_form, 'errors_short_form' => $errors_short_form);
    }

    /**
     * @return array
     */
    function remove_rules_no404wp_with_message($verify_config = false, $hide_button = '') {
        $ftp_form = null;
        $errors = array();
        $errors_short_form = array();

        $remove = $verify_config ? !$this->_config->get_boolean('browsercache.no404wp') : true;
        if ($remove && $this->check_rules_no404wp()) {
            try {
                $this->remove_rules_no404wp();
            } catch(Exception $e) {
                $errors[] = sprintf('"Do not process 404 errors for static objects with WordPress" feature is still <em>active</em>. To disable it, edit the rules in the server configuration file (<strong>%s</strong>) of the site and remove all lines between and including <strong>%s</strong> and <strong>%s</strong> markers inclusive.%s',
                    w3_get_browsercache_rules_cache_path(),
                    W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP, W3TC_MARKER_END_BROWSERCACHE_NO404WP, $hide_button);
                $errors_short_form[] = sprintf('Edit file (<strong>%s</strong>) and remove all lines between and including <strong>%s</strong> and <strong>%s</strong> markers inclusive.',
                    w3_get_browsercache_rules_cache_path(),
                    W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP, W3TC_MARKER_END_BROWSERCACHE_NO404WP);

                if (!isset($ftp_form) && $e instanceof FilesystemCredentialException)
                    $ftp_form = $e->ftp_form();
            }
        }
        return array('errors' => $errors, 'ftp_form' => $ftp_form, 'errors_short_form' => $errors_short_form);
    }
}
