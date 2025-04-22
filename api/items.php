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
        $collection_id = $_GET['collection_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        
        $sql = "SELECT i.*, GROUP_CONCAT(t.name) as tags 
                FROM items i 
                LEFT JOIN item_tags it ON i.id = it.item_id 
                LEFT JOIN tags t ON it.tag_id = t.id 
                WHERE i.user_id = ?";
        $params = [$user['id']];
        
        if ($collection_id) {
            $sql .= " AND i.collection_id = ?";
            $params[] = $collection_id;
        }
        
        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        
        if ($type) {
            $sql .= " AND i.type = ?";
            $params[] = $type;
        }
        
        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";
        
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
                user_id, collection_id, type, title, author, description, 
                cover_url, status, rating, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user['id'],
                $data['collection_id'] ?? null,
                $data['type'],
                $data['title'],
                $data['author'] ?? null,
                $data['description'] ?? null,
                $data['cover_url'] ?? null,
                $data['status'] ?? 'wishlist',
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
                collection_id = ?, title = ?, author = ?, description = ?, 
                cover_url = ?, status = ?, rating = ?, notes = ?
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([
                $data['collection_id'] ?? null,
                $data['title'] ?? null,
                $data['author'] ?? null,
                $data['description'] ?? null,
                $data['cover_url'] ?? null,
                $data['status'] ?? null,
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