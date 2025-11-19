<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher'])) {
        try {
            $username = sanitize($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $department_id = intval($_POST['department_id']);
            
            // Handle photo upload
            $photo_path = NULL;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/teachers/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                    $new_filename = 'teacher_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $photo_path = 'uploads/teachers/' . $new_filename;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path)) {
                        $photo_path = NULL;
                        $error = "Failed to upload photo, but teacher will be added without photo.";
                    }
                }
            }
            
            // Check if photo column exists
            $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'photo'");
            
            if ($check_column->num_rows > 0) {
                // Photo column exists - use it
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, photo) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?)");
                $stmt->bind_param("sssssss", $username, $password, $full_name, $email, $phone, $department_id, $photo_path);
            } else {
                // Photo column doesn't exist - don't use it
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id) VALUES (?, ?, ?, ?, ?, 'teacher', ?)");
                $stmt->bind_param("ssssss", $username, $password, $full_name, $email, $phone, $department_id);
                $error = "Photo column doesn't exist in database. Please run the ALTER TABLE query first.";
            }
            
            if ($stmt->execute()) {
                $success = "Teacher added successfully!";
            } else {
                $error = "Error adding teacher: " . $stmt->error;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $new_status = intval($_POST['new_status']);
        
        $conn->query("UPDATE users SET is_active = $new_status WHERE id = $teacher_id");
        $success = "Teacher status updated!";
    }
}

// Get all teachers
$teachers_query = "SELECT u.*, d.dept_name,
                   (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                   FROM users u
                   LEFT JOIN departments d ON u.department_id = d.id
                   WHERE u.role = 'teacher'
                   ORDER BY u.full_name";
$teachers = $conn->query($teachers_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .profile-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            display: none;
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
            <h1>🎓 NIT College - Manage Teachers</h1>
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
            <h3>➕ Add New Teacher</h3>
            <form method="POST" enctype="multipart/form-data" style="max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📸 Profile Photo (JPG, PNG, GIF - Max 5MB):</label>
                    <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                    <img id="photoPreview" class="photo-preview" alt="Preview">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>👨‍🏫 All Teachers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Classes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (isset($teacher['photo']) && $teacher['photo']): ?>
                                <img src="../<?php echo htmlspecialchars($teacher['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 40px;">👨‍🏫</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['dept_name'] ?? 'Not Assigned'); ?></td>
                        <td><span class="badge badge-info"><?php echo $teacher['class_count']; ?></span></td>
                        <td>
                            <?php if ($teacher['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $teacher['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $teacher['is_active'] ? 'Deactivate' : 'Activate'; ?>
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