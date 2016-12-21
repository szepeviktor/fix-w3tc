# W3 Total Cache (Fixed) [![Build Status](https://travis-ci.org/szepeviktor/w3-total-cache-fixed.svg?branch=v0.9.4.x)](https://travis-ci.org/szepeviktor/w3-total-cache-fixed)

This project is a community driven build of W3 Total Cache (W3TC) originally developed by [@ftownes](https://github.com/ftownes).  The aim is to continuously incorporate fixes, improvements, and enhancements over the official Wordpress release of [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/).

[DONE]: http://i65.tinypic.com/2dbjpn6.png "Feature Integrated"
[PENDING]: http://i68.tinypic.com/25000tw.png "Still Pending"

**There are two actively maintained _W3TC (Fixed)_ generations in this repository: [Version 0.9.4.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.4.x) and [Version 0.9.5.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.5.x)**<br>

Both generations fill voids for two sets of users.  Although on the surface both are separated by a trivial subversion increment in their names, their underlying coding structure is vastly different.  Because of that they potentially operate differently under the same server-side configurations.

**_Version 0.9.4.x_** is for those who, for one reason or another, are unable to upgrade to W3TC v0.9.5.x and so prefer to stick with this legacy version (because they've had success with it) but still yearn for updates, security patches, and new features.

**_Version 0.9.5.x_** is a fork of the latest official release of W3TC (found on WordPress) but also integrates updates, security patches, and new features.

Make sure you select the correct generation that fits your environment.

---

### Latest Release

| Generation    | Date |Version | Download Link
| ------------- |:-------------:|:-----:|-----|
| For 0.9.4.x Users      | 2016-12-21 | 0.9.4.6| [w3-total-cache-fixed-(for-v0.9.4.x-users).zip](https://github.com/szepeviktor/w3-total-cache-fixed/files/667432/w3-total-cache-fixed-for-v0.9.4.x-users.zip)
| For 0.9.5.x Users      | ---  | --- | _soon_

---

### Installation
_**Note:** After the following steps, all future updates and installations will be handled from within WordPress._

1. Deactivate (but don't delete) your existing W3 Total Cache plugin (if installed) from within WordPress' Plugin page.
1. Use FTP or some other file manager to navigate to _`wp-content/plugins/`_.
1. Download the **_latest release_** (see above) and extract its contents into _`wp-content/plugins/`_.
1. Activate the _W3 Total Cache (Fixed)_ plugin from within WordPress' Plugin page.
1. Verify everything is working correctly and that your original configuration settings are still present. However, if any problems do occur during this installation then just deactivate this plugin and reactivate your original one.  **_Do not attempt to activate both plugins._**
1. Delete the previously deactivated plugin (step 1) from within WordPress' Plugin page.
1. Empty all caches.

### Fixes, Improvements, & Enhancement Highlights
_**Note:** This list does not reflect all of the myriad of fixes/changes -- just the key ones of interest._

![DONE] Removed Deprecated WordPress Code<br>
![DONE] Full PHP7 Compliancy (Passes [PHPCompatibility](https://github.com/wimg/PHPCompatibility): 100%)<br>
![DONE] Amazon Web Services (AWS) v4 Signature Support (IPv4 &amp; IPv6) with New Endpoints/Regions<br>
![DONE] Option to Embed Minified JS and CSS Content Directly into HTML Page<br>
![DONE] Extended WP-CLI Support<br>
![DONE] Memcache & Memcached Extension Support<br>
![DONE] APCu Support<br>
![DONE] OPcache Support<br>
![DONE] WOFF2 Font Support<br>
![DONE] Proper HTTPS Caching<br>
![DONE] AMP Support<br>
![DONE] Redis Support<br>
![DONE] Removed Nag Screens, Obsolete Widgets, & Licensing<br>
