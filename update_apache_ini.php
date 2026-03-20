<?php

$phpIniPath = 'D:\wamp64\bin\apache\apache2.4.65\bin\php.ini';
$cacertPath = 'D:\wamp64\bin\php\php8.5.0\cacert.pem';

if (! file_exists($phpIniPath)) {
    exit("Apache php.ini not found at $phpIniPath\n");
}

$content = file_get_contents($phpIniPath);

// Replace or uncomment curl.cainfo
if (preg_match('/^;?curl\.cainfo\s*=.*/m', $content)) {
    $content = preg_replace('/^;?curl\.cainfo\s*=.*/m', "curl.cainfo = \"$cacertPath\"", $content);
} else {
    $content .= "\r\n[curl]\r\ncurl.cainfo = \"$cacertPath\"\r\n";
}

// Replace or uncomment openssl.cafile
if (preg_match('/^;?openssl\.cafile\s*=.*/m', $content)) {
    $content = preg_replace('/^;?openssl\.cafile\s*=.*/m', "openssl.cafile = \"$cacertPath\"", $content);
} else {
    $content .= "\r\n[openssl]\r\nopenssl.cafile = \"$cacertPath\"\r\n";
}

file_put_contents($phpIniPath, $content);

echo "Apache php.ini updated successfully.\n";
