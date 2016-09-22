# Fix W3TC (W3 Total Cache) [![Build Status](https://travis-ci.org/szepeviktor/fix-w3tc.svg?branch=master)](https://travis-ci.org/szepeviktor/fix-w3tc)

Fix and customize W3 Total Cache by [@ftownes](https://github.com/ftownes)

### Installation

1. Deactivate official plugin from WP.org
1. Delete plugin directory `w3-total-cache` by hand, DO NOT click "Delete" on Plugins page
1. Unpack [master](https://github.com/szepeviktor/fix-w3tc/archive/master.zip) in `/wp-content/plugins/`
1. Rename the extracted directory from `fix-w3tc-master` to `w3-total-cache`
1. Activate w3tc
1. Check that it's working

### Fix

- [x] Modernize deprecated WordPress code
- [x] Support PHP7 [disallow reference arguments](https://github.com/php/php-src/commit/fafe01b)
- [x] Add Memcache(d) support
- [x] Add APCu support
- [x] Support OPcache
- [x] Support WOFF2 font format
- [x] Fix https caching
- [ ] [Halfdone CloudFlare support in trunk](https://github.com/szepeviktor/fix-w3tc/issues/68)
- [x] Support AMP
- [x] Support Redis

### And customize

- [x] Disable edge mode `evaluation.reminder`
- [x] Hide most widgets from W3TC Dashboard
- [x] Remove informational submenus `$pages_tail`
- [x] Remove contextual help `w3tc_*`
- [x] Make admin pages smaller `#w3tc h2.logo { float: right; }`
- [x] Remove HTML comment by hooking `w3tc_can_print_comment`
- [x] Disable EDD/licensing

### Disabled submenus

- FAQ
- Support
- Install
- About

### Disabled W3TC plugins

- W3_Plugin_NewRelicAdmin
- W3_Licensing

### Disabled W3TC widgets

- W3_Widget_SpreadTheWord
- W3_Widget_News
- W3_Widget_Forum
- W3_Widget_MaxCDN
- W3_Widget_NetDNA
- W3_Widget_NewRelic

### Mini CI

```bash
find -type f -name "*.php" -exec php -l "{}" ";"
```

### Upstream

svn: https://plugins.svn.wordpress.org/w3-total-cache/

git: https://github.com/wp-plugins/w3-total-cache.git

### PHP 7 support

In version 7.0.9 reference arguments were disallowed in `call_user_func()`.

This is how to find them in the code:

```bash
grep -Fnr 'ob_callback(&$buffer)' w3-total-cache/*
```

Props. [IT Nota](https://www.itnota.com/fixing-php7-compatibility-issue-w3-total-cache/)

### Alternatives

- https://github.com/bermanco/w3-total-cache-php7
- https://github.com/tperalta82/w3-total-cache-ng
- https://github.com/wp-plugins/w3tc-auto-pilot
- https://github.com/StefanoWP/W3-Total-Cache-WordPress-Plugin-Default-Settings

<!--
configwriter: master.phps ???
inspect alternative
try Google Page Speed API on dashboard widget
-->
