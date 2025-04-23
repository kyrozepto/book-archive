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

// Get user ID from API key
$stmt = $db->prepare("SELECT id FROM users WHERE api_key = ?");
$stmt->execute([$api_key]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$user_id = $user['id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all notes for the user
        $stmt = $db->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($notes);
        break;
        
    case 'POST':
        // Create a new note
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Content is required']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO notes (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $data['content']]);
        
        $noteId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Note created successfully',
            'id' => $noteId
        ]);
        break;
        
    case 'PUT':
        // Update a note
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID and content are required']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE notes SET content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['content'], $data['id'], $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found']);
        }
        break;
        
    case 'DELETE':
        // Delete a note
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID is required']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 