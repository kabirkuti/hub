<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class section details
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section, c.year as class_year, c.semester as class_semester
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get attendance statistics
$stats_query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance
                WHERE student_id = $student_id";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$total_days = $stats['total_days'];
$attendance_percentage = $total_days > 0 ? round(($stats['present'] / $total_days) * 100, 2) : 0;

// Display class section
$section_names = [
    'Civil' => '🗿️ Civil Engineering',
    'Mechanical' => '⚙️ Mechanical Engineering',
    'CSE-A' => '💻 Computer Science - A',
    'CSE-B' => '💻 Computer Science - B',
    'Electrical' => '⚡ Electrical Engineering'
];

$display_section = isset($section_names[$student['section']]) ? 
                   $section_names[$student['section']] : 
                   htmlspecialchars($student['section'] ?? $student['class_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student</title>
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
        
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
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
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-mini {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-mini-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-mini-label {
            font-size: 12px;
            color: #666;
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
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
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
                <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                    <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                         alt="Profile Photo" 
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">👨‍🎓</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="student">
                    <input type="hidden" name="user_id" value="<?php echo $student_id; ?>">
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
                        
                    </button>
                </form>
            </div>
            
            <h2 style="margin: 15px 0 5px 0;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p style="font-size: 18px; opacity: 0.9;">Roll No: <?php echo htmlspecialchars($student['roll_number']); ?></p>
            <p style="font-size: 16px; opacity: 0.8;"><?php echo $display_section; ?></p>
            
            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: #28a745;"><?php echo $stats['present']; ?></div>
                    <div class="stat-mini-label">Present</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: #dc3545;"><?php echo $stats['absent']; ?></div>
                    <div class="stat-mini-label">Absent</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                        <?php echo $attendance_percentage; ?>%
                    </div>
                    <div class="stat-mini-label">Attendance</div>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 25px; color: #667eea;">📋 Personal Information</h3>
            
            <div class="profile-info-grid">
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($student['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Roll Number</label>
                    <value><?php echo htmlspecialchars($student['roll_number']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($student['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($student['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($student['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Class/Section</label>
                    <value><?php echo $display_section; ?></value>
                </div>
                
                <div class="info-card">
                    <label>Academic Year</label>
                    <value><?php echo htmlspecialchars($student['year']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Semester</label>
                    <value><?php echo htmlspecialchars($student['semester']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Admission Year</label>
                    <value><?php echo htmlspecialchars($student['admission_year']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Account Status</label>
                    <value>
                        <?php if ($student['is_active']): ?>
                            <span class="badge badge-success">✅ Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Inactive</span>
                        <?php endif; ?>
                    </value>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 15px; margin-top: 20px;">
            <h3 style="margin-bottom: 20px; color: #667eea;">📊 Quick Statistics</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $total_days; ?></div>
                    <div>Total Classes</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $stats['present']; ?></div>
                    <div>Days Present</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $stats['absent']; ?></div>
                    <div>Days Absent</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $stats['late']; ?></div>
                    <div>Days Late</div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="attendance_report.php" class="btn btn-success">📊 View Detailed Report</a>
        </div>
    </div>
</body>
</html>