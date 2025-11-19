<?php
require_once '../db.php';

// This script populates/updates the attendance_summary table
// Can be run manually or set up as a cron job

// Set execution time limit for large datasets
set_time_limit(300); // 5 minutes

// Start output buffering
ob_start();

echo "<h2>🔄 Attendance Summary Update Process</h2>";
echo "<p>Started at: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'attendance_summary'");
if ($table_check->num_rows === 0) {
    echo "<div style='color: red; padding: 20px; background: #f8d7da; border-radius: 10px;'>";
    echo "<h3>❌ ERROR: attendance_summary table does not exist!</h3>";
    echo "<p>Please run the SQL schema first to create the table.</p>";
    echo "<p>You can find the SQL script in the artifacts provided.</p>";
    echo "</div>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }</style>";
    ob_end_flush();
    exit;
}

$conn->begin_transaction();

try {
    // Step 1: Get current record count
    $count_query = "SELECT COUNT(*) as count FROM attendance_summary";
    $result = $conn->query($count_query);
    $old_count = $result->fetch_assoc()['count'];
    echo "<p>📊 Current records in attendance_summary: <strong>$old_count</strong></p>";
    
    // Step 2: Clear existing summary (full refresh approach)
    echo "<p>🗑️ Clearing existing summary data...</p>";
    $conn->query("DELETE FROM attendance_summary");
    echo "<p>✅ Cleared successfully</p>";
    
    // Step 3: Insert aggregated data - FIXED QUERY
    echo "<p>📥 Calculating and inserting new summary data...</p>";
    $query = "INSERT INTO attendance_summary 
              (student_id, class_id, month, year, total_days, present_days, absent_days, late_days, attendance_percentage)
              SELECT 
                  sa.student_id,
                  s.class_id,
                  MONTH(sa.attendance_date) as month,
                  YEAR(sa.attendance_date) as year,
                  COUNT(*) as total_days,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days,
                  ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
              FROM student_attendance sa
              INNER JOIN students s ON sa.student_id = s.id
              WHERE s.is_active = 1
              GROUP BY sa.student_id, s.class_id, YEAR(sa.attendance_date), MONTH(sa.attendance_date)";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Insert failed: " . $conn->error);
    }
    
    $new_count = $conn->affected_rows;
    echo "<p>✅ Inserted <strong>$new_count</strong> summary records</p>";
    
    // Step 4: Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT student_id) as unique_students,
                    COUNT(DISTINCT class_id) as unique_classes,
                    ROUND(AVG(attendance_percentage), 2) as avg_attendance,
                    MIN(attendance_percentage) as min_attendance,
                    MAX(attendance_percentage) as max_attendance
                    FROM attendance_summary";
    
    $stats_result = $conn->query($stats_query);
    
    if (!$stats_result) {
        throw new Exception("Stats query failed: " . $conn->error);
    }
    
    $stats = $stats_result->fetch_assoc();
    
    echo "<hr>";
    echo "<h3>📈 Summary Statistics</h3>";
    echo "<ul>";
    echo "<li>Total Records: <strong>{$stats['total_records']}</strong></li>";
    echo "<li>Unique Students: <strong>{$stats['unique_students']}</strong></li>";
    echo "<li>Unique Classes: <strong>{$stats['unique_classes']}</strong></li>";
    echo "<li>Average Attendance: <strong>{$stats['avg_attendance']}%</strong></li>";
    echo "<li>Min Attendance: <strong>{$stats['min_attendance']}%</strong></li>";
    echo "<li>Max Attendance: <strong>{$stats['max_attendance']}%</strong></li>";
    echo "</ul>";
    
    // Step 5: Show sample data
    echo "<hr>";
    echo "<h3>📋 Sample Records (Latest 10)</h3>";
    $sample_query = "SELECT 
                     ats.*, 
                     s.full_name, 
                     s.roll_number,
                     c.class_name,
                     c.section
                     FROM attendance_summary ats
                     JOIN students s ON ats.student_id = s.id
                     JOIN classes c ON ats.class_id = c.id
                     ORDER BY ats.last_updated DESC
                     LIMIT 10";
    
    $sample = $conn->query($sample_query);
    
    if ($sample && $sample->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>Student</th><th>Roll No</th><th>Class</th><th>Section</th><th>Month/Year</th>";
        echo "<th>Total Days</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th>";
        echo "</tr>";
        
        while ($row = $sample->fetch_assoc()) {
            $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
            $att_color = $row['attendance_percentage'] >= 75 ? 'green' : ($row['attendance_percentage'] >= 60 ? 'orange' : 'red');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['roll_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['class_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['section']) . "</td>";
            echo "<td>{$month_name} {$row['year']}</td>";
            echo "<td>{$row['total_days']}</td>";
            echo "<td style='color: green;'>{$row['present_days']}</td>";
            echo "<td style='color: red;'>{$row['absent_days']}</td>";
            echo "<td style='color: orange;'>{$row['late_days']}</td>";
            echo "<td style='color: {$att_color}; font-weight: bold;'>{$row['attendance_percentage']}%</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No sample records available yet. This could mean:</p>";
        echo "<ul>";
        echo "<li>No attendance has been marked yet</li>";
        echo "<li>All students are marked as inactive</li>";
        echo "<li>There's an issue with the data relationships</li>";
        echo "</ul>";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ Attendance summary updated successfully!</h3>";
    echo "<p style='margin: 10px 0 0 0;'>Completed at: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p style='margin: 10px 0 0 0;'><a href='index.php' style='color: #155724; font-weight: bold;'>← Return to Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    
    echo "<hr>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>❌ Error occurred!</h3>";
    echo "<p style='margin: 10px 0 0 0;'><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='margin: 10px 0 0 0;'><strong>Error Location:</strong> Line " . $e->getLine() . "</p>";
    echo "<hr style='margin: 15px 0;'>";
    echo "<h4>Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li>Ensure the attendance_summary table exists (run the SQL schema)</li>";
    echo "<li>Check that student_attendance table has data</li>";
    echo "<li>Verify all students have valid class_id assignments</li>";
    echo "<li>Check database error logs for more details</li>";
    echo "</ol>";
    echo "<p><a href='index.php' style='color: #721c24; font-weight: bold;'>← Return to Dashboard</a></p>";
    echo "</div>";
}

// Style the output
echo "<style>
    body { 
        font-family: 'Segoe UI', Arial, sans-serif; 
        padding: 20px; 
        background: #f5f5f5; 
        line-height: 1.6;
    }
    h2, h3 { 
        color: #333; 
        margin-top: 20px;
    }
    h2 {
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    p { 
        line-height: 1.8;
        margin: 10px 0;
    }
    ul, ol { 
        line-height: 1.8; 
        margin: 10px 0;
        padding-left: 30px;
    }
    table { 
        background: white; 
        margin-top: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th { 
        text-align: left;
        padding: 12px 8px !important;
    }
    td {
        padding: 10px 8px !important;
    }
    hr {
        border: none;
        border-top: 2px solid #ddd;
        margin: 30px 0;
    }
    a {
        text-decoration: none;
        transition: all 0.3s;
    }
    a:hover {
        text-decoration: underline;
    }
</style>";

ob_end_flush();
?>