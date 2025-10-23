<?php
include 'db_connect.php';

echo "<h2>Registration Date Validation and Fix Tool</h2>";
echo "<p>This tool helps identify and fix problematic registration dates that affect outstanding balance calculations.</p>";

// Check for problematic dates
echo "<h3>1. Checking for Problematic Registration Dates</h3>";

// Future dates
$future_dates_query = $conn->query("SELECT t.*, h.house_no FROM tenants t LEFT JOIN houses h ON t.house_id = h.id WHERE t.date_in > CURDATE() AND t.status = 1");
echo "<h4>Tenants with Future Registration Dates:</h4>";
if ($future_dates_query->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f8d7da;'>";
    echo "<th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Name</th><th style='padding: 8px;'>House</th><th style='padding: 8px;'>Registration Date</th><th style='padding: 8px;'>Issue</th>";
    echo "</tr>";
    
    while ($row = $future_dates_query->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['id']}</td>";
        echo "<td style='padding: 8px;'>{$row['firstname']} {$row['lastname']}</td>";
        echo "<td style='padding: 8px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px; color: red;'>Future Date</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ No future registration dates found.</p>";
}

// Very old dates (before 2020)
$old_dates_query = $conn->query("SELECT t.*, h.house_no FROM tenants t LEFT JOIN houses h ON t.house_id = h.id WHERE t.date_in < '2020-01-01' AND t.status = 1");
echo "<h4>Tenants with Very Old Registration Dates (Before 2020):</h4>";
if ($old_dates_query->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #fff3cd;'>";
    echo "<th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Name</th><th style='padding: 8px;'>House</th><th style='padding: 8px;'>Registration Date</th><th style='padding: 8px;'>Years Ago</th>";
    echo "</tr>";
    
    while ($row = $old_dates_query->fetch_assoc()) {
        $years_ago = (new DateTime())->diff(new DateTime($row['date_in']))->y;
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['id']}</td>";
        echo "<td style='padding: 8px;'>{$row['firstname']} {$row['lastname']}</td>";
        echo "<td style='padding: 8px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px; color: orange;'>{$years_ago} years</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ No unusually old registration dates found.</p>";
}

// Duplicate registration dates
$duplicate_dates_query = $conn->query("
    SELECT date_in, COUNT(*) as count, GROUP_CONCAT(CONCAT(firstname, ' ', lastname) SEPARATOR ', ') as tenants 
    FROM tenants 
    WHERE status = 1 
    GROUP BY date_in 
    HAVING count > 1
");
echo "<h4>Duplicate Registration Dates:</h4>";
if ($duplicate_dates_query->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #d1ecf1;'>";
    echo "<th style='padding: 8px;'>Registration Date</th><th style='padding: 8px;'>Number of Tenants</th><th style='padding: 8px;'>Tenant Names</th>";
    echo "</tr>";
    
    while ($row = $duplicate_dates_query->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px;'>{$row['count']}</td>";
        echo "<td style='padding: 8px;'>{$row['tenants']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: blue;'>ℹ️ Multiple tenants with the same registration date might indicate data entry issues.</p>";
} else {
    echo "<p style='color: green;'>✅ No duplicate registration dates found.</p>";
}

// Show current outstanding balance calculations
echo "<h3>2. Current Outstanding Balance Analysis</h3>";
$tenants_analysis = $conn->query("
    SELECT t.*, h.house_no, h.price, 
           CONCAT(t.firstname, ' ', t.lastname) as name
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.id 
    WHERE t.status = 1 
    ORDER BY t.date_in DESC
");

if ($tenants_analysis->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #e9ecef;'>";
    echo "<th style='padding: 8px;'>Tenant</th>";
    echo "<th style='padding: 8px;'>House</th>";
    echo "<th style='padding: 8px;'>Registration Date</th>";
    echo "<th style='padding: 8px;'>Monthly Rate</th>";
    echo "<th style='padding: 8px;'>Months Owed</th>";
    echo "<th style='padding: 8px;'>Total Payable</th>";
    echo "<th style='padding: 8px;'>Total Paid</th>";
    echo "<th style='padding: 8px;'>Outstanding Balance</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "</tr>";
    
    while ($row = $tenants_analysis->fetch_assoc()) {
        // Calculate months using the corrected method
        $start_date = new DateTime($row['date_in']);
        $current_date = new DateTime(date('Y-m-d'));
        $interval = $start_date->diff($current_date);
        $months = ($interval->y * 12) + $interval->m;

        // If we're past the day of the month when they registered, add 1 more month
        if ($current_date->format('d') >= $start_date->format('d')) {
            $months += 1;
        }

        $payable = $row['price'] * $months;
        
        // Get total paid
        $paid_query = $conn->query("SELECT SUM(amount) as paid FROM payments WHERE tenant_id = " . $row['id']);
        $paid = $paid_query->num_rows > 0 ? $paid_query->fetch_array()['paid'] : 0;
        $outstanding = $payable - $paid;
        
        // Determine status
        $status = "Normal";
        $status_color = "black";
        
        if ($outstanding < 0) {
            $status = "Overpaid";
            $status_color = "blue";
        } elseif ($outstanding > ($row['price'] * 3)) {
            $status = "High Debt";
            $status_color = "red";
        } elseif ($outstanding == 0) {
            $status = "Paid Up";
            $status_color = "green";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['name']}</td>";
        echo "<td style='padding: 8px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 8px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td style='padding: 8px;'>{$months}</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($payable, 2) . "</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($paid, 2) . "</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($outstanding, 2) . "</td>";
        echo "<td style='padding: 8px; color: {$status_color};'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Recommendations
echo "<h3>3. Recommendations</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>To Fix Registration Date Issues:</h4>";
echo "<ol>";
echo "<li><strong>Review Future Dates:</strong> Update any registration dates that are in the future</li>";
echo "<li><strong>Verify Old Dates:</strong> Confirm very old registration dates are accurate</li>";
echo "<li><strong>Check Duplicates:</strong> Review tenants with identical registration dates</li>";
echo "<li><strong>Validate Data Entry:</strong> Ensure new tenant registration dates are accurate</li>";
echo "<li><strong>Regular Audits:</strong> Periodically check for data inconsistencies</li>";
echo "</ol>";
echo "</div>";

echo "<h3>4. System Improvements Made</h3>";
echo "<div style='background-color: #cce5ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>Recent Fixes Applied:</h4>";
echo "<ul>";
echo "<li>✅ Added default value (today's date) for new tenant registration</li>";
echo "<li>✅ Added max date validation (cannot select future dates)</li>";
echo "<li>✅ Added server-side validation for registration dates</li>";
echo "<li>✅ Added client-side validation to prevent future dates</li>";
echo "<li>✅ Improved month calculation logic for outstanding balance</li>";
echo "<li>✅ Added helpful text to guide users</li>";
echo "</ul>";
echo "</div>";

echo "<h3>5. Testing the Fix</h3>";
echo "<p>To test the registration date improvements:</p>";
echo "<ol>";
echo "<li>Try creating a new tenant - should default to today's date</li>";
echo "<li>Try selecting a future date - should show error message</li>";
echo "<li>Check outstanding balance calculations - should be more accurate</li>";
echo "<li>Review existing tenant data for any anomalies</li>";
echo "</ol>";
?>
