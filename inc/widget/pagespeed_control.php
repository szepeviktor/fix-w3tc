<?php if (!defined('W3TC')) die(); ?>
<p>
	<label>
        Page Speed <acronym title="Application Programming Interface">API</acronym> Key:
	    <input type="text" name="w3tc_widget_pagespeed_key" value="<?php echo esc_attr($this->_config->get_string('widget.pagespeed.key')); ?>" size="40" class="w3tc-ignore-change" />
    </label>
</p>
<p>To acquire an <acronym title="Application Programming Interface">API</acronym> key, visit the <a href="https://code.google.com/apis/console" target="_blank">APIs Console</a>. Go to the Project Home tab, activate the Page Speed Online <acronym title="Application Programming Interface">API</acronym>, and accept the Terms of Service.</p>
<p>Then go to the <acronym title="Application Programming Interface">API</acronym> Access tab. The <acronym title="Application Programming Interface">API</acronym> key is in the Simple <acronym title="Application Programming Interface">API</acronym> Access section.</p>
<p>
	<label>
        Key Restriction (Referrer):
         <input type="text" name="widget_pagespeed_key_restrict_referrer" value="<?php echo esc_attr($this->_config->get_string('widget.pagespeed.key.restrict.referrer')); ?>" size="40" class="w3tc-ignore-change" />
    </label>
</p>
<p>Although not required, to prevent unauthorized use and quota theft, you have the option to restrict your key using a designated HTTP referrer. If you decide to use it, you will need to set this referrer within the API Console's "Http Referrers (web sites)" key restriction area (under Credentials).</p>