<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$department = $conn->query($dept_query)->fetch_assoc();

// Get department students
$students_query = "SELECT s.*, c.class_name,
                   (SELECT COUNT(*) FROM student_attendance WHERE student_id = s.id) as total_attendance,
                   (SELECT COUNT(*) FROM student_attendance WHERE student_id = s.id AND status = 'present') as present_count
                   FROM students s
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE s.department_id = $department_id AND s.is_active = 1
                   ORDER BY s.roll_number";
$students = $conn->query($students_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Students - HOD</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 <?php echo htmlspecialchars($department['dept_name']); ?> - Students</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="table-container">
            <h3>👨‍🎓 Department Students</h3>
            
            <?php if ($students->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students->fetch_assoc()): 
                            $attendance_percentage = $student['total_attendance'] > 0 
                                ? round(($student['present_count'] / $student['total_attendance']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><?php echo $student['year']; ?></td>
                            <td><?php echo $student['semester']; ?></td>
                            <td>
                                <strong style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo $attendance_percentage; ?>%
                                </strong>
                            </td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No students found in this department.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>