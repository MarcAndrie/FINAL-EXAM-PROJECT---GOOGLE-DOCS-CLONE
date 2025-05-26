<?php
require_once '../config.php';
requireLogin();
checkSuspension();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['document_id']) || !isset($input['user_id']) || !isset($input['permission_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$document_id = intval($input['document_id']);
$target_user_id = intval($input['user_id']);
$permission_type = $input['permission_type'] === 'write' ? 'write' : 'read';
$user_id = $_SESSION['user_id'];

try {
    // Only author or someone with write permission can share
    if (!canEditDocument($pdo, $user_id, $document_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission to share']);
        exit();
    }

    // Prevent sharing to self
    if ($target_user_id === $user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot share to yourself']);
        exit();
    }

    // Check if user already has permission
    $stmt = $pdo->prepare("SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $target_user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User already has permission']);
        exit();
    }

    // Add permission
    $stmt = $pdo->prepare("INSERT INTO document_permissions (document_id, user_id, permission_type, granted_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$document_id, $target_user_id, $permission_type, $user_id]);

    // Log activity
    logActivity($pdo, $document_id, $user_id, 'share', "Shared with user $target_user_id as $permission_type");

    echo json_encode(['success' => true, 'message' => 'Permission granted']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>