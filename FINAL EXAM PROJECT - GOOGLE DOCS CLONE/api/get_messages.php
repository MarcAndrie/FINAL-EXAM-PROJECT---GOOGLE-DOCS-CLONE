<?php
require_once '../config.php';
requireLogin();
checkSuspension();

header('Content-Type: application/json');

if (!isset($_GET['document_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit();
}

$document_id = intval($_GET['document_id']);
$user_id = $_SESSION['user_id'];

try {
    // Check access
    if (!hasDocumentAccess($pdo, $user_id, $document_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT dm.*, u.username 
        FROM document_messages dm
        JOIN users u ON dm.user_id = u.id
        WHERE dm.document_id = ?
        ORDER BY dm.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$document_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>