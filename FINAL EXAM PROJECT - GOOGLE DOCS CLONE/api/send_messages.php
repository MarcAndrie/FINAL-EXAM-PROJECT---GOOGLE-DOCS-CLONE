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

if (!isset($input['document_id']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$document_id = intval($input['document_id']);
$message = trim($input['message']);
$user_id = $_SESSION['user_id'];

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit();
}

try {
    // Check access
    if (!hasDocumentAccess($pdo, $user_id, $document_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Save message
    $stmt = $pdo->prepare("INSERT INTO document_messages (document_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$document_id, $user_id, $message]);

    // Log activity
    logActivity($pdo, $document_id, $user_id, 'edit', 'Sent a message in chat');

    echo json_encode(['success' => true, 'message' => 'Message sent']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>