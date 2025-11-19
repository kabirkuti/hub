<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$department = $conn->query($dept_query)->fetch_assoc();

// Get department teachers
$teachers_query = "SELECT u.*, 
                   (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                   FROM users u
                   WHERE u.role = 'teacher' AND u.department_id = $department_id AND u.is_active = 1
                   ORDER BY u.full_name";
$teachers = $conn->query($teachers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Teachers - HOD</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 <?php echo htmlspecialchars($department['dept_name']); ?> - Teachers</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="table-container">
            <h3>👨‍🏫 Department Teachers</h3>
            
            <?php if ($teachers->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned Classes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $teacher['id']; ?></td>
                            <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                            <td><span class="badge badge-info"><?php echo $teacher['class_count']; ?> Classes</span></td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No teachers found in this department.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>