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
        $stmt = $db->prepare("
            SELECT id, content, created_at 
            FROM notes 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notes as &$note) {
            $note['content'] = processMentions($note['content']);
        }
        
        echo json_encode($notes);
        break;
        
    case 'POST':
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
        
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['note_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID is required']);
            exit;
        }

        $note_id = $data['note_id'];
        
        $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found or unauthorized']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['note_id']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID and content are required']);
            exit;
        }

        $note_id = $data['note_id'];
        $content = $data['content'];
        
        $stmt = $db->prepare("UPDATE notes SET content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $note_id, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found or unauthorized']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function processMentions($content) {
    $content = preg_replace_callback(
        '/@(book|journal)-([a-zA-Z0-9\.]+)/',
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