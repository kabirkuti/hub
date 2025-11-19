<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_parent'])) {
        $parent_name = sanitize($_POST['parent_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $student_id = intval($_POST['student_id']);
        $relationship = sanitize($_POST['relationship']);
        
        // Handle photo upload
        $photo_path = NULL;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/parents/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                $new_filename = 'parent_' . time() . '_' . uniqid() . '.' . $file_ext;
                $photo_path = $new_filename;  // Store only filename
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename);
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO parents (parent_name, email, phone, password, student_id, relationship, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $parent_name, $email, $phone, $password, $student_id, $relationship, $photo_path);
        
        if ($stmt->execute()) {
            $success = "Parent added successfully!";
        } else {
            $error = "Error adding parent: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_parent'])) {
        $parent_id = intval($_POST['parent_id']);
        
        // Get photo path before deleting
        $result = $conn->query("SELECT photo FROM parents WHERE id = $parent_id");
        if ($row = $result->fetch_assoc() && $row['photo']) {
            @unlink('../uploads/parents/' . $row['photo']);
        }
        
        if ($conn->query("DELETE FROM parents WHERE id = $parent_id")) {
            $success = "Parent deleted successfully!";
        } else {
            $error = "Error deleting parent: " . $conn->error;
        }
    }
}

// Get all parents
$parents_query = "SELECT p.*, s.roll_number, s.full_name as student_name, d.dept_name
                  FROM parents p
                  JOIN students s ON p.student_id = s.id
                  LEFT JOIN departments d ON s.department_id = d.id
                  ORDER BY p.parent_name";
$parents = $conn->query($parents_query);

// Get students for dropdown
$students = $conn->query("SELECT id, roll_number, full_name FROM students WHERE is_active = 1 ORDER BY roll_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - Admin</title>
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
            <h1>🎓 NIT College - Manage Parents</h1>
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
            <h3>➕ Add New Parent</h3>
            <form method="POST" enctype="multipart/form-data" style="max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Parent Name:</label>
                    <input type="text" name="parent_name" required>
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
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Student:</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php 
                        $students->data_seek(0);
                        while ($student = $students->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Relationship:</label>
                    <select name="relationship" required>
                        <option value="">-- Select --</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📸 Profile Photo (Optional - JPG, PNG, GIF - Max 5MB):</label>
                    <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                    <img id="photoPreview" class="photo-preview" alt="Preview">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_parent" class="btn btn-primary">Add Parent</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>👨‍👩‍👦 All Parents</h3>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Parent Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Student</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Relationship</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $parents->data_seek(0);
                    while ($parent = $parents->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($parent['photo']) && file_exists("../uploads/parents/" . $parent['photo'])): ?>
                                <img src="../uploads/parents/<?php echo htmlspecialchars($parent['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 35px;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($parent['parent_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                        <td><?php echo htmlspecialchars($parent['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($parent['dept_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst($parent['relationship']); ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this parent?');">
                                <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                <button type="submit" name="delete_parent" class="btn btn-danger btn-sm">Delete</button>
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