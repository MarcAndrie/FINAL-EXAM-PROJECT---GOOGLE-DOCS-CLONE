<?php
require_once '../config.php';
requireLogin();
checkSuspension();

if (!isAdmin()) {
    header('Location: ../user/index.php');
    exit();
}

// Get all documents with author information
$stmt = $pdo->prepare("
    SELECT d.*, u.username as author_name, u.email as author_email,
           COUNT(dp.id) as shared_count
    FROM documents d 
    JOIN users u ON d.author_id = u.id 
    LEFT JOIN document_permissions dp ON d.id = dp.document_id
    GROUP BY d.id
    ORDER BY d.updated_at DESC
");
$stmt->execute();
$documents = $stmt->fetchAll();

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$stmt->execute();
$user_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_documents FROM documents");
$stmt->execute();
$doc_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as suspended_users FROM users WHERE is_suspended = 1");
$stmt->execute();
$suspended_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Google Docs Clone</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 Admin Dashboard</h1>
        </div>
        <div class="nav-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="manage_users.php" class="btn btn-sm btn-secondary">Manage Users</a>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $user_stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-info">
                    <h3><?php echo $doc_stats['total_documents']; ?></h3>
                    <p>Total Documents</p>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">🚫</div>
                <div class="stat-info">
                    <h3><?php echo $suspended_stats['suspended_users']; ?></h3>
                    <p>Suspended Users</p>
                </div>
            </div>
        </div>
        
        <!-- Documents Section -->
        <div class="section">
            <div class="section-header">
                <h2>All Documents</h2>
                <p>View and monitor all documents in the system</p>
            </div>
            
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No Documents Found</h3>
                    <p>No documents have been created yet.</p>
                </div>
            <?php else: ?>
                <div class="documents-grid">
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                                <div class="document-meta">
                                    <span class="author">by <?php echo htmlspecialchars($doc['author_name']); ?></span>
                                    <span class="date"><?php echo formatTimeAgo($doc['updated_at']); ?></span>
                                </div>
                            </div>
                            
                            <div class="document-stats">
                                <div class="stat">
                                    <span class="stat-label">Shared with:</span>
                                    <span class="stat-value"><?php echo $doc['shared_count']; ?> user(s)</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Created:</span>
                                    <span class="stat-value"><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="document-actions">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" 
                                   class="btn btn-sm btn-primary">View Document</a>
                                <button onclick="showActivityLogs(<?php echo $doc['id']; ?>)" 
                                        class="btn btn-sm btn-secondary">Activity Logs</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Activity Logs Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Document Activity Logs</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="activityLogs">Loading...</div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function showActivityLogs(documentId) {
            document.getElementById('activityModal').style.display = 'block';
            
            fetch(`../api/get_activity_logs.php?document_id=${documentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivityLogs(data.logs);
                    }
                </script>
</body>
</html> else {
                        document.getElementById('activityLogs').innerHTML = 
                            '<p class="error">Failed to load activity logs.</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('activityLogs').innerHTML = 
                        '<p class="error">Error loading activity logs.</p>';
                });
        }
        
        function displayActivityLogs(logs) {
            const container = document.getElementById('activityLogs');
            
            if (logs.length === 0) {
                container.innerHTML = '<p>No activity logs found.</p>';
                return;
            }
            
            let html = '<div class="activity-logs">';
            logs.forEach(log => {
                html += `
                    <div class="activity-item">
                        <div class="activity-user">${log.username}</div>
                        <div class="activity-action">${log.action_type}</div>
                        <div class="activity-details">${log.action_details || ''}</div>
                        <div class="activity-time">${formatTimeAgo(log.timestamp)}</div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        function closeModal() {
            document.getElementById('activityModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('activityModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
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