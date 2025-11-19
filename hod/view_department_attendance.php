<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$department = $conn->query($dept_query)->fetch_assoc();

// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_class = isset($_GET['class']) ? intval($_GET['class']) : '';

// Build query
$where_clauses = ["sa.attendance_date = '$filter_date'", "c.department_id = $department_id"];

if ($filter_class) {
    $where_clauses[] = "c.id = $filter_class";
}

$where_sql = implode(' AND ', $where_clauses);

// Get attendance records
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name,
                     c.class_name, u.full_name as teacher_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     JOIN classes c ON sa.class_id = c.id
                     JOIN users u ON sa.marked_by = u.id
                     WHERE $where_sql
                     ORDER BY c.class_name, s.roll_number";
$attendance_records = $conn->query($attendance_query);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance sa
                JOIN classes c ON sa.class_id = c.id
                WHERE $where_sql";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get department classes for filter
$classes_query = "SELECT * FROM classes WHERE department_id = $department_id ORDER BY class_name";
$classes = $conn->query($classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Attendance - HOD</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 <?php echo htmlspecialchars($department['dept_name']); ?> - Attendance</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="table-container" style="margin-bottom: 30px;">
            <h3>🔍 Filter Attendance</h3>
            <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px;">
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo $filter_date; ?>">
                </div>
                
                <div class="form-group">
                    <label>Class:</label>
                    <select name="class">
                        <option value="">All Classes</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <h3>📊 Total Records</h3>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['late']; ?></div>
            </div>
        </div>

        <div class="table-container">
            <h3>📝 Attendance Records for <?php echo date('d M Y', strtotime($filter_date)); ?></h3>
            
            <?php if ($attendance_records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $attendance_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($record['status'] === 'present') $status_class = 'badge-success';
                                elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found for the selected filters.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>