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
    // Check if user has access to this document (or is admin)
    if (!isAdmin() && !hasDocumentAccess($pdo, $user_id, $document_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Get activity logs for the document
    $stmt = $pdo->prepare("
        SELECT al.*, u.username, u.email
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        WHERE al.document_id = ?
        ORDER BY al.timestamp DESC
        LIMIT 50
    ");
    $stmt->execute([$document_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>