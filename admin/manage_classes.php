<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        $class_name = sanitize($_POST['class_name']);
        $department_id = intval($_POST['department_id']);
        $year = intval($_POST['year']);
        $section = sanitize($_POST['section']);
        $teacher_id = intval($_POST['teacher_id']);
        $semester = intval($_POST['semester']);
        $academic_year = sanitize($_POST['academic_year']);
        
        // Always create a new class entry (allows same section with different teachers)
        $stmt = $conn->prepare("INSERT INTO classes (class_name, department_id, year, section, teacher_id, semester, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisiss", $class_name, $department_id, $year, $section, $teacher_id, $semester, $academic_year);
        
        if ($stmt->execute()) {
            $success = "Class added successfully!";
        } else {
            $error = "Error adding class: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_class'])) {
        $class_id = intval($_POST['class_id']);
        
        if ($conn->query("DELETE FROM classes WHERE id = $class_id")) {
            $success = "Class deleted successfully!";
        } else {
            $error = "Error deleting class: " . $conn->error;
        }
    }
}

// Get all classes grouped by section
$classes_query = "SELECT c.*, d.dept_name, u.full_name as teacher_name,
                  (SELECT COUNT(DISTINCT s.id) FROM students s WHERE s.class_id = c.id OR s.class_id IN (SELECT c2.id FROM classes c2 WHERE c2.section = c.section AND c2.year = c.year AND c2.semester = c.semester)) as student_count
                  FROM classes c
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN users u ON c.teacher_id = u.id
                  ORDER BY c.section, c.year, c.semester, u.full_name";
$classes = $conn->query($classes_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get teachers
$teachers = $conn->query("SELECT id, full_name, department_id FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script>
        // Auto-fill class name based on selections
        function updateClassName() {
            const year = document.querySelector('select[name="year"]').value;
            const section = document.querySelector('select[name="section"]').value;
            const teacher = document.querySelector('select[name="teacher_id"]');
            const teacherName = teacher.options[teacher.selectedIndex].text;
            const classNameInput = document.querySelector('input[name="class_name"]');
            
            if (year && section && teacher.value) {
                const sectionNames = {
                    'Civil': 'Civil Engineering',
                    'Mechanical': 'Mechanical Engineering',
                    'CSE-A': 'Computer Science & Engineering - A',
                    'CSE-B': 'Computer Science & Engineering - B',
                    'Electrical': 'Electrical Engineering',
                    'IT': 'IT',
                    'B': 'Section B',
                    'C': 'Section C',
                    'D': 'Section D'
                };
                
                const yearMap = {
                    '1': '1st Year',
                    '2': '2nd Year',
                    '3': '3rd Year',
                    '4': '4th Year'
                };
                
                const sectionName = sectionNames[section] || section;
                const yearName = yearMap[year] || year + ' Year';
                
                classNameInput.value = `${sectionName} - ${yearName} (${teacherName})`;
            }
        }
    </script>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Manage Classes & Teacher Assignments</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
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

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>➕ Add Class with Teacher Assignment</h3>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>📌 Note:</strong> You can add the same section multiple times with different teachers. This allows multiple teachers to teach the same class (different subjects).
            </div>
            <form method="POST" style="max-width: 900px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Class Name:</label>
                    <input type="text" name="class_name" required placeholder="Will be auto-filled based on section, year and teacher">
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <select name="department_id" required id="department_select">
                        <option value="">-- Select Department --</option>
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Section:</label>
                    <select name="section" required onchange="updateClassName()">
                        <option value="">-- Select Section --</option>
                        <option value="Civil">Civil Engineering</option>
                        
                        <option value="Mechanical">Mechanical Engineering</option>
                        <option value="CSE-A">Computer Science & Engineering - A</option>
                        <option value="CSE-B">Computer Science & Engineering - B</option>
                        <option value="Electrical">Electrical Engineering</option>
                        <option value="" disabled>──────────────</option>
                        <option value="IT">IT</option>
                        <option value="B">Section B</option>
                        <option value="C">Section C</option>
                        <option value="D">Section D</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Year:</label>
                    <select name="year" required onchange="updateClassName()">
                        <option value="">-- Select --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Semester:</label>
                    <select name="semester" required>
                        <option value="">-- Select --</option>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Academic Year:</label>
                    <input type="text" name="academic_year" required placeholder="e.g., 2024-25">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Assign Teacher:</label>
                    <select name="teacher_id" required onchange="updateClassName()">
                        <option value="">-- Select Teacher --</option>
                        <?php 
                        $departments->data_seek(0);
                        while ($teacher = $teachers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_class" class="btn btn-primary">➕ Add Class with Teacher</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>📚 All Classes & Teacher Assignments</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Teacher</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_section = '';
                    $section_color = '';
                    while ($class = $classes->fetch_assoc()): 
                        // Highlight same sections with different background
                        $section_key = $class['section'] . '-' . $class['year'] . '-' . $class['semester'];
                        if ($section_key != $current_section) {
                            $current_section = $section_key;
                            $section_color = ($section_color == '#f8f9fa') ? '#ffffff' : '#f8f9fa';
                        }
                    ?>
                    <tr style="background: <?php echo $section_color; ?>">
                        <td><?php echo $class['id']; ?></td>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['dept_name']); ?></td>
                        <td><?php echo $class['year']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                        <td><?php echo $class['semester']; ?></td>
                        <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                        <td><strong><?php echo htmlspecialchars($class['teacher_name']); ?></strong></td>
                        <td><span class="badge badge-success"><?php echo $class['student_count']; ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this class assignment?');">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <strong>💡 Tip:</strong> Classes with the same section, year, and semester are grouped with alternating background colors. Each row represents one teacher teaching that class.
            </div>
        </div>
    </div>
</body>
</html>