<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $class_id = intval($_POST['class_id']);
    $teacher_id = intval($_POST['teacher_id']);
    
    $stmt = $conn->prepare("UPDATE classes SET teacher_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $teacher_id, $class_id);
    
    if ($stmt->execute()) {
        $success = "Teacher assigned successfully!";
    } else {
        $error = "Error assigning teacher: " . $conn->error;
    }
}

// Get all classes with teacher info
$classes_query = "SELECT c.*, d.dept_name, u.full_name as teacher_name
                  FROM classes c
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN users u ON c.teacher_id = u.id
                  ORDER BY c.class_name";
$classes = $conn->query($classes_query);

// Get all teachers
$teachers_query = "SELECT id, full_name, department_id FROM users 
                   WHERE role = 'teacher' AND is_active = 1 
                   ORDER BY full_name";
$teachers = $conn->query($teachers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Classes - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - Assign Teachers to Classes</h1>
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

        <div class="table-container">
            <h3>🎯 Class & Teacher Assignments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Current Teacher</th>
                        <th>Assign Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($class = $classes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['dept_name']); ?></td>
                        <td><?php echo $class['year']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                        <td>
                            <?php if ($class['teacher_name']): ?>
                                <span class="badge badge-success"><?php echo htmlspecialchars($class['teacher_name']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-warning">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 10px;">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <select name="teacher_id" required style="padding: 5px;">
                                    <option value="">-- Select Teacher --</option>
                                    <?php 
                                    $teachers->data_seek(0);
                                    while ($teacher = $teachers->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $teacher['id']; ?>" 
                                                <?php echo $class['teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" name="assign_teacher" class="btn btn-primary btn-sm">
                                    Assign
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