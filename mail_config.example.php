<?php
// Copy this file to mail_config.php and configure it only on your local server.
// Never commit mail_config.php or real SMTP credentials to a public repository.
return [
    'host' => 'smtp.example.com',
    'username' => 'your-email@example.com',
    'password' => 'REPLACE_WITH_APP_PASSWORD',
    'encryption' => 'tls',
    'port' => 587,
    'from_name' => 'ShareBite',
];
