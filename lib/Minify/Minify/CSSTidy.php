<?php

class Minify_CSSTidy {
    public static function minify($css, $options = array()) {
        $options = array_merge(array(
            'remove_bslash' => true,
            'compress_colors' => false,
            'compress_font-weight' => false,
            'lowercase_s' => false,
            'optimise_shorthands' => 0,
            'remove_last_;' => false,
            'space_before_important' => false,
            'case_properties' => 1,
            'sort_properties' => false,
            'sort_selectors' => false,
            'merge_selectors' => 0,
            'discard_invalid_selectors' => false,
            'discard_invalid_properties' => false,
            'css_level' => 'CSS3.0',
            'preserve_css' => false,
            'timestamp' => false,
            'template' => 'default'
        ), $options);

        set_include_path(get_include_path() . PATH_SEPARATOR . W3TC_LIB_DIR . '/CSSTidy');

        require_once 'class.csstidy.php';

        $csstidy = new csstidy();

        foreach ($options as $option => $value) {
            $csstidy->set_cfg($option, $value);
        }

        $csstidy->load_template($options['template']);
        $csstidy->parse($css);

        $css = $csstidy->print->plain();

        $css = Minify_CSS_UriRewriter::rewrite($css, $options);

        return $css;
    }
}
