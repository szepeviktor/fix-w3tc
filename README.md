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
| For 0.9.5.x Users      | 2017-02-21 | 0.9.5.2.3 | [w3-total-cache-fixed-(for-v0.9.5.x-users).zip](https://github.com/szepeviktor/w3-total-cache-fixed/releases/download/0.9.5.2.3/w3-total-cache-fixed-for-v0.9.5.x-users.zip) 
| For 0.9.4.x Users      | 2017-02-21 | 0.9.4.6.4 | [w3-total-cache-fixed-(for-v0.9.4.x-users).zip](https://github.com/szepeviktor/w3-total-cache-fixed/releases/download/0.9.4.6.4/w3-total-cache-fixed-for-v0.9.4.x-users.zip)

---

### Installation (for v0.9.5.x Users)

1. Deactivate and delete your existing W3 Total Cache plugin (if installed) from within WordPress' Plugin page.
1. Use FTP or some other file manager to navigate to _`wp-content/plugins/`_.
1. Download the **_latest release_** (see above) and extract its contents into _`wp-content/plugins/`_. The extracted directory name should be: **`w3-total-cache`**.  If not, then rename it.
1. Activate the _W3 Total Cache (Fixed)_ plugin from within WordPress' Plugin page.
1. Verify everything is working correctly and that your original configuration settings are still present.
1. Empty all caches.

### Fixes, Improvements, & Enhancement Highlights

Since the last [official release](https://wordpress.org/plugins/w3-total-cache/) of W3 Total Cache (v0.9.5.2), the following new features, bug fixes, and updates have been applied to this repository's [v0.9.5.x](https://github.com/szepeviktor/w3-total-cache-fixed/tree/v0.9.5.x) branch, which has its base already synced to v0.9.5.2:

Type | More Information |
:--- | --- |
:cyclone: New Feature | [Dashboard Widget For Flushing Individual User-Inputted URLs](https://github.com/szepeviktor/w3-total-cache-fixed/commit/f098003e8e4b4a3dbc2504b8a47b62205d5f6b9b)<br>:wrench: [+ **Extra: #PR335** &ndash; Adds Missing Nonce check](https://github.com/szepeviktor/w3-total-cache-fixed/pull/335) |
:beetle: Bug Fix | [Gzipped Cached Pages Are Not Decoded Correctly &ndash; PHP 5.3.x Specific](https://github.com/szepeviktor/w3-total-cache-fixed/pull/313) |
:beetle: Bug Fix | [_{uploads_dir}_ Placeholder & Full URLS Issue In CDN Custom Files Field](https://github.com/szepeviktor/w3-total-cache-fixed/pull/316) |
:diamond_shape_with_a_dot_inside: Update | [CSSTidy Updated to v1.5.5 With New Options &ndash; Requires PHP 5.4+](https://github.com/szepeviktor/w3-total-cache-fixed/pull/317) |
:cyclone: New Feature | [Google PageSpeed Widget &ndash; Key Restriction Field Added](https://github.com/szepeviktor/w3-total-cache-fixed/pull/318) |
:cyclone: New Feature | [Page Cache &ndash; Added 4 New "_Never Cache ..._" Fields](https://github.com/szepeviktor/w3-total-cache-fixed/pull/319)<br>:wrench: [+ **Extra: #PR320** &ndash; Adds Missing Check for Page & Post Type](https://github.com/szepeviktor/w3-total-cache-fixed/pull/320) |
:beetle: Bug Fix | [Admin Image URLs Malformed For Must-Use Plugins (mu-plugins)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/322) |
:cyclone: New Feature | [WP-CLI &ndash; Prime the Page Cache (Cache Preload)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/324) |
:beetle: Bug Fix | [Flushing Not Working Consistently For Post Changes](https://github.com/szepeviktor/w3-total-cache-fixed/pull/331) |
:diamond_shape_with_a_dot_inside: Update | [Amazon Web Services (AWS) Signature v4 Support & New Locations](https://github.com/szepeviktor/w3-total-cache-fixed/pull/332) |
:beetle: Bug Fix | [Save Cloudflare Settings &ndash; SSL Update Failure](https://github.com/szepeviktor/w3-total-cache-fixed/pull/334) |
:cyclone: New Feature | [Rewrite URLs via _wp_..._attachment_for_js()_ filter when CDN is Enabled](https://github.com/szepeviktor/w3-total-cache-fixed/pull/336)<br>:wrench: [+ **Extra: #PR350** &ndash; Checkbox to Use CDN URLs for Media Library](https://github.com/szepeviktor/w3-total-cache-fixed/pull/350) |
:beetle: Bug Fix | [Malformed HTML in Generated Item UIs (Admin Pages)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/343) |
:cyclone: New Feature | ["Security Headers" Section Added to Browser Cache](https://github.com/szepeviktor/w3-total-cache-fixed/pull/344)<br>:wrench: [+ **Extra: #PR363** &ndash; Adds Default Values to CSP (Security Headers)](https://github.com/szepeviktor/w3-total-cache-fixed/pull/363)<br>:wrench: [+ **Extra: #PR377** &ndash; Important Change &ndash; Session Cookies](https://github.com/szepeviktor/w3-total-cache-fixed/pull/377) |
:beetle: Bug Fix | [W3TC is Collecting Tracking Usage At All Times](https://github.com/szepeviktor/w3-total-cache-fixed/pull/349) |
:beetle: Bug Fix | [Additional Home URLs (set in Page Cache) not Purging](https://github.com/szepeviktor/w3-total-cache-fixed/pull/357) |
:beetle: Bug Fix | [Configuration Bug &ndash; Redis/Memcached Server Entries](https://github.com/szepeviktor/w3-total-cache-fixed/pull/367) |
:beetle: Bug Fix | [Undefined Variable: _is_amp_endpoint_](https://github.com/szepeviktor/w3-total-cache-fixed/pull/370) |
:beetle: Bug Fix | [Error Message: _Trying to Get Property of Non-Object_](https://github.com/szepeviktor/w3-total-cache-fixed/pull/376) |
:diamond_shape_with_a_dot_inside: Update | [Page Cache &ndash; _Accepted Query Strings_ Enhancement](https://github.com/szepeviktor/w3-total-cache-fixed/pull/380) |
:beetle: Bug Fix | [Incorrect Use of Removing Query String From URLs](https://github.com/szepeviktor/w3-total-cache-fixed/pull/382) |
:diamond_shape_with_a_dot_inside: Update | [Enhance _remove_query()_ to Recognize Other Ampersand Forms](https://github.com/szepeviktor/w3-total-cache-fixed/pull/383) |
:beetle: Bug Fix | [WP Query String Being Stripped Unexpectedly](https://github.com/szepeviktor/w3-total-cache-fixed/pull/384) |
:cyclone: New Feature | [Filter to Set Cache Lifetime Period On A Per-Page Basis](https://github.com/szepeviktor/w3-total-cache-fixed/pull/388) |
:beetle: Bug Fix | [Warning: _Invalid Arguments in Minify_Environment.php_](https://github.com/szepeviktor/w3-total-cache-fixed/pull/389) |
:beetle: Bug Fix | [Feeds Not Caching Nor Serving Back as XML](https://github.com/szepeviktor/w3-total-cache-fixed/pull/393) |
:diamond_shape_with_a_dot_inside: Update | [Smart Browser Cache Default Settings](https://github.com/szepeviktor/w3-total-cache-fixed/pull/394)<br>:wrench: [+ **Extra: #PR395** &ndash; A Few More Useful Smart Default Settings](https://github.com/szepeviktor/w3-total-cache-fixed/pull/395) |
:diamond_shape_with_a_dot_inside: Update | [Expanded Regex Support & Improved Page Cache Cookies](https://github.com/szepeviktor/w3-total-cache-fixed/pull/398) |
:beetle: Bug Fix | [Debug Mode Not Working](https://github.com/szepeviktor/w3-total-cache-fixed/pull/405)<br>:wrench: [+ **Extra: #PR406** &ndash; Missed File - Debug Mode Not Working](https://github.com/szepeviktor/w3-total-cache-fixed/pull/406) |
:beetle: Bug Fix | [PHP Deprecation Notice &ndash; _is_comments_popup()_](https://github.com/szepeviktor/w3-total-cache-fixed/pull/407) |
:beetle: Bug Fix | [_W3TC-Include-JS-Head_ Tag Implementation Missing For Auto Mode](https://github.com/szepeviktor/w3-total-cache-fixed/pull/401) |
:beetle: Bug Fix | [Catch Exceptions Thrown When Saving Config](https://github.com/szepeviktor/w3-total-cache-fixed/pull/408) |
:beetle: Bug Fix | [Fix feeds on the dashboard](https://github.com/szepeviktor/w3-total-cache-fixed/pull/413) |
:diamond_shape_with_a_dot_inside: Update | [Make the dashboard responsive](https://github.com/szepeviktor/w3-total-cache-fixed/pull/414) |
:beetle: Bug Fix | [Deprecated the "Allow, Deny, and Order" directives](https://github.com/szepeviktor/w3-total-cache-fixed/pull/418) |
:beetle: Bug Fix | [Util_Environment::document_root() On Windows return "/" instead of "\\"](https://github.com/szepeviktor/w3-total-cache-fixed/pull/422) |
:cyclone: New Feature | [Customize Cache Directory](https://github.com/szepeviktor/w3-total-cache-fixed/pull/423) |
:beetle: Bug Fix | [YUI Compressor fix for JAVA path](https://github.com/szepeviktor/w3-total-cache-fixed/pull/426) |
:beetle: Bug Fix | [Closure Compiler fix for JAVA path](https://github.com/szepeviktor/w3-total-cache-fixed/pull/428) |
:beetle: Bug Fix | [Fixed Redis Test on Admin Dashboard](https://github.com/szepeviktor/w3-total-cache-fixed/pull/430) |
