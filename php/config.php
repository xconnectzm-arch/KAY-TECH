<?php
/* SMTP configuration for Titan Email */
define('SMTP_HOST', 'smtp.titan.email');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'you@yourdomain.com'); // TODO: set your Titan mailbox
define('SMTP_PASSWORD', 'REPLACE_WITH_APP_PASSWORD'); // TODO: set Titan app password
define('MAIL_TO', 'info@yourdomain.com'); // Where to receive contact messages
define('MAIL_TO_NAME', 'Kaytech Website'); // Recipient display name
define('MAIL_SUBJECT', 'New message from Kaytech contact form');
define('RECAPTCHA_SECRET', ''); // optional
