<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

$auth = new Auth();
$db = new Database();

// Check API key
if (!isset($_SERVER['HTTP_X_API_KEY'])) {
    http_response_code(401);
    echo json_encode(['error' => 'API key is required']);
    exit;
}

$api_key = $_SERVER['HTTP_X_API_KEY'];

// Validate API key directly
if (!$auth->validateApiKey($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Handle GET request for searching journals
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $max_results = isset($_GET['max_results']) ? $_GET['max_results'] : 10;
        
        $url = "http://export.arxiv.org/api/query?search_query=all:" . urlencode($search) . 
               "&start=" . $start . "&max_results=" . $max_results;
        
        $response = file_get_contents($url);
        if ($response === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch papers from arXiv']);
            exit;
        }
        
        // Convert XML to JSON
        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        echo $json;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Search query is required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} 