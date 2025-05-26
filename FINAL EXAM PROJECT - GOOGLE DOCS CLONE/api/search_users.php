<?php
require_once '../config.php';
requireLogin();
checkSuspension();

header('Content-Type: application/json');

if (!isset($_GET['q']) || !isset($_GET['document_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$search = trim($_GET['q']);
$document_id = intval($_GET['document_id']);
$user_id = $_SESSION['user_id'];

try {
    // Find users not already shared and not self, not suspended
    $stmt = $pdo->prepare("
        SELECT id, username, email
        FROM users
        WHERE (username LIKE ? OR email LIKE ?)
          AND is_suspended = 0
          AND id != ?
          AND id NOT IN (
              SELECT user_id FROM document_permissions WHERE document_id = ?
          )
        LIMIT 10
    ");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $user_id, $document_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>