<?php
require_once '../config.php';
requireLogin();
checkSuspension();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$doc_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check permission
$is_author = false;
$can_edit = false;

// Get document and permission
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Document not found.');
}

$is_author = ($doc['author_id'] == $user_id);
$can_edit = $is_author || canEditDocument($pdo, $user_id, $doc_id);

if (!$can_edit && !hasDocumentAccess($pdo, $user_id, $doc_id)) {
    die('You do not have access to this document.');
}

// Read only mode?
$readonly = isset($_GET['readonly']) && !$can_edit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Document - Google Docs Clone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/editor.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 Edit Document</h1>
        </div>
        <div class="nav-user">
            <a href="index.php" class="btn btn-sm btn-outline">Back to My Documents</a>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    <div class="editor-container">
        <form id="editorForm" autocomplete="off">
            <input type="hidden" name="document_id" id="document_id" value="<?php echo $doc_id; ?>">
            <div class="editor-header">
                <input type="text" id="docTitle" name="title"
                    value="<?php echo htmlspecialchars($doc['title']); ?>"
                    <?php echo $readonly ? 'readonly' : ''; ?>
                    class="editor-title"
                    maxlength="100"
                    required
                    >
                <span class="editor-status" id="editorStatus"></span>
            </div>
            <div class="editor-toolbar">
                <!-- For future: add formatting buttons here -->
            </div>
            <div class="editor-area-wrapper">
                <textarea id="docContent" name="content" class="editor-area" rows="20"
                    <?php echo $readonly ? 'readonly' : ''; ?>
                ><?php echo htmlspecialchars($doc['content']); ?></textarea>
            </div>
            <div class="editor-actions">
                <?php if (!$readonly): ?>
                <button type="button" id="saveBtn" class="btn btn-primary">Save</button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
    <script src="../js/editor.js"></script>
</body>
</html>