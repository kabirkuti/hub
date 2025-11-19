<?php
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: $role/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT College - Attendance System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .dev-info {
    margin-top: 20px;
    padding: 16px 0;
    background: #fdfdfd;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    text-align: center;
}

.dev-title {
    color: #4a4a4a;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
}

.dev-links {
    font-size: 15px;
    font-weight: 600;
}

.dev-links a {
    color: #0066cc;
    text-decoration: none;
    padding: 3px 6px;
    position: relative;
    transition: 0.25s ease;
}

.dev-links a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 0%;
    height: 2px;
    background: #0066cc;
    border-radius: 2px;
    transition: width 0.25s ease;
}

.dev-links a:hover {
    color: #004a99;
}

.dev-links a:hover::after {
    width: 100%;
}

.divider {
    color: #888;
    padding: 0 10px;
    font-size: 16px;
}

    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 Attendance Hub </h1>
            <h2>Attendance Management System</h2>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    if ($_GET['error'] === 'invalid') {
                        echo "❌ Invalid username or password!";
                    } elseif ($_GET['error'] === 'unauthorized') {
                        echo "⛔ Unauthorized access!";
                    } elseif ($_GET['error'] === 'inactive') {
                        echo "⚠️ Your account is inactive. Contact admin.";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'logout'): ?>
            <div class="alert alert-success">
                ✅ Logged out successfully!
            </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="role">Login As:</label>
                <select name="role" id="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin">👨‍💼 Admin</option>
                    <option value="hod">👔 HOD</option>
                    <option value="teacher">👨‍🏫 Teacher</option>
                    <option value="student">👨‍🎓 Student</option>
                    <option value="parent">👨‍👩‍👦 Parent</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">Username / Roll Number / Email:</label>
                <input type="text" name="username" id="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">🔐 Login</button>
        </form>
        
      <div class="login-footer">
    <p>📧 Forgot password? Contact administrator</p>

    
    <hr style="margin: 10px 0; opacity: 0.3;">

   <div class="dev-info">
    <p class="dev-title"><strong>💻 Design & Development By</strong></p>
    <p class="dev-links">
        <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" target="_blank">
            ✨ Himanshu Patil
        </a>
        <span class="divider">|</span>
        <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" target="_blank">
            🚀 Pranay Panore
        </a>
    </p>
</div>
</div>

    </div>
</body>
</html>