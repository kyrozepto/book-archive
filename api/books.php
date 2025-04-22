<?php
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check API key
if (!isset($_SERVER['HTTP_X_API_KEY'])) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required', 'details' => 'Please provide a valid API key in the X-API-Key header']);
    exit;
}

$api_key = $_SERVER['HTTP_X_API_KEY'];
if (!$auth->validateApiKey($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key', 'details' => 'The provided API key is not valid or has expired']);
    exit;
}

// Get search query
$search = $_GET['search'] ?? '';
if (empty($search)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query required', 'details' => 'Please provide a search term']);
    exit;
}

// Search Open Library API
$url = "https://openlibrary.org/search.json?q=" . urlencode($search);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $http_code !== 200) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch from Open Library',
        'details' => 'Service temporarily unavailable. Please try again later.'
    ]);
    exit;
}

echo $response;
?> 