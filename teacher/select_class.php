<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $_SESSION['user_id'];

// Get teacher's classes
$classes_query = "SELECT c.*, d.dept_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND is_active = 1) as student_count
                  FROM classes c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.teacher_id = $teacher_id
                  ORDER BY c.class_name";
$classes = $conn->query($classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Class - Teacher</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - Select Class</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <h2>📚 Select Class to Mark Attendance</h2>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Students</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($classes->num_rows > 0): ?>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['dept_name']); ?></td>
                            <td><?php echo $class['year']; ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                            <td><?php echo $class['semester']; ?></td>
                            <td><span class="badge badge-success"><?php echo $class['student_count']; ?></span></td>
                            <td>
                                <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>" 
                                   class="btn btn-primary">
                                    ✅ Mark Attendance
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">
                                <div class="alert alert-info">No classes assigned to you yet. Please contact admin.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>