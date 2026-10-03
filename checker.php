<?php
// checker.php - Standalone backend proxy for Bulk URL Checker
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow local testing

$raw_url = $_POST['url'] ?? '';
$url = trim($raw_url);

if (empty($url)) {
    echo json_encode(['success' => false, 'data' => ['message' => 'Empty URL']]);
    exit;
}

if (!preg_match('#^https?://#i', $url)) {
    $url = 'https://' . $url;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'data' => ['message' => 'Invalid URL']]);
    exit;
}

$start_time = microtime(true);

// Initialize cURL for HEAD request
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true, // Send HEAD request
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Standalone Bulk Link Checker/1.0)'
]);

curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error_msg = curl_error($ch);
curl_close($ch);

// If HEAD fails or returns common blocking codes, try GET
if ($code === 0 || in_array($code, [400, 403, 405])) {
    $ch_get = curl_init($url);
    curl_setopt_array($ch_get, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Standalone Bulk Link Checker/1.0)',
        // Download max 32kb to save memory
        CURLOPT_RANGE          => '0-32768' 
    ]);
    
    curl_exec($ch_get);
    $code = curl_getinfo($ch_get, CURLINFO_HTTP_CODE);
    $error_msg = curl_error($ch_get);
    curl_close($ch_get);
}

$response_time = round((microtime(true) - $start_time) * 1000);

if ($code === 0) {
    echo json_encode(['success' => true, 'data' => [
        'url'           => $url,
        'code'          => 0,
        'status'        => 'Connection Error',
        'status_key'    => 'error',
        'message'       => $error_msg ? $error_msg : 'DNS or Connection Failed',
        'response_time' => $response_time
    ]]);
    exit;
}

// Classify HTTP Status
if ($code >= 200 && $code < 300) {
    $status = 'Active';
    $status_key = 'active';
} elseif ($code >= 300 && $code < 400) {
    $status = 'Redirect';
    $status_key = 'redirect';
} elseif ($code >= 400 && $code < 500) {
    $status = '4xx Client Error';
    $status_key = 'client_error';
} elseif ($code >= 500 && $code < 600) {
    $status = '5xx Server Error';
    $status_key = 'server_error';
} else {
    $status = 'Unknown';
    $status_key = 'error';
}

echo json_encode(['success' => true, 'data' => [
    'url'           => $url,
    'code'          => $code,
    'status'        => $status,
    'status_key'    => $status_key,
    'message'       => 'HTTP ' . $code,
    'response_time' => $response_time
]]);
?>