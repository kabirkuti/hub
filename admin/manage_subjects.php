<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $subject_name = sanitize($_POST['subject_name']);
        $subject_code = sanitize($_POST['subject_code']);
        $department_id = intval($_POST['department_id']);
        $semester = intval($_POST['semester']);
        
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, department_id, semester) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $subject_name, $subject_code, $department_id, $semester);
        
        if ($stmt->execute()) {
            $success = "Subject added successfully!";
        } else {
            $error = "Error adding subject: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_subject'])) {
        $subject_id = intval($_POST['subject_id']);
        
        if ($conn->query("DELETE FROM subjects WHERE id = $subject_id")) {
            $success = "Subject deleted successfully!";
        } else {
            $error = "Error deleting subject: " . $conn->error;
        }
    }
}

// Get all subjects
$subjects_query = "SELECT s.*, d.dept_name 
                   FROM subjects s
                   LEFT JOIN departments d ON s.department_id = d.id
                   ORDER BY d.dept_name, s.semester, s.subject_name";
$subjects = $conn->query($subjects_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Manage Subjects</h1>
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
            <h3>➕ Add New Subject</h3>
            <form method="POST" style="max-width: 800px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Subject Name:</label>
                    <input type="text" name="subject_name" required placeholder="e.g., Data Structures">
                </div>
                
                <div class="form-group">
                    <label>Subject Code:</label>
                    <input type="text" name="subject_code" required placeholder="e.g., CS301">
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
                
                <div class="form-group">
                    <label>Semester:</label>
                    <select name="semester" required>
                        <option value="">-- Select --</option>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>📚 All Subjects</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $subject['id']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($subject['dept_name']); ?></td>
                        <td><?php echo $subject['semester']; ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject?');">
                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                <button type="submit" name="delete_subject" class="btn btn-danger btn-sm">Delete</button>
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