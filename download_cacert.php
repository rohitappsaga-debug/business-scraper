<?php

$url = 'https://curl.se/ca/cacert.pem';
$dest = 'D:/wamp64/bin/php/php8.5.0/cacert.pem';

echo "Downloading fresh cacert.pem from $url...".PHP_EOL;

// We use stream_context_create with verify_peer=false ONLY for this download
// if the current certificate is broken, otherwise we can't download the fix!
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);

$content = file_get_contents($url, false, $context);

if ($content === false) {
    exit("Error: Failed to download cacert.pem from $url".PHP_EOL);
}

if (file_put_contents($dest, $content)) {
    echo "Successfully saved cacert.pem to $dest".PHP_EOL;
} else {
    exit("Error: Failed to save cacert.pem to $dest".PHP_EOL);
}
