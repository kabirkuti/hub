<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$department = $conn->query($dept_query)->fetch_assoc();

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get monthly attendance summary by class
$summary_query = "SELECT c.class_name, c.year, c.section,
                  COUNT(DISTINCT sa.student_id) as total_students,
                  COUNT(sa.id) as total_records,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_count
                  FROM classes c
                  LEFT JOIN student_attendance sa ON c.id = sa.class_id 
                  AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = '$filter_month'
                  WHERE c.department_id = $department_id
                  GROUP BY c.id
                  ORDER BY c.class_name";
$summary = $conn->query($summary_query);

// Get low attendance students (below 75%)
$low_attendance_query = "SELECT s.roll_number, s.full_name, c.class_name,
                         COUNT(sa.id) as total_days,
                         SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days
                         FROM students s
                         JOIN classes c ON s.class_id = c.id
                         LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                         AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = '$filter_month'
                         WHERE s.department_id = $department_id AND s.is_active = 1
                         GROUP BY s.id
                         HAVING (present_days / total_days * 100) < 75 AND total_days > 0
                         ORDER BY (present_days / total_days * 100) ASC";
$low_attendance = $conn->query($low_attendance_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - HOD</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 <?php echo htmlspecialchars($department['dept_name']); ?> - Reports</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="table-container" style="margin-bottom: 30px;">
            <h3>🔍 Select Month</h3>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group">
                    <label>Month:</label>
                    <input type="month" name="month" value="<?php echo $filter_month; ?>">
                </div>
                <button type="submit" class="btn btn-primary">View Report</button>
            </form>
        </div>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>📊 Class-wise Attendance Summary - <?php echo date('F Y', strtotime($filter_month.'-01')); ?></h3>
            
            <?php if ($summary->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Year</th>
                            <th>Section</th>
                            <th>Students</th>
                            <th>Total Records</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $summary->fetch_assoc()): 
                            $percentage = $row['total_records'] > 0 
                                ? round(($row['present_count'] / $row['total_records']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['section']); ?></span></td>
                            <td><?php echo $row['total_students']; ?></td>
                            <td><?php echo $row['total_records']; ?></td>
                            <td><span class="badge badge-success"><?php echo $row['present_count']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $row['absent_count']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $row['late_count']; ?></span></td>
                            <td>
                                <strong style="color: <?php echo $percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo $percentage; ?>%
                                </strong>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No attendance data found for selected month.</div>
            <?php endif; ?>
        </div>

        <?php if ($low_attendance->num_rows > 0): ?>
        <div class="table-container">
            <h3>⚠️ Students with Low Attendance (Below 75%)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $low_attendance->fetch_assoc()): 
                        $percentage = round(($student['present_days'] / $student['total_days']) * 100, 2);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                        <td><?php echo $student['total_days']; ?></td>
                        <td><?php echo $student['present_days']; ?></td>
                        <td>
                            <strong style="color: #dc3545;">
                                <?php echo $percentage; ?>%
                            </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>