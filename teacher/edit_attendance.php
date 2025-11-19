<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    header("Location: index.php");
    exit();
}

$class_id = intval($_GET['class_id']);
$edit_date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $edit_date)) {
    header("Location: index.php?error=invalid_date");
    exit();
}

// Verify this class belongs to the teacher
$class_query = "SELECT c.*, d.dept_name FROM classes c 
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("ii", $class_id, $teacher_id);
$stmt->execute();
$class_result = $stmt->get_result();

if ($class_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

$class = $class_result->fetch_assoc();

// Check if attendance exists for this date
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_attendance 
                               WHERE class_id = ? AND attendance_date = ?");
$check_stmt->bind_param("is", $class_id, $edit_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$attendance_exists = $check_result->fetch_assoc()['count'] > 0;

if (!$attendance_exists) {
    header("Location: mark_attendance.php?class_id=$class_id&error=no_attendance_found");
    exit();
}

// Get students with attendance for selected date
$students_query = "SELECT s.*, sa.status, sa.remarks 
                   FROM students s
                   LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                   AND sa.class_id = ? AND sa.attendance_date = ?
                   WHERE s.class_id = ? AND s.is_active = 1 
                   ORDER BY s.roll_number";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("isi", $class_id, $edit_date, $class_id);
$students_stmt->execute();
$students = $students_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - Teacher</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .attendance-form { max-width: 1200px; margin: 0 auto; }
        .student-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }
        .student-row:hover { background: #f8f9fa; }
        .student-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .student-row input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .student-row input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .present-label:has(input:checked) { background: #d4edda; color: #155724; }
        .absent-label:has(input:checked) { background: #f8d7da; color: #721c24; }
        .late-label:has(input:checked) { background: #fff3cd; color: #856404; }
        .header-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            padding: 15px;
            background: #667eea;
            color: white;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - Edit Attendance</h1>
        </div>
        <div class="user-info">
            <a href="view_attendance.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($class['dept_name']); ?></p>
            <p><strong>Editing Date:</strong> <?php echo date('l, d F Y', strtotime($edit_date)); ?></p>
            
            <div class="alert alert-warning">
                ⚠️ You are editing attendance for <?php echo date('d M Y', strtotime($edit_date)); ?>. Changes will update the existing records.
            </div>
        </div>

        <div class="quick-actions">
            <button type="button" onclick="markAll('present')" class="btn btn-success">✅ Mark All Present</button>
            <button type="button" onclick="markAll('absent')" class="btn btn-danger">❌ Mark All Absent</button>
        </div>

        <form method="POST" action="save_attendance.php" class="attendance-form" onsubmit="return confirm('Are you sure you want to update this attendance?');">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $edit_date; ?>">
            
            <div class="table-container" style="padding: 0;">
                <div class="header-row">
                    <div>Roll No</div>
                    <div>Student Name</div>
                    <div>Present</div>
                    <div>Absent</div>
                    <div>Late</div>
                    <div>Remarks</div>
                </div>
                
                <?php while ($student = $students->fetch_assoc()): 
                    $student_id = $student['id'];
                    $status = $student['status'] ?? 'present';
                    $remarks = $student['remarks'] ?? '';
                ?>
                <div class="student-row">
                    <div><?php echo htmlspecialchars($student['roll_number']); ?></div>
                    <div><?php echo htmlspecialchars($student['full_name']); ?></div>
                    
                    <div>
                        <label class="present-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="present" 
                                   <?php echo $status === 'present' ? 'checked' : ''; ?>>
                            Present
                        </label>
                    </div>
                    
                    <div>
                        <label class="absent-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="absent"
                                   <?php echo $status === 'absent' ? 'checked' : ''; ?>>
                            Absent
                        </label>
                    </div>
                    
                    <div>
                        <label class="late-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="late"
                                   <?php echo $status === 'late' ? 'checked' : ''; ?>>
                            Late
                        </label>
                    </div>
                    
                    <div>
                        <input type="text" 
                               name="remarks[<?php echo $student_id; ?>]" 
                               placeholder="Optional remarks"
                               value="<?php echo htmlspecialchars($remarks); ?>">
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 50px; font-size: 16px;">
                    💾 Update Attendance
                </button>
            </div>
        </form>
    </div>

    <script>
    function markAll(status) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
        radios.forEach(radio => {
            radio.checked = true;
        });
    }
    </script>
</body>
</html>