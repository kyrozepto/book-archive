<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/Auth.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields',
        'details' => 'Username and password are required'
    ]);
    exit;
}

$auth = new Auth();

$api_key = $auth->register($data['username'], $data['password']);

if ($api_key) {
    session_start();
    $_SESSION['api_key'] = $api_key;
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'api_key' => $api_key,
        'redirect' => 'dashboard.php'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed',
        'details' => 'Username might be taken'
    ]);
} 