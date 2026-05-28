<?php
$apiKey = 'AIzaSyDYu_ZbY2VZd0ikchIWD7y1hHuynFUZq-c';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<pre>";
foreach ($data['models'] ?? [] as $m) {
    if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
        echo "✅ " . $m['name'] . "\n";
    }
}
echo "</pre>";