<?php

$url = 'https://www.google.com/search?q=restaurant+in+kamrej+surat&npsic=0&rflfq=1&rldoc=1';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36');
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
echo 'Body Length: '.strlen($result)."\n";
if (str_contains($result, 'captcha')) {
    echo "BLOCKED: CAPTCHA detected.\n";
} else {
    echo 'First 500 chars: '.substr(strip_tags($result), 0, 500)."...\n";
    // Check for some common local pack text
    if (str_contains($result, 'Kamrej')) {
        echo "FOUND: 'Kamrej' text in body!\n";
    }
}
