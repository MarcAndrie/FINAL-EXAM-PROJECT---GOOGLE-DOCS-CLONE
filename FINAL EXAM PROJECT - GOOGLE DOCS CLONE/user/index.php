<?php
require_once '../config.php';
requireLogin();
checkSuspension();

if (isAdmin()) {
    header('Location: ../admin/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get documents created by the user
$stmt = $pdo->prepare("
    SELECT d.*, COUNT(dp.id) as shared_count
    FROM documents d 
    LEFT JOIN document_permissions dp ON d.id = dp.document_id
    WHERE d.author_id = ?
    GROUP BY d.id
    ORDER BY d.updated_at DESC
");
$stmt->execute([$user_id]);
$my_documents = $stmt->fetchAll();

// Get documents shared with the user
$stmt = $pdo->prepare("
    SELECT d.*, u.username as author_name, dp.permission_type
    FROM documents d 
    JOIN users u ON d.author_id = u.id
    JOIN document_permissions dp ON d.id = dp.document_id
    WHERE dp.user_id = ?
    ORDER BY d.updated_at DESC
");
$stmt->execute([$user_id]);
$shared_documents = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT al.*, d.title as document_title, u.username
    FROM activity_logs al
    JOIN documents d ON al.document_id = d.id
    JOIN users u ON al.user_id = u.id
    WHERE d.author_id = ? OR EXISTS (
        SELECT 1 FROM document_permissions dp 
        WHERE dp.document_id = d.id AND dp.user_id = ?
    )
    ORDER BY al.timestamp DESC
    LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$recent_activity = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - Google Docs Clone</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/editor.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h1>📝 My Documents</h1>
        </div>
        <div class="nav-user">
            <a href="create_document.php" class="btn btn-sm btn-primary">+ New Document</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Quick Stats -->
        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($my_documents); ?></span>
                <span class="stat-label">My Documents</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($shared_documents); ?></span>
                <span class="stat-label">Shared with Me</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($recent_activity); ?></span>
                <span class="stat-label">Recent Activities</span>
            </div>
        </div>
        
        <!-- My Documents Section -->
        <div class="section">
            <div class="section-header">
                <h2>My Documents</h2>
                <p>Documents you've created</p>
            </div>
            
            <?php if (empty($my_documents)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <h3>No Documents Yet</h3>
                    <p>Create your first document to get started!</p>
                    <a href="create_document.php" class="btn btn-primary">Create Document</a>
                </div>
            <?php else: ?>
                <div class="documents-grid">
                    <?php foreach ($my_documents as $doc): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                                <div class="document-meta">
                                    <span class="date"><?php echo formatTimeAgo($doc['updated_at']); ?></span>
                                </div>
                            </div>
                            
                            <div class="document-preview">
                                <?php 
                                $preview = strip_tags($doc['content']);
                                echo htmlspecialchars(substr($preview, 0, 150)) . (strlen($preview) > 150 ? '...' : '');
                                ?>
                            </div>
                            
                            <div class="document-stats">
                                <span class="shared-count">
                                    👥 Shared with <?php echo $doc['shared_count']; ?> user(s)
                                </span>
                            </div>
                            
                            <div class="document-actions">
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>" 
                                   class="btn btn-sm btn-primary">Edit</a>
                                <a href="share_document.php?id=<?php echo $doc['id']; ?>" 
                                   class="btn btn-sm btn-secondary">Share</a>
                                <button onclick="viewActivity(<?php echo $doc['id']; ?>)" 
                                        class="btn btn-sm btn-outline">Activity</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Shared Documents Section -->
        <?php if (!empty($shared_documents)): ?>
        <div class="section">
            <div class="section-header">
                <h2>Shared with Me</h2>
                <p>Documents others have shared with you</p>
            </div>
            
            <div class="documents-grid">
                <?php foreach ($shared_documents as $doc): ?>
                    <div class="document-card shared">
                        <div class="document-header">
                            <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                            <div class="document-meta">
                                <span class="author">by <?php echo htmlspecialchars($doc['author_name']); ?></span>
                                <span class="date"><?php echo formatTimeAgo($doc['updated_at']); ?></span>
                            </div>
                        </div>
                        
                        <div class="document-preview">
                            <?php 
                            $preview = strip_tags($doc['content']);
                            echo htmlspecialchars(substr($preview, 0, 150)) . (strlen($preview) > 150 ? '...' : '');
                            ?>
                        </div>
                        
                        <div class="document-permission">
                            <span class="permission-badge <?php echo $doc['permission_type']; ?>">
                                <?php echo ucfirst($doc['permission_type']); ?> Access
                            </span>
                        </div>
                        
                        <div class="document-actions">
                            <?php if ($doc['permission_type'] === 'write'): ?>
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>" 
                                   class="btn btn-sm btn-primary">Edit</a>
                            <?php else: ?>
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>&readonly=1" 
                                   class="btn btn-sm btn-secondary">View</a>
                            <?php endif; ?>
                            <button onclick="viewActivity(<?php echo $doc['id']; ?>)" 
                                    class="btn btn-sm btn-outline">Activity</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Activity Section -->
        <?php if (!empty($recent_activity)): ?>
        <div class="section">
            <div class="section-header">
                <h2>Recent Activity</h2>
                <p>Latest changes in your documents</p>
            </div>
            
            <div class="activity-feed">
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-user">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($activity['username'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                <?php echo htmlspecialchars($activity['action_type']); ?>d
                                <strong><?php echo htmlspecialchars($activity['document_title']); ?></strong>
                            </div>
                            <div class="activity-time">
                                <?php echo formatTimeAgo($activity['timestamp']); ?>
                            </div>
                            <?php if ($activity['action_details']): ?>
                                <div class="activity-details">
                                    <?php echo htmlspecialchars($activity['action_details']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Activity Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Document Activity</h3>
                <button class="modal-close" onclick="closeActivityModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="activityLogs">Loading...</div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function viewActivity(documentId) {
            document.getElementById('activityModal').style.display = 'block';
            
            fetch(`../api/get_activity_logs.php?document_id=${documentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivityLogs(data.logs);
                    } else {
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
            
            let html = '<div class="activity-logs-detailed">';
            logs.forEach(log => {
                html += `
                    <div class="activity-log-item">
                        <div class="log-user">
                            <div class="user-avatar small">
                                ${log.username.substring(0, 2).toUpperCase()}
                            </div>
                            <strong>${log.username}</strong>
                        </div>
                        <div class="log-action">${log.action_type}</div>
                        <div class="log-details">${log.action_details || 'No details'}</div>
                        <div class="log-time">${formatTimeAgo(log.timestamp)}</div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        function closeActivityModal() {
            document.getElementById('activityModal').style.display = 'none';
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
            const modal = document.getElementById('activityModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>