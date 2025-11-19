<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Verify teacher has access to this class
$verify_query = "SELECT c.*, d.dept_name FROM classes c 
                 JOIN departments d ON c.department_id = d.id
                 WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $class_id, $user['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Get the section from the class
$section = $class['section'];

// Get filter parameters
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// FIXED: Get attendance records for ALL STUDENTS in this SECTION
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     JOIN classes c ON s.class_id = c.id
                     WHERE c.section = ? 
                     AND sa.attendance_date BETWEEN ? AND ?
                     AND sa.marked_by = ?
                     ORDER BY sa.attendance_date DESC, s.roll_number";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("sssi", $section, $filter_date_from, $filter_date_to, $user['id']);
$stmt->execute();
$attendance_records = $stmt->get_result();

// FIXED: Get statistics for the SECTION
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE c.section = ? 
                AND sa.attendance_date BETWEEN ? AND ?
                AND sa.marked_by = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("sssi", $section, $filter_date_from, $filter_date_to, $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// FIXED: Get student-wise attendance summary for ALL students in SECTION
$summary_query = "SELECT s.roll_number, s.full_name,
                  COUNT(sa.id) as total_days,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days
                  FROM students s
                  JOIN classes c ON s.class_id = c.id
                  LEFT JOIN student_attendance sa ON s.id = sa.student_id
                  AND sa.attendance_date BETWEEN ? AND ?
                  AND sa.marked_by = ?
                  WHERE c.section = ?
                  AND s.is_active = 1
                  GROUP BY s.id, s.roll_number, s.full_name
                  ORDER BY s.roll_number";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("ssis", $filter_date_from, $filter_date_to, $user['id'], $section);
$stmt->execute();
$student_summary = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - <?php echo htmlspecialchars($class['section']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
    <style>
        .download-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .download-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 15px;
        }
        .download-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
        }
        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .btn-excel {
            background: linear-gradient(135deg, #1e7e34, #28a745);
        }
        .btn-pdf {
            background: linear-gradient(135deg, #c82333, #dc3545);
        }
        .btn-print {
            background: linear-gradient(135deg, #5a6268, #6c757d);
        }
        @media print {
            .navbar, .download-section, .btn { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Reports - <?php echo htmlspecialchars($class['section']); ?></h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="summary-card">
            <h2>📚 <?php echo htmlspecialchars($class['class_name']); ?></h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="label">Section</div>
                    <div class="number"><?php echo htmlspecialchars($class['section']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Department</div>
                    <div class="number"><?php echo htmlspecialchars($class['dept_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Year</div>
                    <div class="number"><?php echo $class['year']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Semester</div>
                    <div class="number"><?php echo $class['semester']; ?></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h3>🔍 Filter Attendance</h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                
                <div class="form-group">
                    <label>From Date:</label>
                    <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>">
                </div>
                
                <div class="form-group">
                    <label>To Date:</label>
                    <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>">
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
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
            <h3>👥 Student-wise Attendance Summary</h3>
            <p style="margin-bottom: 15px; color: #666;">
                Showing attendance for students in section: <strong><?php echo htmlspecialchars($class['section']); ?></strong>
            </p>
            
            <?php if ($student_summary->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $student_summary->data_seek(0); // Reset pointer
                        while ($student = $student_summary->fetch_assoc()): 
                            $percentage = $student['total_days'] > 0 
                                ? round(($student['present_days'] / $student['total_days']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo $student['total_days']; ?></td>
                            <td><span class="badge badge-success"><?php echo $student['present_days']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $student['absent_days']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $student['late_days']; ?></span></td>
                            <td><strong><?php echo $percentage; ?>%</strong></td>
                            <td>
                                <?php if ($percentage >= 75): ?>
                                    <span class="badge badge-success">Good</span>
                                <?php elseif ($percentage >= 60): ?>
                                    <span class="badge badge-warning">Average</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Low ⚠️</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    ℹ️ No students found in this section. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>

        <!-- Download Section -->
        <?php if ($attendance_records->num_rows > 0): ?>
        <div class="download-section">
            <h3 style="text-align: center; margin-bottom: 10px;">📥 Download Detailed Attendance Records</h3>
            <p style="text-align: center; color: #666; margin-bottom: 15px;">
                Export attendance data from <strong><?php echo date('d M Y', strtotime($filter_date_from)); ?></strong> 
                to <strong><?php echo date('d M Y', strtotime($filter_date_to)); ?></strong>
            </p>
            <div class="download-buttons">



                <a href="download_attendance.php?class_id=<?php echo $class_id; ?>&section=<?php echo urlencode($section); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&format=excel" 
                   class="download-btn btn-excel"
                   download>
                    📊 Download Excel (CSV)
                </a>
                
                <a href="download_attendance.php?class_id=<?php echo $class_id; ?>&section=<?php echo urlencode($section); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&format=pdf" 
                   class="download-btn btn-pdf" 
                   target="_blank">
                    📄 Download PDF
                </a>
                
                <button onclick="window.print()" class="download-btn btn-print">
                    🖨️ Print Report
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h3>📋 Detailed Attendance Records</h3>
            

            <?php if ($attendance_records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Marked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $attendance_records->data_seek(0); // Reset pointer
                        while ($record = $attendance_records->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
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
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found for the selected date range.</div>
            <?php endif; ?>

            <a href="teacher/update_attendance_summary.php">attendance</a>
        </div>

    </div>
</body>
</html>