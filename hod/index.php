<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$dept_result = $conn->query($dept_query);
$department = $dept_result->fetch_assoc();

// Get statistics
$stats = [];

// Total teachers in department
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND department_id = $department_id AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total students in department
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE department_id = $department_id AND is_active = 1");
$stats['students'] = $result->fetch_assoc()['count'];

// Total classes in department
$result = $conn->query("SELECT COUNT(*) as count FROM classes WHERE department_id = $department_id");
$stats['classes'] = $result->fetch_assoc()['count'];

// Today's attendance in department
$today = date('Y-m-d');
$today_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent
                FROM student_attendance sa
                JOIN classes c ON sa.class_id = c.id
                WHERE c.department_id = $department_id AND sa.attendance_date = '$today'";
$today_result = $conn->query($today_query);
$today_stats = $today_result->fetch_assoc();

// Get department teachers
$teachers_query = "SELECT * FROM users WHERE role = 'teacher' AND department_id = $department_id AND is_active = 1 ORDER BY full_name";
$teachers = $conn->query($teachers_query);

// Get department classes with attendance
$classes_query = "SELECT c.*, u.full_name as teacher_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND is_active = 1) as student_count,
                  (SELECT COUNT(*) FROM student_attendance WHERE class_id = c.id AND attendance_date = '$today') as today_marked
                  FROM classes c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.department_id = $department_id
                  ORDER BY c.class_name";
$classes = $conn->query($classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - HOD Panel</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2>🏢 <?php echo htmlspecialchars($department['dept_name']); ?></h2>
            <p><strong>Department Code:</strong> <?php echo htmlspecialchars($department['dept_code']); ?></p>
            <div style="margin-top: 10px;">
                <p>💡 <strong>Tip:</strong> Click "👤 My Profile" above to view and upload your profile photo!</p>
            </div>
        </div>

        <h3>📊 Department Overview</h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👨‍🏫 Teachers</h3>
                <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                <a href="view_teachers.php" class="btn btn-info btn-sm">View Teachers</a>
                 <a href="http://localhost/NIT/admin/manage_teachers.php" class="btn btn-info btn-sm">Add Teachers</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🎓 Students</h3>
                <div class="stat-value"><?php echo $stats['students']; ?></div>
                <a href="view_students.php" class="btn btn-info btn-sm">View Students</a>
            </div>
            
            <div class="stat-card">
                <h3>📚 Classes</h3>
                <div class="stat-value"><?php echo $stats['classes']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📝 Today's Attendance</h3>
                <div class="stat-value"><?php echo $today_stats['total'] ?? 0; ?></div>
                <p style="margin-top: 10px; font-size: 14px;">
                    <span style="color: #28a745;">✅ <?php echo $today_stats['present'] ?? 0; ?></span> | 
                    <span style="color: #dc3545;">❌ <?php echo $today_stats['absent'] ?? 0; ?></span>
                </p>
            </div>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📚 Department Classes - Today's Attendance Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Total Students</th>
                        <th>Today's Marked</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $classes->data_seek(0);
                    while ($class = $classes->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo $class['year']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                        <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Not Assigned'); ?></td>
                        <td><span class="badge badge-success"><?php echo $class['student_count']; ?></span></td>
                        <td><?php echo $class['today_marked']; ?></td>
                        <td>
                            <?php if ($class['today_marked'] > 0): ?>
                                <span class="badge badge-success">✅ Marked</span>
                            <?php else: ?>
                                <span class="badge badge-warning">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>👨‍🏫 Department Teachers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $teachers->data_seek(0);
                    while ($teacher = $teachers->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td><span class="badge badge-success">Active</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>