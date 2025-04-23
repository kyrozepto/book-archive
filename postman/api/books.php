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

// Handle GET request for searching books
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $url = "https://openlibrary.org/search.json?q=" . urlencode($search);
        
        $response = file_get_contents($url);
        if ($response === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch books from Open Library']);
            exit;
        }
        
        echo $response;
    } elseif (isset($_GET['id'])) {
        $id = $_GET['id'];
        $url = "https://openlibrary.org/works/" . $id . ".json";
        
        $response = file_get_contents($url);
        if ($response === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch book details from Open Library']);
            exit;
        }
        
        echo $response;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Search query or book ID is required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} 