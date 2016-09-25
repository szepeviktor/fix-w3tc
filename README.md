# Fix W3TC (W3 Total Cache) [![Build Status](https://travis-ci.org/szepeviktor/fix-w3tc.svg?branch=master)](https://travis-ci.org/szepeviktor/fix-w3tc)

A community driven build of W3 Total Cache, originally developed by [@ftownes](https://github.com/ftownes).  The aim is to continuously incorporate fixes, improvements, and enhancements over the official (and long abandoned: 2 years) Wordpress release of [W3 Total Cache v0.9.4.1](https://wordpress.org/plugins/w3-total-cache/).

[check]: http://www.ingenuity.com/wp-content/uploads/2013/06/checkmark-survey-icon.png "Logo Title Text 1"
[uncheck]: http://iconshow.me/media/images/ui/ios7-icons/png/16/circle-outline.png "Off Title Text 1"

### Installation

1. Deactivate your existing W3 Total Cache plugin (if it exists).  **_DO NOT_ CLICK THE "DELETE" BUTTON!**
1. Use FTP or some other file manager to navigate to `/wp-content/plugins/` and delete your existing `w3-total-cache` plugin directory.
1. Download and unpack: **_[Master](https://github.com/szepeviktor/fix-w3tc/archive/master.zip)_** into `/wp-content/plugins/`
1. Rename the extracted directory from `fix-w3tc-master` to `w3-total-cache`
1. Activate the W3 Total Cache plugin

### Fixes, Improvements, & Enhancement Highlights
_**Note:** This list does not reflect all of the myriad of fixes/changes -- just the key ones of interest_

![check] Removed Deprecated WordPress Code<br>
![check] Full PHP7 Compliancy (Passes [PHPCompatibility](https://github.com/wimg/PHPCompatibility): 100%)<br>
![check] Memcache & Memcached Extension Support<br>
![check] APCu Support<br>
![check] OPcache Support<br>
![check] WOFF2 Font Support<br>
![check] Proper HTTPS Caching<br>
![check] AMP Support<br>
![check] Redis Support<br>
![check] Removed Nag Screens, Obsolete Widgets, & Licensing<br>
![uncheck] Improved CloudFlare Support (**_Status_**: [Half-done](https://github.com/szepeviktor/fix-w3tc/issues/68))
