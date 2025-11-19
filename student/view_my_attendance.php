<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get date filter
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get attendance for selected date
$attendance_query = "SELECT * FROM student_attendance 
                     WHERE student_id = $student_id AND attendance_date = '$filter_date'";
$attendance = $conn->query($attendance_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - My Attendance</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="table-container" style="margin-bottom: 30px;">
            <h3>📅 Select Date</h3>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo $filter_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary">View</button>
            </form>
        </div>

        <?php if ($attendance): ?>
            <div class="table-container">
                <h3>📝 Attendance Details for <?php echo date('d F Y', strtotime($filter_date)); ?></h3>
                
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 18px;">
                        <div><strong>Date:</strong> <?php echo date('l, d F Y', strtotime($attendance['attendance_date'])); ?></div>
                        <div>
                            <strong>Status:</strong> 
                            <?php
                            $status_class = '';
                            if ($attendance['status'] === 'present') $status_class = 'badge-success';
                            elseif ($attendance['status'] === 'absent') $status_class = 'badge-danger';
                            else $status_class = 'badge-warning';
                            ?>
                            <span class="badge <?php echo $status_class; ?>" style="font-size: 16px; padding: 8px 15px;">
                                <?php echo strtoupper($attendance['status']); ?>
                            </span>
                        </div>
                        <div><strong>Marked At:</strong> <?php echo date('h:i A', strtotime($attendance['marked_at'])); ?></div>
                        <div><strong>Remarks:</strong> <?php echo htmlspecialchars($attendance['remarks'] ?? 'No remarks'); ?></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ No attendance record found for <?php echo date('d F Y', strtotime($filter_date)); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>