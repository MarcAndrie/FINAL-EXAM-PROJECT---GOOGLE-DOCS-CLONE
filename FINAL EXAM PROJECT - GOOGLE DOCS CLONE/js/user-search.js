// Real-time user search and share (for share_document.php)

document.addEventListener('DOMContentLoaded', function () {
    const userSearch = document.getElementById('userSearch');
    const userSearchResults = document.getElementById('userSearchResults');
    let selectedUserId = null;
    if (!userSearch) return;

    userSearch.addEventListener('input', function() {
        const q = userSearch.value.trim();
        userSearchResults.innerHTML = '';
        selectedUserId = null;
        if (q.length < 2) return;
        const docId = document.getElementById('document_id').value;
        fetch(`../api/search_users.php?q=${encodeURIComponent(q)}&document_id=${docId}`)
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

    // Expose for use in share logic
    window.getSelectedUserId = () => selectedUserId;
});