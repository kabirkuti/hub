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

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = sanitize($_POST['attendance_date']);
    $attendance_data = $_POST['attendance'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $status = sanitize($status);
        $remarks = isset($_POST['remarks'][$student_id]) ? sanitize($_POST['remarks'][$student_id]) : '';
        
        // Check if attendance already exists for today
        $check_query = "SELECT id FROM student_attendance 
                       WHERE student_id = ? AND class_id = ? AND attendance_date = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iis", $student_id, $class_id, $attendance_date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing attendance
            $update_query = "UPDATE student_attendance 
                           SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW()
                           WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssii", $status, $remarks, $user['id'], $existing['id']);
            
            if ($update_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new attendance
            $insert_query = "INSERT INTO student_attendance 
                           (student_id, class_id, attendance_date, status, remarks, marked_by) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iisssi", $student_id, $class_id, $attendance_date, $status, $remarks, $user['id']);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "✅ Attendance saved successfully! ($success_count students marked)";
    }
    if ($error_count > 0) {
        $error = "⚠️ Some errors occurred while saving attendance ($error_count failed)";
    }
}

// Get students for this section (by matching section name across all class IDs)
$attendance_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');

// Get all students from the same section
$students_query = "SELECT s.*, 
                   sa.status as today_status, sa.remarks as today_remarks
                   FROM students s
                   LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                       AND sa.attendance_date = ? AND sa.class_id = ?
                   WHERE s.class_id IN (SELECT id FROM classes WHERE section = ?) 
                   AND s.is_active = 1
                   ORDER BY s.roll_number";

$stmt = $conn->prepare($students_query);
$stmt->bind_param("sis", $attendance_date, $class_id, $class['section']);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo htmlspecialchars($class['section']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
    <script src="../assets/attendance.js"></script>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Mark Attendance - <?php echo htmlspecialchars($class['section']); ?></h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
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

        <div class="summary-card">
            <h2><?php echo htmlspecialchars($class['section']); ?></h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="label">📖 Class</div>
                    <div class="number" style="font-size: 16px;"><?php echo htmlspecialchars($class['class_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">🏢 Department</div>
                    <div class="number" style="font-size: 16px;"><?php echo htmlspecialchars($class['dept_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">📅 Year</div>
                    <div class="number"><?php echo $class['year']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">📆 Semester</div>
                    <div class="number"><?php echo $class['semester']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">👥 Total Students</div>
                    <div class="number"><?php echo $students->num_rows; ?></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <form method="POST" onsubmit="return validateAttendance()">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <label style="font-weight: bold; margin-right: 10px;">📅 Attendance Date:</label>
                        <input type="date" name="attendance_date" value="<?php echo $attendance_date; ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required 
                               style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"
                               onchange="this.form.submit()">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="markAll('present')" class="btn btn-success">
                            ✅ Mark All Present
                        </button>
                        <button type="button" onclick="markAll('absent')" class="btn btn-danger">
                            ❌ Mark All Absent
                        </button>
                    </div>
                </div>

                <?php if ($students->num_rows > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 15px; text-align: left;">Roll Number</th>
                                <th style="padding: 15px; text-align: left;">Student Name</th>
                                <th style="padding: 15px; text-align: center;">✅ Present</th>
                                <th style="padding: 15px; text-align: center;">❌ Absent</th>
                                <th style="padding: 15px; text-align: center;">⏰ Late</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 15px;">
                                        <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                    </td>
                                    <td style="padding: 15px;">
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn present <?php echo ($student['today_status'] == 'present') ? 'active' : ''; ?>" 
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="present" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'present') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Present
                                        </label>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn absent <?php echo ($student['today_status'] == 'absent') ? 'active' : ''; ?>"
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="absent" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'absent') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Absent
                                        </label>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn late <?php echo ($student['today_status'] == 'late') ? 'active' : ''; ?>"
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="late" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'late') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Late
                                        </label>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; text-align: center;">
                        <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 15px 50px; font-size: 16px;">
                            💾 Save Attendance
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ⚠️ No students found in section "<?php echo htmlspecialchars($class['section']); ?>". 
                        Please contact the administrator to assign students to this section.
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>💡 Important Notes</h3>
            <ul style="line-height: 2; padding-left: 20px;">
                <li><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?> - You are viewing all students enrolled in this section</li>
                <li>Click on Present/Absent/Late buttons to mark attendance</li>
                <li>Green highlighting indicates Present, Red for Absent, Yellow for Late</li>
                <li>Use quick action buttons to mark all students at once</li>
                <li>Don't forget to click "Save Attendance" when done!</li>
            </ul>
        </div>
    </div>
</body>
</html>