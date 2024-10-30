=== Maven Media Protect ===
Contributors: mustela, CarlosBaena
Tags: authentication, private, block, files, content, login, password, member
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 0.2

Protect files with user and password. 

== Description ==

Protect files with WordPress Authentication, using a "Proxy" approach.

Note: Please, do not use this plugin if you have too many files to protect. There are other better ways to do it. 

= Features: =

* Protect files

== Installation ==

Maven Media Protect&trade; is designed to run with minimal modifications in your WP instalation.

= Basic Install: =

1. Upload the `/maven-media-protector/` directory and its contents to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress&reg;
3. Add the following lines to your  .htaccess

RewriteCond %{REQUEST_FILENAME} -s 

RewriteRule ^wp-content/uploads/(.*)$ /wp-content/plugins/maven-media-protector/maven-media-protector-proxy.php?file=$1 [QSA,L]




== Screenshots ==
1. WordPress Media

== Changelog ==
 
