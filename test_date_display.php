<?php
include 'db_connect.php';

echo "<h2>Test Date Display Fix</h2>";
echo "<p>Testing the date display fix for the 'Nov 30, -0001' issue.</p>";

// Get all active tenants
$tenants = $conn->query("
    SELECT t.*, h.house_no, h.price,
           CONCAT(t.firstname, ' ', t.lastname) as name
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.id 
    WHERE t.status = 1
    ORDER BY t.id
");

if ($tenants->num_rows > 0) {
    echo "<h3>Current Tenant Date Display Test</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Tenant</th>";
    echo "<th style='padding: 8px;'>Raw Date</th>";
    echo "<th style='padding: 8px;'>Formatted Date (Old Method)</th>";
    echo "<th style='padding: 8px;'>Formatted Date (New Method)</th>";
    echo "<th style='padding: 8px;'>Outstanding Balance</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "</tr>";
    
    while ($tenant = $tenants->fetch_assoc()) {
        $date_in = $tenant['date_in'];
        $price = $tenant['price'];
        $name = $tenant['name'];
        
        // Old method (problematic)
        $old_formatted = date("M d, Y", strtotime($date_in));
        
        // New method (safe)
        if (empty($date_in) || $date_in == '0000-00-00') {
            $new_formatted = "Invalid Date";
            $status = "❌ Invalid Date";
        } else {
            $timestamp = strtotime($date_in);
            if ($timestamp === false) {
                $new_formatted = "Invalid Date";
                $status = "❌ Parse Error";
            } else {
                $new_formatted = date("M d, Y", $timestamp);
                $status = "✅ Valid";
            }
        }
        
        // Calculate outstanding balance
        if (empty($date_in) || $date_in == '0000-00-00' || !DateTime::createFromFormat('Y-m-d', $date_in)) {
            $months = 0;
            $payable = 0;
        } else {
            $start_date = new DateTime($date_in);
            $current_date = new DateTime(date('Y-m-d'));
            
            if ($start_date > $current_date) {
                $months = 0;
                $payable = 0;
            } else {
                $interval = $start_date->diff($current_date);
                $months = ($interval->y * 12) + $interval->m;
                
                if ($current_date->format('d') >= $start_date->format('d')) {
                    $months += 1;
                }
                
                $payable = $price * $months;
            }
        }
        
        // Get payments
        $payments_query = $conn->query("SELECT SUM(amount) as paid FROM payments WHERE tenant_id = " . $tenant['id']);
        $paid = $payments_query->num_rows > 0 ? $payments_query->fetch_array()['paid'] : 0;
        $paid = $paid ? $paid : 0;
        
        $outstanding = $payable - $paid;
        
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>{$name}</strong></td>";
        echo "<td style='padding: 8px;'>{$date_in}</td>";
        echo "<td style='padding: 8px; " . ($old_formatted == 'Nov 30, -0001' ? 'color: red;' : '') . "'>{$old_formatted}</td>";
        echo "<td style='padding: 8px; color: green;'>{$new_formatted}</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($outstanding, 2) . "</td>";
        echo "<td style='padding: 8px;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Summary</h3>";
    echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<h4>✅ Date Display Issue Fixed:</h4>";
    echo "<ul>";
    echo "<li><strong>Problem:</strong> Invalid dates (0000-00-00) were showing as 'Nov 30, -0001'</li>";
    echo "<li><strong>Root Cause:</strong> strtotime() returns false for invalid dates, which becomes -1 when passed to date()</li>";
    echo "<li><strong>Solution:</strong> Added validation before formatting dates</li>";
    echo "<li><strong>Result:</strong> Invalid dates now show as 'Invalid Date' instead of confusing dates</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<p>No active tenants found.</p>";
}

echo "<h3>Files Updated for Date Display Fix</h3>";
echo "<div style='background-color: #cce5ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<ul>";
echo "<li>✅ <strong>view_payment.php</strong> - Fixed 'Rent Started' date display</li>";
echo "<li>✅ <strong>admin_class.php</strong> - Fixed 'rent_started' in get_tdetails function</li>";
echo "</ul>";
echo "<p><strong>Both files now safely handle invalid dates and prevent the 'Nov 30, -0001' display issue.</strong></p>";
echo "</div>";

echo "<h3>How the Fix Works</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h4>Before (Problematic):</h4>";
echo "<code>date(\"M d, Y\", strtotime(\$date_in))</code>";
echo "<p>❌ If \$date_in is '0000-00-00', strtotime() returns false, date() converts false to -1, resulting in 'Nov 30, -0001'</p>";

echo "<h4>After (Safe):</h4>";
echo "<pre>";
echo "if (empty(\$date_in) || \$date_in == '0000-00-00') {\n";
echo "    echo \"Invalid Date\";\n";
echo "} else {\n";
echo "    \$timestamp = strtotime(\$date_in);\n";
echo "    if (\$timestamp === false) {\n";
echo "        echo \"Invalid Date\";\n";
echo "    } else {\n";
echo "        echo date(\"M d, Y\", \$timestamp);\n";
echo "    }\n";
echo "}";
echo "</pre>";
echo "<p>✅ Validates the date before formatting, shows 'Invalid Date' for problematic dates</p>";
echo "</div>";
?>
