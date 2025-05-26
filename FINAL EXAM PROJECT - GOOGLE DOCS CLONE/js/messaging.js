// Messaging/chat for document (for messages.php)

document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatStatus = document.getElementById('chatStatus');
    if (!chatForm || !chatMessages) return;
    const docId = document.getElementById('document_id') ? document.getElementById('document_id').value : (window.DOCUMENT_ID || null);

    function loadMessages() {
        fetch('../api/get_messages.php?document_id=' + docId)
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
                    chatMessages.innerHTML = msgs || '<div style="color:#888;">No messages yet.</div>';
                    chatMessages.scrollTop = 99999;
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

    chatForm.onsubmit = function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;
        fetch('../api/send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({document_id: docId, message: msg})
        })
        .then(r => r.json())
        .then(data => {
            chatStatus.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-error'}">${data.message}</div>`;
            if (data.success) {
                chatInput.value = '';
                loadMessages();
            }
        });
    };
    // Auto refresh every 10s
    setInterval(loadMessages, 10000);
    loadMessages();
});