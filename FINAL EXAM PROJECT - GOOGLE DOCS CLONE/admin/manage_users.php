<?php
require_once '../config.php';
requireLogin();
checkSuspension();

if (!isAdmin()) {
    header('Location: ../user/index.php');
    exit();
}

// Get all users with their document counts
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(d.id) as document_count
    FROM users u 
    LEFT JOIN documents d ON u.id = d.author_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 Manage Users</h1>
        </div>
        <div class="nav-user">
            <a href="index.php" class="btn btn-sm btn-secondary">← Back to Dashboard</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="section">
            <div class="section-header">
                <h2>User Management</h2>
                <p>Manage user accounts and their access permissions</p>
            </div>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👤</div>
                    <h3>No Users Found</h3>
                    <p>No user accounts have been created yet.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Documents</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="<?php echo $user['is_suspended'] ? 'suspended-row' : ''; ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                            </div>
                                            <div class="user-details">
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <small>ID: <?php echo $user['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="document-count"><?php echo $user['document_count']; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_suspended'] ? 'suspended' : 'active'; ?>">
                                            <?php echo $user['is_suspended'] ? 'Suspended' : 'Active'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <label class="toggle-switch" title="Toggle suspension status">
                                                <input type="checkbox" 
                                                       <?php echo $user['is_suspended'] ? 'checked' : ''; ?>
                                                       onchange="toggleSuspension(<?php echo $user['id']; ?>, this)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <button onclick="viewUserDocuments(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    class="btn btn-sm btn-secondary">View Documents</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User Documents Modal -->
    <div id="userDocumentsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Documents by <span id="modalUsername"></span></h3>
                <button class="modal-close" onclick="closeUserDocumentsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="userDocuments">Loading...</div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function toggleSuspension(userId, checkbox) {
            const isChecked = checkbox.checked;
            
            // Show confirmation
            if (confirm(`Are you sure you want to ${isChecked ? 'suspend' : 'activate'} this user?`)) {
                fetch('../api/toggle_suspension.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        suspend: isChecked
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the row appearance
                        const row = checkbox.closest('tr');
                        const statusBadge = row.querySelector('.status-badge');
                        
                        if (isChecked) {
                            row.classList.add('suspended-row');
                            statusBadge.textContent = 'Suspended';
                            statusBadge.className = 'status-badge suspended';
                        } else {
                            row.classList.remove('suspended-row');
                            statusBadge.textContent = 'Active';
                            statusBadge.className = 'status-badge active';
                        }
                        
                        showAlert(data.message, 'success');
                    } else {
                        // Revert checkbox state
                        checkbox.checked = !isChecked;
                        showAlert(data.message || 'Failed to update user status', 'error');
                    }
                })
                .catch(error => {
                    // Revert checkbox state
                    checkbox.checked = !isChecked;
                    showAlert('Error updating user status', 'error');
                });
            } else {
                // Revert checkbox state if cancelled
                checkbox.checked = !isChecked;
            }
        }
        
        function viewUserDocuments(userId, username) {
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('userDocumentsModal').style.display = 'block';
            
            fetch(`../api/get_user_documents.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUserDocuments(data.documents);
                    } else {
                        document.getElementById('userDocuments').innerHTML = 
                            '<p class="error">Failed to load user documents.</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('userDocuments').innerHTML = 
                        '<p class="error">Error loading user documents.</p>';
                });
        }
        
        function displayUserDocuments(documents) {
            const container = document.getElementById('userDocuments');
            
            if (documents.length === 0) {
                container.innerHTML = '<p>This user has not created any documents yet.</p>';
                return;
            }
            
            let html = '<div class="user-documents-list">';
            documents.forEach(doc => {
                html += `
                    <div class="document-item">
                        <div class="document-title">${doc.title}</div>
                        <div class="document-meta">
                            <span>Created: ${formatDate(doc.created_at)}</span>
                            <span>Updated: ${formatTimeAgo(doc.updated_at)}</span>
                        </div>
                        <div class="document-actions">
                            <a href="view_document.php?id=${doc.id}" class="btn btn-sm btn-primary">View</a>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        function closeUserDocumentsModal() {
            document.getElementById('userDocumentsModal').style.display = 'none';
        }
        
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} floating-alert`;
            alert.textContent = message;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        
        function formatTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            return Math.floor(diff / 86400) + ' days ago';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userDocumentsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>