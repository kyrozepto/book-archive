<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields',
        'details' => 'Username and password are required'
    ]);
    exit;
}

// Initialize Auth               
$auth = new Auth();

// Attempt login
$api_key = $auth->login($data['username'], $data['password']);

if ($api_key) {
    // Start session and store API key
    session_start();
    $_SESSION['api_key'] = $api_key;
    
    // Login successful
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'api_key' => $api_key,
        'redirect' => 'dashboard.php'
    ]);
} else {
    // Login failed
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed',
        'details' => 'Invalid username or password'
    ]);
} 