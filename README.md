Notify Module
==================

This module is required to send messages to email.

Links
-----
  * [Home Page](http://abricos.org/mods/notify/)
  * [Home Page (Russian)](http://ru.abricos.org/mods/notify/)

Configuration
-----

All settings in the file /includes/config.php

 ### Config SMTP
 
	$config["module"]["notify"] = array(
		"SMTP" => true,                          // SMTP enable
		"SMTPHost" => "mail.yourdomain.com",     // SMTP Server
		"SMTPPort" => 26,                        // SMTP Port
		"SMTPAuth" => true,                      // SMTP use authorise
		"SMTPUsername" => "yourname@youdomain",  // SMTP Username 
		"SMTPPassword" => "yourpassword"         // SMTP Password
	);
 
 ### Debug mode
If debugging is enabled sends a message, the message is do not go 
to the post office, but are created as separate files in the 
folder /cache/eml. 
In this folder /cache/eml should have write access (chmod 777).

	$config['module']['notify'] = array(
		"totestfile" => true
	);
 