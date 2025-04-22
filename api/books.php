<?php
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check API key
if (!isset($_SERVER['HTTP_X_API_KEY'])) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required']);
    exit;
}

$api_key = $_SERVER['HTTP_X_API_KEY'];
if (!$auth->validateApiKey($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Get search query
$search = $_GET['search'] ?? '';
if (empty($search)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query required']);
    exit;
}

// Search Open Library API
$url = "https://openlibrary.org/search.json?q=" . urlencode($search);
$response = file_get_contents($url);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch from Open Library']);
    exit;
}

echo $response;
?> 