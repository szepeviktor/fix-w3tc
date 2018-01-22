# W3 Total Cache (Fixed) [![Build Status](https://travis-ci.org/szepeviktor/w3-total-cache-fixed.svg?branch=v0.9.5.x)](https://travis-ci.org/szepeviktor/w3-total-cache-fixed)

This project is a community driven build of W3 Total Cache (W3TC) originally developed by [@ftownes](https://github.com/ftownes).  The aim is to continuously incorporate fixes, improvements, and enhancements over the official Wordpress release of [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/).

---

### Installation

1. Deactivate and delete your existing W3 Total Cache plugin (if installed) from within WordPress' Plugin page.
1. Use FTP or some other file manager to navigate to _`wp-content/plugins/`_.
1. Download the [latest release](https://github.com/szepeviktor/w3-total-cache-fixed/releases/latest) and extract its contents into _`wp-content/plugins/`_. The extracted directory name should be: **`w3-total-cache`**.  If not, then rename it.
1. Activate the _W3 Total Cache (Fixed)_ plugin from within WordPress' Plugin page.
1. Verify everything is working correctly and that your original configuration settings are still present.
1. Empty all caches.

### Fixes, Improvements, & Enhancement Highlights

For all changes by W3 Total Cache (Fixed) contributors (that were or not merged by W3 Total Cache official release), read the [changelog](https://github.com/szepeviktor/w3-total-cache-fixed/wiki/Changelog).

Since the last [official release](https://wordpress.org/plugins/w3-total-cache/) of W3 Total Cache, the following new features, bug fixes, and updates have been applied to this repository's:

<!--- :cyclone: New Feature | [Label](https://github.com/) | --->
<!--- :beetle: Bug Fix | [Label](https://github.com/) | --->
<!--- :diamond_shape_with_a_dot_inside: Update | [Label](https://github.com/) | --->

Type | More Information |
:--- | --- |
:beetle: Bug Fix | [Fix for "Fatal error: Uncaught exception 'Exception' with message 'unknown engine'"](https://github.com/szepeviktor/w3-total-cache-fixed/pull/553) |
:beetle: Bug Fix | [PHP Notice:  Undefined index: minify in CacheFlush_Locally.php](https://github.com/szepeviktor/w3-total-cache-fixed/pull/554) |
:beetle: Bug Fix | [PHP Notice: Undefined offset: 1 in Extension_CloudFlare_Plugin.php on line 376](https://github.com/szepeviktor/w3-total-cache-fixed/pull/555) |
(https://github.com/szepeviktor/w3-total-cache-fixed/pull/555) |
:beetle: Bug Fix | [Nginx: missing semicolon](https://github.com/szepeviktor/w3-total-cache-fixed/pull/556) |
