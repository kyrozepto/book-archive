<?php
session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$db = new Database();

if (!isset($_SESSION['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$api_key = $_SESSION['api_key'];
$user = $auth->getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';

if (empty($query)) {
    echo json_encode(['items' => []]);
    exit;
}

try {
    $searchQuery = '%' . $query . '%';
    $items = [];

    if ($type === 'all' || $type === 'book') {
        $stmt = $db->prepare("
            SELECT id, title, 'book' as type 
            FROM items 
            WHERE type = 'book' AND (title LIKE ? OR id LIKE ?)
            LIMIT 5
        ");
        $stmt->execute([$searchQuery, $searchQuery]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($books as &$book) {
            $book['mention'] = '@book-' . $book['id'];
        }
        $items = array_merge($items, $books);
    }

    if ($type === 'all' || $type === 'journal') {
        $stmt = $db->prepare("
            SELECT id, title, 'journal' as type 
            FROM items 
            WHERE type = 'journal' AND (title LIKE ? OR id LIKE ?)
            LIMIT 5
        ");
        $stmt->execute([$searchQuery, $searchQuery]);
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($journals as &$journal) {
            $journal['mention'] = '@journal-' . $journal['id'];
        }
        $items = array_merge($items, $journals);
    }

    echo json_encode(['items' => $items]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 