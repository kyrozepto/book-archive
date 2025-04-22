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

// Search arXiv API
$url = "http://export.arxiv.org/api/query?search_query=all:" . urlencode($search) . "&start=0&max_results=10";
$response = file_get_contents($url);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch from arXiv']);
    exit;
}

// Parse arXiv XML response
$xml = simplexml_load_string($response);
$papers = [];

foreach ($xml->entry as $entry) {
    $papers[] = [
        'id' => (string)$entry->id,
        'title' => (string)$entry->title,
        'summary' => (string)$entry->summary,
        'authors' => array_map(function($author) {
            return (string)$author->name;
        }, (array)$entry->author),
        'published' => (string)$entry->published,
        'pdf_url' => (string)$entry->link[0]['href']
    ];
}

echo json_encode($papers); 