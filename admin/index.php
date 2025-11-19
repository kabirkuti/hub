<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get statistics
$stats = [];

// Total departments
$result = $conn->query("SELECT COUNT(*) as count FROM departments");
$stats['departments'] = $result->fetch_assoc()['count'];

// Total HODs
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod' AND is_active = 1");
$stats['hods'] = $result->fetch_assoc()['count'];

// Total teachers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
$stats['students'] = $result->fetch_assoc()['count'];

// Total classes
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];

// Total parents
$result = $conn->query("SELECT COUNT(*) as count FROM parents");
$stats['parents'] = $result->fetch_assoc()['count'];

// Today's attendance
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE()");
$stats['today_attendance'] = $result->fetch_assoc()['count'];

// Recent activities
$recent_query = "SELECT sa.*, s.full_name as student_name, s.roll_number, 
                 c.class_name, u.full_name as teacher_name
                 FROM student_attendance sa
                 JOIN students s ON sa.student_id = s.id
                 JOIN classes c ON sa.class_id = c.id
                 JOIN users u ON sa.marked_by = u.id
                 ORDER BY sa.marked_at DESC LIMIT 10";
$recent_activities = $conn->query($recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - Admin Panel</h1>
        </div>
        <div class="user-info">
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <h2>📊 Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🏢 Departments</h3>
                <div class="stat-value"><?php echo $stats['departments']; ?></div>
                <a href="manage_departments.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👔 HODs</h3>
                <div class="stat-value"><?php echo $stats['hods']; ?></div>
                <a href="manage_hod.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🏫 Teachers</h3>
                <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                <a href="manage_teachers.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🎓 Students</h3>
                <div class="stat-value"><?php echo $stats['students']; ?></div>
                <a href="manage_students.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>📚 Classes</h3>
                <div class="stat-value"><?php echo $stats['classes']; ?></div>
                <a href="manage_classes.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍👩‍👦 Parents</h3>
                <div class="stat-value"><?php echo $stats['parents']; ?></div>
                <a href="manage_parents.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>📝 Today's Attendance</h3>
                <div class="stat-value"><?php echo $stats['today_attendance']; ?></div>
                <a href="view_attendance_reports.php" class="btn btn-info btn-sm">View Reports</a>
            </div>
        </div>

        <div class="table-container">
            <h3>🕒 Recent Attendance Activities</h3>
            
            <?php if ($recent_activities->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Student</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Marked By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($activity['marked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($activity['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($activity['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($activity['class_name']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($activity['status'] === 'present') $status_class = 'badge-success';
                                elseif ($activity['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($activity['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($activity['teacher_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No recent attendance activities</div>
            <?php endif; ?>
        </div>

 

    </div>

  
</body>
</html>