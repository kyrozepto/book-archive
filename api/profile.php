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
        // Get user profile
        $stmt = $db->prepare("SELECT id, username, created_at FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
        
        echo json_encode($profile);
        break;
        
    case 'PUT':
        // Update user profile
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided']);
            exit;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
        }
        
        if (isset($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updates)) {
            $params[] = $user['id'];
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 