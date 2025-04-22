<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

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

$method = $_SERVER['REQUEST_METHOD'];
$user = $auth->getCurrentUser();

switch ($method) {
    case 'GET':
        // Get all collections for the user
        $stmt = $db->prepare("SELECT * FROM collections WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $collections = $stmt->fetchAll();
        
        echo json_encode($collections);
        break;
        
    case 'POST':
        // Create new collection
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Collection name required']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO collections (user_id, name, description) VALUES (?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $data['name'],
            $data['description'] ?? null
        ]);
        
        $collection_id = $db->lastInsertId();
        echo json_encode(['id' => $collection_id]);
        break;
        
    case 'PUT':
        // Update collection
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Collection ID and name required']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE collections SET name = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['id'],
            $user['id']
        ]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'DELETE':
        // Delete collection
        $collection_id = $_GET['id'] ?? null;
        
        if (!$collection_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Collection ID required']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM collections WHERE id = ? AND user_id = ?");
        $stmt->execute([$collection_id, $user['id']]);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 