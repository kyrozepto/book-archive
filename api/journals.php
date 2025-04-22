<?php
session_start();
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

if (!isset($_SESSION['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $start = $_GET['start'] ?? 0;
    $max_results = $_GET['max_results'] ?? 20;

    if (empty($search)) {
        http_response_code(400);
        echo json_encode(['error' => 'Search term is required']);
        exit;
    }

    $url = "https://export.arxiv.org/api/query?search_query=all:" . urlencode($search) . "&start=" . $start . "&max_results=" . $max_results;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode(['error' => 'Failed to fetch from arXiv API']);
        exit;
    }

    // Parse the XML response
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse arXiv response']);
        exit;
    }

    $papers = [];
    foreach ($xml->entry as $entry) {
        $id = (string)$entry->id;
        $id = substr($id, strrpos($id, '/') + 1);
        
        $authors = [];
        foreach ($entry->author as $author) {
            $authors[] = (string)$author->name;
        }

        $pdfLink = '';
        foreach ($entry->link as $link) {
            if ((string)$link['title'] === 'pdf') {
                $pdfLink = (string)$link['href'];
                break;
            }
        }

        $papers[] = [
            'id' => $id,
            'title' => (string)$entry->title,
            'authors' => $authors,
            'summary' => (string)$entry->summary,
            'pdfLink' => $pdfLink,
            'published' => (string)$entry->published,
            'updated' => (string)$entry->updated
        ];
    }

    echo json_encode([
        'total' => (int)$xml->totalResults,
        'papers' => $papers
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} 