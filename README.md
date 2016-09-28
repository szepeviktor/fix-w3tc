# Fix W3TC (W3 Total Cache) [![Build Status](https://travis-ci.org/szepeviktor/fix-w3tc.svg?branch=master)](https://travis-ci.org/szepeviktor/fix-w3tc)

A community driven build of W3 Total Cache, originally developed by [@ftownes](https://github.com/ftownes).  The aim is to continuously incorporate fixes, improvements, and enhancements over the official Wordpress release of [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/).

[DONE]: http://i65.tinypic.com/2dbjpn6.png "Feature Integrated"
[PENDING]: http://i68.tinypic.com/25000tw.png "Still Pending"

### Installation

1. Deactivate your existing W3 Total Cache plugin (if it exists).  **_DO NOT_ CLICK THE "DELETE" BUTTON!**
1. Use FTP or some other file manager to navigate to `/wp-content/plugins/` and delete your existing `w3-total-cache` plugin directory.
1. Download and unpack: **_[Master](https://github.com/szepeviktor/fix-w3tc/archive/master.zip)_** into `/wp-content/plugins/`
1. Rename the extracted directory from `fix-w3tc-master` to `w3-total-cache`
1. Activate the W3 Total Cache plugin

### Fixes, Improvements, & Enhancement Highlights
_**Note:** This list does not reflect all of the myriad of fixes/changes -- just the key ones of interest_

![DONE] Removed Deprecated WordPress Code<br>
![DONE] Full PHP7 Compliancy (Passes [PHPCompatibility](https://github.com/wimg/PHPCompatibility): 100%)<br>
![DONE] Memcache & Memcached Extension Support<br>
![DONE] APCu Support<br>
![DONE] OPcache Support<br>
![DONE] WOFF2 Font Support<br>
![DONE] Proper HTTPS Caching<br>
![DONE] AMP Support<br>
![DONE] Redis Support<br>
![DONE] Removed Nag Screens, Obsolete Widgets, & Licensing<br>
![PENDING] Improved CloudFlare Support (**_Status_**: [Half-done](https://github.com/szepeviktor/fix-w3tc/issues/68))
