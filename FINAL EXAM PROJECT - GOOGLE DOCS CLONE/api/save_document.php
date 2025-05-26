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

if (!isset($input['document_id']) || !isset($input['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$document_id = intval($input['document_id']);
$content = $input['content'];
$user_id = $_SESSION['user_id'];

try {
    // Check permission
    if (!canEditDocument($pdo, $user_id, $document_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No write permission']);
        exit();
    }

    // Update document content
    $stmt = $pdo->prepare("UPDATE documents SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$content, $document_id]);

    // Log activity
    logActivity($pdo, $document_id, $user_id, 'edit', 'Auto-saved document content');

    echo json_encode(['success' => true, 'message' => 'Document saved']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>