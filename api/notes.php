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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all notes for the user
        $stmt = $db->prepare("
            SELECT id, content, created_at 
            FROM notes 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process mentions in notes
        foreach ($notes as &$note) {
            $note['content'] = processMentions($note['content']);
        }
        
        echo json_encode($notes);
        break;
        
    case 'POST':
        // Create new note
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Content is required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO notes (user_id, content) 
                VALUES (?, ?)
            ");
            $stmt->execute([$user['id'], $data['content']]);
            
            $noteId = $db->lastInsertId();
            
            // Process mentions and create relationships
            processMentionsAndCreateRelationships($data['content'], $noteId);
            
            http_response_code(201);
            echo json_encode([
                'message' => 'Note created successfully',
                'id' => $noteId
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create note']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Process mentions in note content
function processMentions($content) {
    // Convert @book-id and @journal-id mentions to styled spans with links
    $content = preg_replace_callback(
        '/@(book|journal)-(\d+)/',
        function($matches) {
            $type = $matches[1];
            $id = $matches[2];
            $url = $type === 'book' ? "book-details.php?id=$id" : "journal-details.php?id=$id";
            return '<a href="' . $url . '" class="mention mention-' . $type . '">@' . $type . '-' . $id . '</a>';
        },
        $content
    );
    return $content;
}

// Process mentions and create relationships
function processMentionsAndCreateRelationships($content, $noteId) {
    global $db;
    
    // Find all @book-id and @journal-id mentions in the content
    preg_match_all('/@(book|journal)-(\d+)/', $content, $matches, PREG_SET_ORDER);
    
    if (!empty($matches)) {
        foreach ($matches as $match) {
            $type = $match[1];
            $id = $match[2];
            
            // Verify the item exists
            $stmt = $db->prepare("
                SELECT id 
                FROM items 
                WHERE id = ? AND type = ?
                LIMIT 1
            ");
            $stmt->execute([$id, $type]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                // Create relationship between note and item
                $stmt = $db->prepare("
                    INSERT INTO note_items (note_id, item_id, item_type) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$noteId, $id, $type]);
            }
        }
    }
} 