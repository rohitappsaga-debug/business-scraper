<?php

$url = 'https://www.bing.com/search?q='.urlencode('restaurant in kamrej surat');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
echo 'Body Length: '.strlen($result)."\n";
if (str_contains($result, 'Kamrej')) {
    echo "FOUND: 'Kamrej' text in body!\n";
}
// Print some HTML snippet to find selectors
$snippet = substr($result, strpos($result, 'Kamrej') - 100, 1000);
echo 'Snippet: '.htmlspecialchars($snippet)."\n";
