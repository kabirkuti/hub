<?php
require_once '../db.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];

// Get parent info
$parent = $conn->query("SELECT * FROM parents WHERE id = $parent_id")->fetch_assoc();

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT * FROM student_attendance 
                WHERE student_id = $student_id AND attendance_date = '$today'";
$today_attendance = $conn->query($today_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Attendance - Parent</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - Child's Today Attendance</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍👩‍👦 <?php echo htmlspecialchars($parent['parent_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 30px; border-radius: 15px; text-align: center;">
            <h2>📅 Today's Date: <?php echo date('l, d F Y'); ?></h2>
            <h3 style="margin-top: 10px; color: #667eea;">
                Child: <?php echo htmlspecialchars($student['full_name']); ?> 
                (<?php echo htmlspecialchars($student['roll_number']); ?>)
            </h3>
            
            <?php if ($today_attendance): ?>
                <div style="margin: 40px 0;">
                    <?php
                    $icon = '';
                    $color = '';
                    $message = '';
                    
                    if ($today_attendance['status'] === 'present') {
                        $icon = '✅';
                        $color = '#28a745';
                        $message = 'Your child was marked PRESENT today!';
                    } elseif ($today_attendance['status'] === 'absent') {
                        $icon = '❌';
                        $color = '#dc3545';
                        $message = 'Your child was marked ABSENT today!';
                    } else {
                        $icon = '⏰';
                        $color = '#ffc107';
                        $message = 'Your child was marked LATE today!';
                    }
                    ?>
                    
                    <div style="font-size: 80px;"><?php echo $icon; ?></div>
                    <h1 style="color: <?php echo $color; ?>; margin: 20px 0;">
                        <?php echo $message; ?>
                    </h1>
                    
                    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <p style="font-size: 18px; margin: 10px 0;">
                            <strong>Status:</strong> 
                            <span style="color: <?php echo $color; ?>; font-size: 24px;">
                                <?php echo strtoupper($today_attendance['status']); ?>
                            </span>
                        </p>
                        <p style="font-size: 18px; margin: 10px 0;">
                            <strong>Class:</strong> <?php echo htmlspecialchars($student['class_name']); ?>
                        </p>
                        <p style="font-size: 18px; margin: 10px 0;">
                            <strong>Marked At:</strong> <?php echo date('h:i A', strtotime($today_attendance['marked_at'])); ?>
                        </p>
                        <?php if ($today_attendance['remarks']): ?>
                        <p style="font-size: 18px; margin: 10px 0;">
                            <strong>Remarks:</strong> <?php echo htmlspecialchars($today_attendance['remarks']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($today_attendance['status'] === 'absent'): ?>
                        <div class="alert alert-warning" style="margin-top: 30px;">
                            <strong>⚠️ Important:</strong> Your child was absent today. Please ensure regular attendance for better academic performance.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="margin: 40px 0;">
                    <div style="font-size: 80px;">⏳</div>
                    <h1 style="color: #ffc107; margin: 20px 0;">
                        Attendance Not Marked Yet
                    </h1>
                    <p style="font-size: 18px; color: #666;">
                        The teacher hasn't marked attendance for today. Please check back later.
                    </p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="attendance_report.php" class="btn btn-primary">
                    📊 View Detailed Report
                </a>
            </div>
        </div>
    </div>
</body>
</html>