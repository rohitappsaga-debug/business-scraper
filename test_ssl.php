<?php

echo 'PHP Version: '.phpversion().PHP_EOL;
echo 'curl.cainfo: '.ini_get('curl.cainfo').PHP_EOL;
echo 'openssl.cafile: '.ini_get('openssl.cafile').PHP_EOL;

$locations = openssl_get_cert_locations();
echo 'OpenSSL default_cert_file: '.$locations['default_cert_file'].PHP_EOL;
echo 'OpenSSL ini_cafile: '.$locations['ini_cafile'].PHP_EOL;

$url = 'https://generativelanguage.googleapis.com/';
echo "Testing connection to $url...".PHP_EOL;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);

if ($response === false) {
    echo 'CURL Error: '.curl_error($ch).' (Code: '.curl_errno($ch).')'.PHP_EOL;
} else {
    echo 'Connection successful!'.PHP_EOL;
}
curl_close($ch);
