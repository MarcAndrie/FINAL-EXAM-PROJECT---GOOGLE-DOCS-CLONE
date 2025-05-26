<?php
require_once '../config.php';
requireLogin();
checkSuspension();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '') {
        $error = "Title is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO documents (title, content, author_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $_SESSION['user_id']]);
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Document - Google Docs Clone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/editor.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 New Document</h1>
        </div>
        <div class="nav-user">
            <a href="index.php" class="btn btn-sm btn-outline">Back to My Documents</a>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    <div class="editor-container">
        <form method="post" autocomplete="off">
            <div class="editor-header">
                <input type="text" name="title" class="editor-title" maxlength="100" required placeholder="Document Title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
            </div>
            <div class="editor-area-wrapper">
                <textarea name="content" class="editor-area" rows="18" placeholder="Start writing..."><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="editor-actions">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>