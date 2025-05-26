<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'google_docs_clone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSuspended() {
    return isset($_SESSION['is_suspended']) && $_SESSION['is_suspended'] == 1;
}

function checkSuspension() {
    if (isSuspended()) {
        session_destroy();
        header('Location: ../login.php?error=suspended');
        exit();
    }
}

function logActivity($pdo, $document_id, $user_id, $action_type, $action_details = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action_type, action_details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$document_id, $user_id, $action_type, $action_details]);
    } catch(PDOException $e) {
        // Log error but don't break the application
        error_log("Activity log error: " . $e->getMessage());
    }
}

function hasDocumentAccess($pdo, $user_id, $document_id) {
    // Check if user is the author
    $stmt = $pdo->prepare("SELECT author_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if ($document && $document['author_id'] == $user_id) {
        return true;
    }
    
    // Check if user has permission
    $stmt = $pdo->prepare("SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $user_id]);
    
    return $stmt->fetch() !== false;
}

function canEditDocument($pdo, $user_id, $document_id) {
    // Check if user is the author
    $stmt = $pdo->prepare("SELECT author_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if ($document && $document['author_id'] == $user_id) {
        return true;
    }
    
    // Check if user has write permission
    $stmt = $pdo->prepare("SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ? AND permission_type = 'write'");
    $stmt->execute([$document_id, $user_id]);
    
    return $stmt->fetch() !== false;
}

function formatTimeAgo($timestamp) {
    $time = time() - strtotime($timestamp);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($timestamp));
}

// Set timezone
date_default_timezone_set('UTC');
?>