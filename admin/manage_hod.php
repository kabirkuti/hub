<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_hod'])) {
        $username = sanitize($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $department_id = intval($_POST['department_id']);
        
        // Handle photo upload
        $photo_path = NULL;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/hods/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
                chmod($upload_dir, 0777);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                $new_filename = 'hod_' . time() . '_' . uniqid() . '.' . $file_ext;
                $photo_path = $new_filename;  // Store only filename
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename)) {
                    chmod($upload_dir . $new_filename, 0644);
                } else {
                    $error = "Failed to upload photo. Please check folder permissions.";
                    $photo_path = NULL;
                }
            }
        }
        
        if (!isset($error)) {
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, photo) VALUES (?, ?, ?, ?, ?, 'hod', ?, ?)");
            $stmt->bind_param("sssssis", $username, $password, $full_name, $email, $phone, $department_id, $photo_path);
            
            if ($stmt->execute()) {
                $hod_id = $conn->insert_id;
                // Update department with HOD
                $conn->query("UPDATE departments SET hod_id = $hod_id WHERE id = $department_id");
                $success = "HOD added successfully!";
            } else {
                $error = "Error adding HOD: " . $conn->error;
            }
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $hod_id = intval($_POST['hod_id']);
        $new_status = intval($_POST['new_status']);
        
        $conn->query("UPDATE users SET is_active = $new_status WHERE id = $hod_id");
        $success = "HOD status updated!";
    }
}

// Get all HODs
$hods_query = "SELECT u.*, d.dept_name 
               FROM users u
               LEFT JOIN departments d ON u.department_id = d.id
               WHERE u.role = 'hod'
               ORDER BY u.full_name";
$hods = $conn->query($hods_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage HODs - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .profile-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            display: none;
            border: 3px solid #667eea;
        }
    </style>
    <script>
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Manage HODs</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>➕ Add New HOD</h3>
            <form method="POST" enctype="multipart/form-data" style="max-width: 800px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php 
                        $departments->data_seek(0);
                        while ($dept = $departments->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📸 Profile Photo (Optional - JPG, PNG, GIF - Max 5MB):</label>
                    <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                    <img id="photoPreview" class="photo-preview" alt="Preview">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_hod" class="btn btn-primary">Add HOD</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>👔 All HODs</h3>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hods->data_seek(0);
                    while ($hod = $hods->fetch_assoc()): 
                        // Check if photo exists with full path
                        $photo_exists = false;
                        $photo_src = '';
                        
                        if (!empty($hod['photo'])) {
                            // Try both paths
                            if (file_exists("../uploads/hods/" . $hod['photo'])) {
                                $photo_exists = true;
                                $photo_src = "../uploads/hods/" . htmlspecialchars($hod['photo']);
                            } elseif (file_exists("../" . $hod['photo'])) {
                                $photo_exists = true;
                                $photo_src = "../" . htmlspecialchars($hod['photo']);
                            }
                        }
                    ?>
                    <tr>
                        <td>
                            <?php if ($photo_exists): ?>
                                <img src="<?php echo $photo_src; ?>" 
                                     alt="Photo" class="profile-photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <span style="font-size: 35px; display: none;">👤</span>
                            <?php else: ?>
                                <span style="font-size: 35px;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($hod['username']); ?></td>
                        <td><?php echo htmlspecialchars($hod['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($hod['email']); ?></td>
                        <td><?php echo htmlspecialchars($hod['phone']); ?></td>
                        <td><?php echo htmlspecialchars($hod['dept_name'] ?? 'Not Assigned'); ?></td>
                        <td>
                            <?php if ($hod['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="hod_id" value="<?php echo $hod['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $hod['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $hod['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>