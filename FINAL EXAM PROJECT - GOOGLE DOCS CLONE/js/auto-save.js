// Auto-save: save every 15 seconds if content changed

document.addEventListener('DOMContentLoaded', function () {
    const docForm = document.getElementById('editorForm');
    if (!docForm) return; // Not on editor page

    const docId = document.getElementById('document_id').value;
    const docContent = document.getElementById('docContent');
    const statusSpan = document.getElementById('editorStatus');
    let lastValue = docContent.value;

    setInterval(() => {
        if (docContent.value !== lastValue) {
            autoSaveDocument();
            lastValue = docContent.value;
        }
    }, 15000); // 15 seconds

    function autoSaveDocument() {
        statusSpan.textContent = 'Auto-saving...';
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
                statusSpan.textContent = 'Auto-saved!';
                statusSpan.style.color = '#28a745';
            } else {
                statusSpan.textContent = 'Auto-save failed!';
                statusSpan.style.color = '#d32f2f';
            }
            setTimeout(() => {
                statusSpan.textContent = '';
                statusSpan.style.color = '';
            }, 1200);
        })
        .catch(() => {
            statusSpan.textContent = 'Error!';
            statusSpan.style.color = '#d32f2f';
            setTimeout(() => {
                statusSpan.textContent = '';
                statusSpan.style.color = '';
            }, 1200);
        });
    }
});