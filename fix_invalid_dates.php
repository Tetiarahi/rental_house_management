<?php
include 'db_connect.php';

echo "<h2>Fix Invalid Registration Dates</h2>";
echo "<p>This script identifies and fixes invalid registration dates (0000-00-00) in the database.</p>";

// Check for invalid dates
$invalid_dates_query = $conn->query("
    SELECT t.*, h.house_no, h.price,
           CONCAT(t.firstname, ' ', t.lastname) as name
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.id 
    WHERE t.date_in = '0000-00-00' OR t.date_in IS NULL OR t.date_in = ''
    AND t.status = 1
");

echo "<h3>Tenants with Invalid Registration Dates</h3>";

if ($invalid_dates_query->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8d7da;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Name</th>";
    echo "<th style='padding: 8px;'>House</th>";
    echo "<th style='padding: 8px;'>Current Date</th>";
    echo "<th style='padding: 8px;'>Issue</th>";
    echo "<th style='padding: 8px;'>Suggested Fix</th>";
    echo "</tr>";
    
    $fixes_needed = [];
    
    while ($row = $invalid_dates_query->fetch_assoc()) {
        $suggested_date = date('Y-m-d'); // Default to current date
        
        // Try to find a reasonable date based on payments
        $payment_query = $conn->query("
            SELECT MIN(date_created) as first_payment 
            FROM payments 
            WHERE tenant_id = " . $row['id']
        );
        
        if ($payment_query->num_rows > 0) {
            $payment_row = $payment_query->fetch_assoc();
            if (!empty($payment_row['first_payment'])) {
                // Use first payment date as registration date
                $suggested_date = date('Y-m-d', strtotime($payment_row['first_payment']));
            }
        }
        
        $fixes_needed[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'suggested_date' => $suggested_date
        ];
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['id']}</td>";
        echo "<td style='padding: 8px;'>{$row['name']}</td>";
        echo "<td style='padding: 8px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px; color: red;'>Invalid Date</td>";
        echo "<td style='padding: 8px; color: blue;'>{$suggested_date}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Provide fix options
    echo "<h3>Fix Options</h3>";
    echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<p><strong>Choose how to fix these invalid dates:</strong></p>";
    
    echo "<form method='POST' style='margin: 10px 0;'>";
    echo "<input type='hidden' name='action' value='fix_dates'>";
    
    foreach ($fixes_needed as $fix) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
        echo "<strong>{$fix['name']} (ID: {$fix['id']})</strong><br>";
        echo "<label>";
        echo "<input type='radio' name='fix_{$fix['id']}' value='suggested' checked> ";
        echo "Use suggested date: {$fix['suggested_date']}";
        echo "</label><br>";
        echo "<label>";
        echo "<input type='radio' name='fix_{$fix['id']}' value='custom'> ";
        echo "Use custom date: <input type='date' name='custom_{$fix['id']}' value='{$fix['suggested_date']}'>";
        echo "</label><br>";
        echo "<label>";
        echo "<input type='radio' name='fix_{$fix['id']}' value='skip'> ";
        echo "Skip this tenant";
        echo "</label>";
        echo "</div>";
    }
    
    echo "<button type='submit' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
    echo "Apply Fixes";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    
} else {
    echo "<p style='color: green;'>✅ No invalid registration dates found!</p>";
}

// Handle form submission
if ($_POST['action'] == 'fix_dates') {
    echo "<h3>Applying Fixes...</h3>";
    
    $fixes_applied = 0;
    $fixes_skipped = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'fix_') === 0) {
            $tenant_id = str_replace('fix_', '', $key);
            
            if ($value == 'suggested') {
                // Use the suggested date logic
                $suggested_date = date('Y-m-d');
                
                $payment_query = $conn->query("
                    SELECT MIN(date_created) as first_payment 
                    FROM payments 
                    WHERE tenant_id = " . intval($tenant_id)
                );
                
                if ($payment_query->num_rows > 0) {
                    $payment_row = $payment_query->fetch_assoc();
                    if (!empty($payment_row['first_payment'])) {
                        $suggested_date = date('Y-m-d', strtotime($payment_row['first_payment']));
                    }
                }
                
                $update_query = $conn->prepare("UPDATE tenants SET date_in = ? WHERE id = ?");
                $update_query->bind_param("si", $suggested_date, $tenant_id);
                
                if ($update_query->execute()) {
                    echo "<p style='color: green;'>✅ Updated tenant ID {$tenant_id} with date: {$suggested_date}</p>";
                    $fixes_applied++;
                } else {
                    echo "<p style='color: red;'>❌ Failed to update tenant ID {$tenant_id}</p>";
                }
                
            } elseif ($value == 'custom') {
                $custom_date = $_POST['custom_' . $tenant_id];
                
                if (!empty($custom_date) && DateTime::createFromFormat('Y-m-d', $custom_date)) {
                    $update_query = $conn->prepare("UPDATE tenants SET date_in = ? WHERE id = ?");
                    $update_query->bind_param("si", $custom_date, $tenant_id);
                    
                    if ($update_query->execute()) {
                        echo "<p style='color: green;'>✅ Updated tenant ID {$tenant_id} with custom date: {$custom_date}</p>";
                        $fixes_applied++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update tenant ID {$tenant_id}</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ Invalid custom date for tenant ID {$tenant_id}, skipped</p>";
                    $fixes_skipped++;
                }
                
            } else {
                echo "<p style='color: blue;'>ℹ️ Skipped tenant ID {$tenant_id}</p>";
                $fixes_skipped++;
            }
        }
    }
    
    echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
    echo "<h4>Summary:</h4>";
    echo "<ul>";
    echo "<li>Fixes applied: {$fixes_applied}</li>";
    echo "<li>Fixes skipped: {$fixes_skipped}</li>";
    echo "</ul>";
    echo "<p><strong>Recommendation:</strong> Refresh the page to see updated calculations.</p>";
    echo "</div>";
}

// Show current status after any fixes
echo "<h3>Current Registration Date Status</h3>";
$all_tenants = $conn->query("
    SELECT t.*, h.house_no, h.price,
           CONCAT(t.firstname, ' ', t.lastname) as name
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.id 
    WHERE t.status = 1
    ORDER BY t.date_in DESC
");

if ($all_tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #e9ecef;'>";
    echo "<th style='padding: 8px;'>Tenant</th>";
    echo "<th style='padding: 8px;'>House</th>";
    echo "<th style='padding: 8px;'>Registration Date</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Days Since Registration</th>";
    echo "</tr>";
    
    while ($row = $all_tenants->fetch_assoc()) {
        $status = "Valid";
        $status_color = "green";
        $days_since = "N/A";
        
        if ($row['date_in'] == '0000-00-00' || empty($row['date_in'])) {
            $status = "Invalid";
            $status_color = "red";
        } else {
            $reg_date = new DateTime($row['date_in']);
            $current_date = new DateTime();
            $days_since = $current_date->diff($reg_date)->days;
            
            if ($reg_date > $current_date) {
                $status = "Future Date";
                $status_color = "orange";
            }
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['name']}</td>";
        echo "<td style='padding: 8px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px; color: {$status_color};'>{$status}</td>";
        echo "<td style='padding: 8px;'>{$days_since}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Next Steps</h3>";
echo "<div style='background-color: #cce5ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<ol>";
echo "<li>Fix any remaining invalid registration dates above</li>";
echo "<li>Test the outstanding balance calculations</li>";
echo "<li>Verify the calculations are now accurate</li>";
echo "<li>Consider implementing better validation for future data entry</li>";
echo "</ol>";
echo "</div>";
?>
