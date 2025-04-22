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
        // Get all items for the user
        $type = $_GET['type'] ?? null;
        
        $sql = "SELECT * FROM items WHERE user_id = ?";
        $params = [$user['id']];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Convert tags string to array
        foreach ($items as &$item) {
            $item['tags'] = $item['tags'] ? explode(',', $item['tags']) : [];
        }
        
        echo json_encode($items);
        break;
        
    case 'POST':
        // Add new item
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['type']) || !isset($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Item type and title required']);
            exit;
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert item
            $stmt = $db->prepare("INSERT INTO items (
                user_id, type, title, author, description,
                cover_url, rating, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user['id'],
                $data['type'],
                $data['title'],
                $data['author'] ?? null,
                $data['description'] ?? null,
                $data['cover_url'] ?? null,
                $data['rating'] ?? null,
                $data['notes'] ?? null
            ]);
            
            $item_id = $db->lastInsertId();
            
            // Add tags if provided
            if (isset($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tag_name) {
                    // Insert tag if it doesn't exist
                    $stmt = $db->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                    $stmt->execute([$tag_name]);
                    
                    // Get tag ID
                    $stmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag_id = $stmt->fetchColumn();
                    
                    // Link tag to item
                    $stmt = $db->prepare("INSERT INTO item_tags (item_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$item_id, $tag_id]);
                }
            }
            
            $db->commit();
            echo json_encode(['id' => $item_id]);
            
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add item']);
        }
        break;
        
    case 'PUT':
        // Update item
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID required']);
            exit;
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Update item
            $stmt = $db->prepare("UPDATE items SET 
                type = ?, title = ?, author = ?, description = ?,
                cover_url = ?, rating = ?, notes = ?
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([
                $data['type'],
                $data['title'],
                $data['author'],
                $data['description'],
                $data['cover_url'],
                $data['rating'] ?? null,
                $data['notes'] ?? null,
                $data['id'],
                $user['id']
            ]);
            
            // Update tags if provided
            if (isset($data['tags']) && is_array($data['tags'])) {
                // Remove existing tags
                $stmt = $db->prepare("DELETE FROM item_tags WHERE item_id = ?");
                $stmt->execute([$data['id']]);
                
                // Add new tags
                foreach ($data['tags'] as $tag_name) {
                    // Insert tag if it doesn't exist
                    $stmt = $db->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                    $stmt->execute([$tag_name]);
                    
                    // Get tag ID
                    $stmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag_id = $stmt->fetchColumn();
                    
                    // Link tag to item
                    $stmt = $db->prepare("INSERT INTO item_tags (item_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$data['id'], $tag_id]);
                }
            }
            
            $db->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update item']);
        }
        break;
        
    case 'DELETE':
        // Delete item
        $item_id = $_GET['id'] ?? null;
        
        if (!$item_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID required']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user['id']]);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 