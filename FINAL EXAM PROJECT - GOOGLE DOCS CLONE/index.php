<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Docs Clone</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="landing-container">
        <header class="landing-header">
            <h1>📝 Google Docs Clone</h1>
            <p>Create, edit, and collaborate on documents in real-time</p>
        </header>
        
        <main class="landing-main">
            <div class="landing-card">
                <h2>Welcome Back!</h2>
                <p>Sign in to access your documents and start collaborating.</p>
                <a href="login.php" class="btn btn-primary">Sign In</a>
            </div>
            
            <div class="landing-card">
                <h2>New Here?</h2>
                <p>Create an account to start creating and sharing documents.</p>
                <a href="register.php" class="btn btn-secondary">Create Account</a>
            </div>
        </main>
        
        <footer class="landing-footer">
            <div class="features">
                <div class="feature">
                    <h3>🔄 Real-time Collaboration</h3>
                    <p>Work together with others in real-time</p>
                </div>
                <div class="feature">
                    <h3>💾 Auto-save</h3>
                    <p>Your work is automatically saved as you type</p>
                </div>
                <div class="feature">
                    <h3>📱 Access Anywhere</h3>
                    <p>Access your documents from any device</p>
                </div>
                <div class="feature">
                    <h3>🔒 Secure Sharing</h3>
                    <p>Control who can view and edit your documents</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>