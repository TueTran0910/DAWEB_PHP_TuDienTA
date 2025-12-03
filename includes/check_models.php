<?php
// check_models.php
$apiKey = 'RSOYigrH1dJ3nSNX2p5rEgLAaW2PdLu1ZlHJGMOK'; // Key của bạn
$url = 'https://api.cohere.com/v1/models'; 

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    echo "<h1>Danh sách Model được dùng:</h1>";
    echo "<ul>";
    foreach ($data['models'] as $model) {
        // Chỉ lấy model hỗ trợ Chat
        if (in_array('chat', $model['endpoints'])) {
            echo "<li style='font-size:1.2rem; margin-bottom: 5px;'>
                    <strong style='color:blue'>{$model['name']}</strong>
                  </li>";
        }
    }
    echo "</ul>";
} else {
    echo "Lỗi: " . $response;
}
?>