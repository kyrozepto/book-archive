<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    $auth = new Auth();
    $db = new Database();
    
    // Get API key from header
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    
    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'API key is required']);
        exit;
    }
    
    // Get user ID from API key
    $stmt = $db->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    $userId = $user['id'];
    
    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            try {
                if (isset($_GET['id'])) {
                    // Get specific collection with items
                    $collectionId = $_GET['id'];
                    $stmt = $db->prepare("
                        SELECT c.*, i.id as item_id, i.title, i.author, i.publication_date, i.cover_url, i.type
                        FROM collections c
                        LEFT JOIN collection_items ci ON c.id = ci.collection_id
                        LEFT JOIN items i ON ci.item_id = i.id
                        WHERE c.id = ? AND c.user_id = ?
                        ORDER BY i.title
                    ");
                    $stmt->execute([$collectionId, $userId]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($items)) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Collection not found']);
                        exit;
                    }
                    
                    // Format the response
                    $collection = [
                        'id' => $items[0]['id'],
                        'name' => $items[0]['name'],
                        'description' => $items[0]['description'],
                        'items' => []
                    ];
                    
                    foreach ($items as $item) {
                        if ($item['item_id']) {
                            $collection['items'][] = [
                                'id' => $item['item_id'],
                                'title' => $item['title'],
                                'author' => $item['author'],
                                'publication_date' => $item['publication_date'],
                                'cover_url' => $item['cover_url'],
                                'type' => $item['type']
                            ];
                        }
                    }
                    
                    echo json_encode($collection);
                } else {
                    // Get all collections
                    $stmt = $db->prepare("
                        SELECT c.*, COUNT(ci.item_id) as item_count
                        FROM collections c
                        LEFT JOIN collection_items ci ON c.id = ci.collection_id
                        WHERE c.user_id = ?
                        GROUP BY c.id
                        ORDER BY c.name
                    ");
                    $stmt->execute([$userId]);
                    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($collections);
                }
            } catch (Exception $e) {
                error_log("Error in GET request: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch collections', 'details' => $e->getMessage()]);
            }
            break;
            
        case 'POST':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['name'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Collection name is required']);
                    exit;
                }
                
                $stmt = $db->prepare("
                    INSERT INTO collections (user_id, name, description)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $data['name'],
                    $data['description'] ?? null
                ]);
                
                $collectionId = $db->lastInsertId();
                echo json_encode([
                    'id' => $collectionId,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null
                ]);
            } catch (Exception $e) {
                error_log("Error in POST request: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create collection', 'details' => $e->getMessage()]);
            }
            break;
            
        case 'DELETE':
            try {
                if (!isset($_GET['id']) || !isset($_GET['item_id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Collection ID and item ID are required']);
                    exit;
                }
                
                $collectionId = $_GET['id'];
                $itemId = $_GET['item_id'];
                
                // Verify collection belongs to user
                $stmt = $db->prepare("SELECT id FROM collections WHERE id = ? AND user_id = ?");
                $stmt->execute([$collectionId, $userId]);
                if (!$stmt->fetch()) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Collection not found']);
                    exit;
                }
                
                $stmt = $db->prepare("
                    DELETE FROM collection_items
                    WHERE collection_id = ? AND item_id = ?
                ");
                $stmt->execute([$collectionId, $itemId]);
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                error_log("Error in DELETE request: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Failed to remove item from collection', 'details' => $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error', 'details' => $e->getMessage()]);
} 