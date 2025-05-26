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

if (!hasDocumentAccess($pdo, $user_id, $doc_id)) {
    die('You do not have access to this document.');
}

// Get document info
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Document not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Chat - Google Docs Clone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/editor.css">
    <style>
    .chat-container {max-width:600px;margin:40px auto;background:#fff;padding:32px 18px;border-radius:14px;box-shadow:0 2px 16px rgba(0,0,0,0.07);}
    .chat-messages {min-height:180px;max-height:300px;overflow-y:auto;margin-bottom:18px;}
    .chat-msg {margin-bottom:12px;}
    .chat-msg-user {font-weight:bold;color:#667eea;}
    .chat-msg-time {color:#888;font-size:0.8rem;margin-left:8px;}
    .chat-msg-content {margin-top:2px;}
    .chat-input-row {display:flex;gap:12px;}
    .chat-input-row textarea {flex:1;border-radius:8px;padding:10px;font-size:1rem;}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 Document Chat</h1>
        </div>
        <div class="nav-user">
            <a href="edit_document.php?id=<?php echo $doc_id; ?>" class="btn btn-sm btn-outline">Back to Document</a>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    <div class="chat-container">
        <h2><?php echo htmlspecialchars($doc['title']); ?></h2>
        <div class="chat-messages" id="chatMessages"></div>
        <form id="chatForm" autocomplete="off">
            <div class="chat-input-row">
                <textarea id="chatInput" rows="2" placeholder="Type your message..."></textarea>
                <button type="submit" class="btn btn-primary">Send</button>
            </div>
        </form>
        <div id="chatStatus"></div>
    </div>
    <script>
    function loadMessages() {
        fetch('../api/get_messages.php?document_id=<?php echo $doc_id; ?>')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const msgs = data.messages.map(m =>
                        `<div class="chat-msg">
                            <span class="chat-msg-user">${m.username}</span>
                            <span class="chat-msg-time">${formatTimeAgo(m.created_at)}</span>
                            <div class="chat-msg-content">${escapeHtml(m.message)}</div>
                        </div>`
                    ).join('');
                    document.getElementById('chatMessages').innerHTML = msgs || '<div style="color:#888;">No messages yet.</div>';
                    document.getElementById('chatMessages').scrollTop = 99999;
                }
            });
    }
    function formatTimeAgo(ts) {
        const now = new Date(), time = new Date(ts), diff = Math.floor((now - time) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return Math.floor(diff / 86400) + ' days ago';
    }
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
    document.getElementById('chatForm').onsubmit = function(e) {
        e.preventDefault();
        const msg = document.getElementById('chatInput').value.trim();
        if (!msg) return;
        fetch('../api/send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({document_id: <?php echo $doc_id; ?>, message: msg})
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('chatStatus').innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-error'}">${data.message}</div>`;
            if (data.success) {
                document.getElementById('chatInput').value = '';
                loadMessages();
            }
        });
    };
    // Auto refresh every 10s
    setInterval(loadMessages, 10000);
    loadMessages();
    </script>
</body>
</html>