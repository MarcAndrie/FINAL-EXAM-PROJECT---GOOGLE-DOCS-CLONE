<?php
require_once '../config.php';
requireLogin();
checkSuspension();

// Only admins can suspend users
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['suspend'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = intval($input['user_id']);
$suspend = (bool)$input['suspend'];

try {
    // Check if user exists and is not an admin
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    if ($user['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot suspend admin users']);
        exit();
    }
    
    // Update suspension status
    $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
    $stmt->execute([$suspend ? 1 : 0, $user_id]);
    
    $action = $suspend ? 'suspended' : 'activated';
    $message = "User {$user['username']} has been {$action} successfully";
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'user_id' => $user_id,
        'suspended' => $suspend
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>