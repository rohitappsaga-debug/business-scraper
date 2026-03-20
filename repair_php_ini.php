<?php

$phpIniPath = 'D:\wamp64\bin\php\php8.5.0\php.ini';
$cacertPath = 'D:\wamp64\bin\php\php8.5.0\cacert.pem';

if (! file_exists($phpIniPath)) {
    exit("php.ini not found at $phpIniPath\n");
}

$lines = file($phpIniPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$newLines = [];

$curlFound = false;
$opensslFound = false;

foreach ($lines as $line) {
    // Check for corruption: if a line contains parts of another line unexpectedly
    // Example: "wildcard patterns allowed.         races-vs17-x86_64.dll"
    if (str_contains($line, 'wildcard patterns allowed') && str_contains($line, '.dll')) {
        // Fix this specific line
        $line = ';ffi.preload=';
    }

    // Update curl.cainfo
    if (preg_match('/^;?curl\.cainfo\s*=/', $line)) {
        $line = "curl.cainfo = \"$cacertPath\"";
        $curlFound = true;
    }

    // Update openssl.cafile
    if (preg_match('/^;?openssl\.cafile\s*=/', $line)) {
        $line = "openssl.cafile = \"$cacertPath\"";
        $opensslFound = true;
    }

    $newLines[] = $line;
}

// If they weren't found (unlikely as they should be in the file), add them
if (! $curlFound) {
    $newLines[] = '[curl]';
    $newLines[] = "curl.cainfo = \"$cacertPath\"";
}
if (! $opensslFound) {
    if (! in_array('[openssl]', $newLines)) {
        $newLines[] = '[openssl]';
    }
    $newLines[] = "openssl.cafile = \"$cacertPath\"";
}

// Write the fixed file
file_put_contents($phpIniPath, implode("\r\n", $newLines));

echo "php.ini updated successfully.\n";
