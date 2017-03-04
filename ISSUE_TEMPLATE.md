Thank you for reporting an issue!

Please make sure you describe the issue and your use case.

Is helpful provide some info on your environment (if you know it), eg.:
- Web Server service (eg. Apache, NGINX , IIS, Lighttpd) and version 
- PHP version and handler type (eg. suPHP - Single user PHP, DSO - Dynamic Shared Object, FCGI - FastCGI, CGI - Common Gateway Interface)
- Database service (eg. MySQL, SQL Server) and version 
- Wordpress version
- W3 Total Cache version

Is very helpful provide the error log. You can allow error logging, add or edit these lines in the `wp-config.php` file:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```
with these lines, on each error, Wordpress write an error log in `\wp-content\debug.log`


If you are also proposing a solution then please make sure to elaborate on why your solution is adequate.
