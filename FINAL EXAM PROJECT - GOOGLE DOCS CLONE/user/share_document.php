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

// Check if user can share this document
if (!canEditDocument($pdo, $user_id, $doc_id)) {
    die('You do not have permission to share this document.');
}

// Get document info
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Document not found.');
}

// Get current permissions
$stmt = $pdo->prepare("SELECT dp.*, u.username, u.email FROM document_permissions dp JOIN users u ON dp.user_id = u.id WHERE dp.document_id = ?");
$stmt->execute([$doc_id]);
$permissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Share Document - Google Docs Clone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/editor.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 Share Document</h1>
        </div>
        <div class="nav-user">
            <a href="index.php" class="btn btn-sm btn-outline">Back to My Documents</a>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    <div class="editor-container" style="max-width:600px;">
        <h2><?php echo htmlspecialchars($doc['title']); ?></h2>
        <p class="mb-2">Share this document with other users:</p>

        <!-- User Search -->
        <div class="form-group">
            <label for="userSearch">Find user to share with:</label>
            <input type="text" id="userSearch" class="editor-title" placeholder="Type username or email...">
            <div id="userSearchResults" class="search-results"></div>
        </div>
        <div class="form-group">
            <label for="permissionType">Permission:</label>
            <select id="permissionType" class="editor-title" style="max-width:200px;">
                <option value="read">Read Only</option>
                <option value="write">Can Edit</option>
            </select>
        </div>
        <button class="btn btn-primary" id="addPermissionBtn" type="button">Add Permission</button>
        <div id="shareMessage" style="margin-top:15px;"></div>
        <hr style="margin:30px 0;">

        <!-- Current Permissions -->
        <h3>Already Shared With:</h3>
        <table style="width:100%; margin-bottom:15px;">
            <tr>
                <th align="left">User</th>
                <th align="left">Email</th>
                <th align="left">Permission</th>
            </tr>
            <?php if (empty($permissions)): ?>
                <tr><td colspan="3" style="color:#888;">No users yet.</td></tr>
            <?php else: foreach ($permissions as $perm): ?>
                <tr>
                    <td><?php echo htmlspecialchars($perm['username']); ?></td>
                    <td><?php echo htmlspecialchars($perm['email']); ?></td>
                    <td><?php echo ucfirst($perm['permission_type']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </table>
        <a href="edit_document.php?id=<?php echo $doc_id; ?>" class="btn btn-secondary">Back to Document</a>
    </div>
    <script>
    // Basic user search & share (needs js/user-search.js in production)
    const userSearch = document.getElementById('userSearch');
    const userSearchResults = document.getElementById('userSearchResults');
    let selectedUserId = null;

    userSearch.addEventListener('input', function() {
        const q = userSearch.value.trim();
        userSearchResults.innerHTML = '';
        selectedUserId = null;
        if (q.length < 2) return;
        fetch(`../api/search_users.php?q=${encodeURIComponent(q)}&document_id=<?php echo $doc_id; ?>`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.users.length) {
                    userSearchResults.innerHTML = data.users.map(u =>
                        `<div class="user-result" data-id="${u.id}" style="padding:7px;cursor:pointer;">${u.username} &lt;${u.email}&gt;</div>`
                    ).join('');
                } else {
                    userSearchResults.innerHTML = '<div style="color:#888;padding:7px;">No users found</div>';
                }
            });
    });

    userSearchResults.addEventListener('click', function(e) {
        if (e.target.classList.contains('user-result')) {
            selectedUserId = e.target.getAttribute('data-id');
            userSearch.value = e.target.textContent;
            userSearchResults.innerHTML = '';
        }
    });

    document.getElementById('addPermissionBtn').onclick = function() {
        const permissionType = document.getElementById('permissionType').value;
        if (!selectedUserId) {
            document.getElementById('shareMessage').innerHTML = '<div class="alert alert-error">Please select a user.</div>';
            return;
        }
        fetch('../api/add_permission.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                document_id: <?php echo $doc_id; ?>,
                user_id: selectedUserId,
                permission_type: permissionType
            })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('shareMessage').innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-error'}">${data.message}</div>`;
            if (data.success) setTimeout(() => window.location.reload(), 1200);
        });
    }
    </script>
</body>
</html>