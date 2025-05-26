// Document editor logic (for edit_document.php)

document.addEventListener('DOMContentLoaded', function () {
    const saveBtn = document.getElementById('saveBtn');
    const docForm = document.getElementById('editorForm');
    const docId = document.getElementById('document_id').value;
    const statusSpan = document.getElementById('editorStatus');
    const docTitle = document.getElementById('docTitle');
    const docContent = document.getElementById('docContent');

    if (!saveBtn) return; // If read-only, no save

    // Manual Save button
    saveBtn.addEventListener('click', function () {
        saveDocument();
    });

    // Save on Ctrl+S/Command+S
    docForm.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveDocument();
        }
    });

    function saveDocument() {
        statusSpan.textContent = 'Saving...';
        fetch('../api/save_document.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                document_id: docId,
                content: docContent.value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                statusSpan.textContent = 'Saved!';
                statusSpan.style.color = '#28a745';
            } else {
                statusSpan.textContent = 'Save failed!';
                statusSpan.style.color = '#d32f2f';
            }
            setTimeout(() => {
                statusSpan.textContent = '';
                statusSpan.style.color = '';
            }, 1500);
        })
        .catch(() => {
            statusSpan.textContent = 'Error!';
            statusSpan.style.color = '#d32f2f';
            setTimeout(() => {
                statusSpan.textContent = '';
                statusSpan.style.color = '';
            }, 1500);
        });
    }

});