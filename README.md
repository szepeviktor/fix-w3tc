# W3 Total Cache (Fixed) [![Build Status](https://travis-ci.org/szepeviktor/w3-total-cache-fixed.svg?branch=v0.9.5.x)](https://travis-ci.org/szepeviktor/w3-total-cache-fixed)

This project is a community driven build of W3 Total Cache (W3TC) originally developed by [@ftownes](https://github.com/ftownes).  The aim is to continuously incorporate fixes, improvements, and enhancements over the official Wordpress release of [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/).

[DONE]: http://i65.tinypic.com/2dbjpn6.png "Feature Integrated"
[PENDING]: http://i68.tinypic.com/25000tw.png "Still Pending"

**There are two actively maintained _W3TC (Fixed)_ generations in this repository: [Version 0.9.5.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.5.x) and [Version 0.9.4.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.4.x)**<br>

Both generations fill voids for two sets of users.  Although on the surface both are separated by a trivial subversion increment in their names, their underlying coding structure is vastly different.  Because of that they potentially operate differently under the same server-side configurations.

**_Version 0.9.5.x_** is a fork of the latest official release of W3TC (found on WordPress) but also integrates updates, security patches, and new features.

**_Version 0.9.4.x_** is for those who, for one reason or another, are unable to upgrade to W3TC v0.9.5.x and so prefer to stick with this legacy version (because they've had success with it) but still yearn for updates, security patches, and new features.

Make sure you select the correct generation that fits your environment.

---

### Latest Release

| Generation    | Date |Version | Download Link
| ------------- |:-------------:|:-----:|-----|
| For 0.9.5.x Users      | 2017-02-02 | 0.9.5.2.2 | [w3-total-cache-fixed-(for-v0.9.5.x-users).zip](https://github.com/szepeviktor/w3-total-cache-fixed/releases/download/0.9.5.2.2/w3-total-cache-fixed-for-v0.9.5.x-users.zip) 
| For 0.9.4.x Users      | 2017-02-03 | 0.9.4.6.3 | [w3-total-cache-fixed-(for-v0.9.4.x-users).zip](https://github.com/szepeviktor/w3-total-cache-fixed/releases/download/0.9.4.6.3/w3-total-cache-fixed-for-v0.9.4.x-users.zip)

---

### Installation (for v0.9.5.x Users)

1. Deactivate and delete your existing W3 Total Cache plugin (if installed) from within WordPress' Plugin page.
1. Use FTP or some other file manager to navigate to _`wp-content/plugins/`_.
1. Download the **_latest release_** (see above) and extract its contents into _`wp-content/plugins/`_.
1. Activate the _W3 Total Cache (Fixed)_ plugin from within WordPress' Plugin page.
1. Verify everything is working correctly and that your original configuration settings are still present.
1. Empty all caches.

### Fixes, Improvements, & Enhancement Highlights

Since the last [official release](https://wordpress.org/plugins/w3-total-cache/) of W3 Total Cache (v0.9.5.2), the following new features, bug fixes, and updates have been applied to this repository's [v0.9.5.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.5.x) branch, which has its base already synced to v0.9.5.2:

Type | More Information |
:--- | --- |
:cyclone: New Feature | [Dashboard Widget For Flushing Individual User-Inputted URLs](https://github.com/szepeviktor/w3-total-cache-fixed/commit/f098003e8e4b4a3dbc2504b8a47b62205d5f6b9b)<br>:wrench: [+ **Extra: #PR335** - Adds Missing Nonce check](https://github.com/szepeviktor/w3-total-cache-fixed/pull/335) |
:beetle: Bug Fix | [Gzipped Cached Pages Are Not Decoded Correctly -- PHP 5.3.x Specific](https://github.com/szepeviktor/w3-total-cache-fixed/pull/313) |
:beetle: Bug Fix | [_{uploads_dir}_ Placeholder & Full URLS Issue In CDN Custom Files Field](https://github.com/szepeviktor/w3-total-cache-fixed/pull/316) |
:diamond_shape_with_a_dot_inside: Update | [CSSTidy Updated to v1.5.5 With New Options - Requires PHP 5.4+](https://github.com/szepeviktor/w3-total-cache-fixed/pull/317) |
:cyclone: New Feature | [Google PageSpeed Widget - Key Restriction Field Added](https://github.com/szepeviktor/w3-total-cache-fixed/pull/318) |
:cyclone: New Feature | [Page Cache - Added 4 New "_Never Cache ..._" Fields](https://github.com/szepeviktor/w3-total-cache-fixed/pull/319)<br>:wrench: [+ **Extra: #PR320** - Adds Missing Check for Page & Post Type](https://github.com/szepeviktor/w3-total-cache-fixed/pull/320) |
:beetle: Bug Fix | [Admin Image URLs Malformed For Must-Use Plugins (mu-plugins)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/322) |
:cyclone: New Feature | [WP-CLI - Prime the Page Cache (Cache Preload)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/324) |
:beetle: Bug Fix | [Flushing Not Working Consistently For Post Changes](https://github.com/szepeviktor/w3-total-cache-fixed/pull/331) |
:diamond_shape_with_a_dot_inside: Update | [Amazon Web Services (AWS) Signature v4 Support & New Locations](https://github.com/szepeviktor/w3-total-cache-fixed/pull/332) |
:beetle: Bug Fix | [Save Cloudflare Settings - SSL Update Failure](https://github.com/szepeviktor/w3-total-cache-fixed/pull/334) |
:cyclone: New Feature | [Rewrite URLs via _wp_..._attachment_for_js()_ filter when CDN is Enabled](https://github.com/szepeviktor/w3-total-cache-fixed/pull/336)<br>:wrench: [+ **Extra: #PR350** - Checkbox to Use CDN URLs for Media Library](https://github.com/szepeviktor/w3-total-cache-fixed/pull/350) |
:beetle: Bug Fix | [Malformed HTML in Generated Item UIs (Admin Pages)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/343) |
:cyclone: New Feature | ["Security Headers" Section Added to Browser Cache](https://github.com/szepeviktor/w3-total-cache-fixed/pull/344) |
:beetle: Bug Fix | [W3TC is Collecting Tracking Usage At All Times ](https://github.com/szepeviktor/w3-total-cache-fixed/pull/349) :fire: |
:beetle: Bug Fix | [Additional Home URLs (of Page Cache) not Purging](https://github.com/szepeviktor/w3-total-cache-fixed/pull/357) |