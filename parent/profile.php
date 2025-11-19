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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Parent</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 15px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .upload-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        
        .upload-photo-btn:hover {
            transform: scale(1.1);
            background: #218838;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }
        
        .info-card label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-card value {
            font-size: 18px;
            color: #333;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Hub - My Profile</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👨‍👩‍👦 <?php echo htmlspecialchars($parent['parent_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Profile photo updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">❌ Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-photo-container">
                <?php if (!empty($parent['photo']) && file_exists("../uploads/parents/" . $parent['photo'])): ?>
                    <img src="../uploads/parents/<?php echo htmlspecialchars($parent['photo']); ?>" 
                         alt="Profile Photo" 
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">👨‍👩‍👦</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="parent">
                    <input type="hidden" name="user_id" value="<?php echo $parent_id; ?>">
                    <input type="file" 
                           name="photo" 
                           id="photoInput" 
                           accept="image/*" 
                           style="display: none;"
                           onchange="document.getElementById('photoForm').submit();">
                    <button type="button" 
                            class="upload-photo-btn" 
                            onclick="document.getElementById('photoInput').click();"
                            title="Upload Photo">
                          📷
                    </button>
                </form>
            </div>
            
            <h2 style="margin: 15px 0 5px 0;"><?php echo htmlspecialchars($parent['parent_name']); ?></h2>
            <p style="font-size: 18px; opacity: 0.9;">Parent of: <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p style="font-size: 16px; opacity: 0.8;">Relationship: <?php echo ucfirst($parent['relationship']); ?></p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background: white; padding: 30px; border-radius: 15px;">
                <h3 style="margin-bottom: 25px; color: #667eea;">📋 My Information</h3>
                
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($parent['parent_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($parent['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($parent['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Relationship</label>
                    <value><?php echo ucfirst($parent['relationship']); ?></value>
                </div>
            </div>

            <div style="background: white; padding: 30px; border-radius: 15px;">
                <h3 style="margin-bottom: 25px; color: #667eea;">👨‍🎓 Child's Information</h3>
                
                <div class="info-card">
                    <label>Student Name</label>
                    <value><?php echo htmlspecialchars($student['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Roll Number</label>
                    <value><?php echo htmlspecialchars($student['roll_number']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email</label>
                    <value><?php echo htmlspecialchars($student['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($student['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Class</label>
                    <value><?php echo htmlspecialchars($student['class_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Year & Semester</label>
                    <value>Year <?php echo $student['year']; ?> - Semester <?php echo $student['semester']; ?></value>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="attendance_report.php" class="btn btn-success">📊 View Child's Attendance</a>
        </div>
    </div>
</body>
</html>