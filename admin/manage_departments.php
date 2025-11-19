<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_department'])) {
        $dept_name = sanitize($_POST['dept_name']);
        $dept_code = sanitize($_POST['dept_code']);
        $hod_id = !empty($_POST['hod_id']) ? intval($_POST['hod_id']) : NULL;
        
        $stmt = $conn->prepare("INSERT INTO departments (dept_name, dept_code, hod_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $dept_name, $dept_code, $hod_id);
        
        if ($stmt->execute()) {
            $success = "Department added successfully!";
        } else {
            $error = "Error adding department: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_department'])) {
        $dept_id = intval($_POST['dept_id']);
        $dept_name = sanitize($_POST['dept_name']);
        $dept_code = sanitize($_POST['dept_code']);
        $hod_id = !empty($_POST['hod_id']) ? intval($_POST['hod_id']) : NULL;
        
        $stmt = $conn->prepare("UPDATE departments SET dept_name = ?, dept_code = ?, hod_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $dept_name, $dept_code, $hod_id, $dept_id);
        
        if ($stmt->execute()) {
            $success = "Department updated successfully!";
        } else {
            $error = "Error updating department: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_department'])) {
        $dept_id = intval($_POST['dept_id']);
        
        if ($conn->query("DELETE FROM departments WHERE id = $dept_id")) {
            $success = "Department deleted successfully!";
        } else {
            $error = "Error deleting department: " . $conn->error;
        }
    }
}

// Get all departments
$departments_query = "SELECT d.*, u.full_name as hod_name 
                     FROM departments d
                     LEFT JOIN users u ON d.hod_id = u.id
                     ORDER BY d.dept_name";
$departments = $conn->query($departments_query);

// Get available HODs
$hods_query = "SELECT id, full_name FROM users WHERE role = 'hod' AND is_active = 1 ORDER BY full_name";
$hods = $conn->query($hods_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Manage Departments</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
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
            <h3>➕ Add New Department</h3>
            <form method="POST" style="max-width: 600px;">
                <div class="form-group">
                    <label>Department Name:</label>
                    <input type="text" name="dept_name" required placeholder="e.g., Computer Science & Engineering">
                </div>
                
                <div class="form-group">
                    <label>Department Code:</label>
                    <input type="text" name="dept_code" required placeholder="e.g., CSE">
                </div>
                
                <div class="form-group">
                    <label>Assign HOD (Optional):</label>
                    <select name="hod_id">
                        <option value="">-- Select HOD --</option>
                        <?php 
                        $hods->data_seek(0);
                        while ($hod = $hods->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $hod['id']; ?>">
                                <?php echo htmlspecialchars($hod['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
            </form>
        </div>

        <div class="table-container">
            <h3>🏢 All Departments</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Code</th>
                        <th>HOD</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($dept = $departments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $dept['id']; ?></td>
                        <td><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($dept['dept_code']); ?></span></td>
                        <td><?php echo $dept['hod_name'] ? htmlspecialchars($dept['hod_name']) : 'Not Assigned'; ?></td>
                        <td><?php echo date('d M Y', strtotime($dept['created_at'])); ?></td>
                        <td>
                            <button onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)" 
                                    class="btn btn-warning btn-sm">Edit</button>
                            <form method="POST" style="display:inline;" 
                                  onsubmit="return confirm('Delete this department?');">
                                <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                <button type="submit" name="delete_department" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; padding:50px;">
        <div style="background:white; max-width:600px; margin:auto; padding:30px; border-radius:10px;">
            <h3>✏️ Edit Department</h3>
            <form method="POST">
                <input type="hidden" name="dept_id" id="edit_dept_id">
                
                <div class="form-group">
                    <label>Department Name:</label>
                    <input type="text" name="dept_name" id="edit_dept_name" required>
                </div>
                
                <div class="form-group">
                    <label>Department Code:</label>
                    <input type="text" name="dept_code" id="edit_dept_code" required>
                </div>
                
                <div class="form-group">
                    <label>HOD:</label>
                    <select name="hod_id" id="edit_hod_id">
                        <option value="">-- Select HOD --</option>
                        <?php 
                        $hods->data_seek(0);
                        while ($hod = $hods->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $hod['id']; ?>">
                                <?php echo htmlspecialchars($hod['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" name="update_department" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>

    <script>
    function editDepartment(dept) {
        document.getElementById('edit_dept_id').value = dept.id;
        document.getElementById('edit_dept_name').value = dept.dept_name;
        document.getElementById('edit_dept_code').value = dept.dept_code;
        document.getElementById('edit_hod_id').value = dept.hod_id || '';
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    </script>
</body>
</html>