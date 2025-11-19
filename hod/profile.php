<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$hod_id = $user['id'];
$department_id = $_SESSION['department_id'];

// Get HOD's full information including photo
$hod_query = "SELECT u.*, d.dept_name,
              (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND department_id = u.department_id AND is_active = 1) as teacher_count,
              (SELECT COUNT(*) FROM students WHERE department_id = u.department_id AND is_active = 1) as student_count,
              (SELECT COUNT(*) FROM classes WHERE department_id = u.department_id) as class_count
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.id
              WHERE u.id = $hod_id";
$hod = $conn->query($hod_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HOD</title>
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
        
        .stat-mini {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #667eea;
        }
        
        .stat-mini-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-mini-label {
            font-size: 14px;
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
            <span>👔 <?php echo htmlspecialchars($hod['full_name']); ?></span>
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
                <?php 
                // Check if photo exists
                $photo_exists = false;
                $photo_src = '';
                
                if (!empty($hod['photo'])) {
                    // Try both possible paths
                    if (file_exists("../uploads/hods/" . $hod['photo'])) {
                        $photo_exists = true;
                        $photo_src = "../uploads/hods/" . htmlspecialchars($hod['photo']);
                    } elseif (file_exists("../" . $hod['photo'])) {
                        $photo_exists = true;
                        $photo_src = "../" . htmlspecialchars($hod['photo']);
                    }
                }
                
                if ($photo_exists): 
                ?>
                    <img src="<?php echo $photo_src; ?>" 
                         alt="Profile Photo" 
                         class="profile-photo"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="profile-photo-placeholder" style="display: none;">👔</div>
                <?php else: ?>
                    <div class="profile-photo-placeholder">👔</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="hod">
                    <input type="hidden" name="user_id" value="<?php echo $hod_id; ?>">
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
            
            <h2 style="margin: 15px 0 5px 0;"><?php echo htmlspecialchars($hod['full_name']); ?></h2>
            <p style="font-size: 18px; opacity: 0.9;">Head of Department</p>
            <p style="font-size: 16px; opacity: 0.8;"><?php echo htmlspecialchars($hod['dept_name']); ?></p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['teacher_count']; ?></div>
                    <div class="stat-mini-label">Teachers</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['student_count']; ?></div>
                    <div class="stat-mini-label">Students</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['class_count']; ?></div>
                    <div class="stat-mini-label">Classes</div>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 25px; color: #667eea;">📋 Personal Information</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($hod['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Username</label>
                    <value><?php echo htmlspecialchars($hod['username']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($hod['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($hod['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Role</label>
                    <value>Head of Department (HOD)</value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($hod['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Account Status</label>
                    <value>
                        <?php if ($hod['is_active']): ?>
                            <span class="badge badge-success">✅ Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Inactive</span>
                        <?php endif; ?>
                    </value>
                </div>
                
                <div class="info-card">
                    <label>Member Since</label>
                    <value><?php echo date('F Y', strtotime($hod['created_at'])); ?></value>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 15px; margin-top: 20px;">
            <h3 style="margin-bottom: 20px; color: #667eea;">📊 Department Overview</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $hod['teacher_count']; ?></div>
                    <div>Active Teachers</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $hod['student_count']; ?></div>
                    <div>Enrolled Students</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 36px; font-weight: bold;"><?php echo $hod['class_count']; ?></div>
                    <div>Total Classes</div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="view_teachers.php" class="btn btn-success">👨‍🏫 View Teachers</a>
            <a href="view_students.php" class="btn btn-info">👨‍🎓 View Students</a>
        </div>
    </div>
</body>
</html>