<?php
include 'db_connect.php';

echo "<h2>Outstanding Balance Calculation Test</h2>";

// Test with a real tenant
$tenant_query = $conn->query("
    SELECT t.*, h.house_no, h.price,
           CONCAT(t.firstname, ' ', t.lastname) as name
    FROM tenants t 
    LEFT JOIN houses h ON t.house_id = h.id 
    WHERE t.status = 1
    LIMIT 1
");

if ($tenant_query->num_rows > 0) {
    $tenant = $tenant_query->fetch_assoc();
    
    echo "<h3>Testing with Tenant: {$tenant['name']}</h3>";
    echo "<p><strong>Current Registration Date:</strong> {$tenant['date_in']}</p>";
    echo "<p><strong>Monthly Rate:</strong> ₱" . number_format($tenant['price'], 2) . "</p>";
    
    // Test current calculation logic
    echo "<h4>Current Calculation Logic:</h4>";
    
    $date_in = $tenant['date_in'];
    $price = $tenant['price'];
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<p><strong>Step 1: Date Validation</strong></p>";
    
    if (empty($date_in) || $date_in == '0000-00-00' || !DateTime::createFromFormat('Y-m-d', $date_in)) {
        echo "<p style='color: red;'>❌ Invalid registration date detected: '{$date_in}'</p>";
        echo "<p>Setting months = 0, payable = 0</p>";
        $months = 0;
        $payable = 0;
    } else {
        echo "<p style='color: green;'>✅ Valid registration date: '{$date_in}'</p>";
        
        $start_date = new DateTime($date_in);
        $current_date = new DateTime(date('Y-m-d'));
        
        echo "<p><strong>Step 2: Date Comparison</strong></p>";
        echo "<p>Registration Date: " . $start_date->format('Y-m-d') . "</p>";
        echo "<p>Current Date: " . $current_date->format('Y-m-d') . "</p>";
        
        if ($start_date > $current_date) {
            echo "<p style='color: orange;'>⚠️ Registration date is in the future</p>";
            echo "<p>Setting months = 0, payable = 0</p>";
            $months = 0;
            $payable = 0;
        } else {
            echo "<p style='color: green;'>✅ Registration date is valid (not in future)</p>";
            
            echo "<p><strong>Step 3: Month Calculation</strong></p>";
            $interval = $start_date->diff($current_date);
            $months = ($interval->y * 12) + $interval->m;
            
            echo "<p>Years difference: {$interval->y}</p>";
            echo "<p>Months difference: {$interval->m}</p>";
            echo "<p>Days difference: {$interval->d}</p>";
            echo "<p>Base months: {$months}</p>";
            
            // If we're past the day of the month when they registered, add 1 more month
            if ($current_date->format('d') >= $start_date->format('d')) {
                $months += 1;
                echo "<p>Current day ({$current_date->format('d')}) >= Registration day ({$start_date->format('d')}), adding 1 month</p>";
            } else {
                echo "<p>Current day ({$current_date->format('d')}) < Registration day ({$start_date->format('d')}), no additional month</p>";
            }
            
            echo "<p><strong>Final months owed: {$months}</strong></p>";
            
            $payable = $price * $months;
            echo "<p><strong>Step 4: Calculate Payable</strong></p>";
            echo "<p>Monthly Rate: ₱" . number_format($price, 2) . "</p>";
            echo "<p>Months Owed: {$months}</p>";
            echo "<p><strong>Total Payable: ₱" . number_format($payable, 2) . "</strong></p>";
        }
    }
    echo "</div>";
    
    // Get payments
    $payments_query = $conn->query("SELECT SUM(amount) as paid FROM payments WHERE tenant_id = " . $tenant['id']);
    $paid = $payments_query->num_rows > 0 ? $payments_query->fetch_array()['paid'] : 0;
    $paid = $paid ? $paid : 0;
    
    $outstanding = $payable - $paid;
    
    echo "<h4>Final Calculation:</h4>";
    echo "<div style='background-color: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff;'>";
    echo "<p><strong>Total Payable:</strong> ₱" . number_format($payable, 2) . "</p>";
    echo "<p><strong>Total Paid:</strong> ₱" . number_format($paid, 2) . "</p>";
    echo "<p><strong>Outstanding Balance:</strong> ₱" . number_format($outstanding, 2) . "</p>";
    echo "</div>";
    
} else {
    echo "<p>No tenants found for testing.</p>";
}

// Test with different scenarios
echo "<h3>Test Scenarios</h3>";

$test_scenarios = [
    ['date' => '2024-01-15', 'description' => 'January 2024 registration'],
    ['date' => '2024-06-01', 'description' => 'June 2024 registration'],
    ['date' => '2024-10-01', 'description' => 'October 2024 registration'],
    ['date' => '2024-10-15', 'description' => 'Mid-October 2024 registration'],
    ['date' => '0000-00-00', 'description' => 'Invalid date'],
    ['date' => '2025-01-01', 'description' => 'Future date'],
];

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Scenario</th>";
echo "<th style='padding: 8px;'>Registration Date</th>";
echo "<th style='padding: 8px;'>Current Date</th>";
echo "<th style='padding: 8px;'>Months Calculated</th>";
echo "<th style='padding: 8px;'>Expected Months</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "</tr>";

foreach ($test_scenarios as $scenario) {
    $date_in = $scenario['date'];
    $current_date_str = date('Y-m-d');
    
    // Apply our calculation logic
    if (empty($date_in) || $date_in == '0000-00-00' || !DateTime::createFromFormat('Y-m-d', $date_in)) {
        $months = 0;
        $status = "Invalid Date";
        $status_color = "red";
    } else {
        $start_date = new DateTime($date_in);
        $current_date = new DateTime($current_date_str);
        
        if ($start_date > $current_date) {
            $months = 0;
            $status = "Future Date";
            $status_color = "orange";
        } else {
            $interval = $start_date->diff($current_date);
            $months = ($interval->y * 12) + $interval->m;
            
            if ($current_date->format('d') >= $start_date->format('d')) {
                $months += 1;
            }
            
            $status = "Valid";
            $status_color = "green";
        }
    }
    
    // Calculate expected months manually for comparison
    $expected = "N/A";
    if ($date_in != '0000-00-00' && DateTime::createFromFormat('Y-m-d', $date_in)) {
        $reg_date = new DateTime($date_in);
        $today = new DateTime('2024-10-15'); // Assuming current date should be 2024
        
        if ($reg_date <= $today) {
            $diff = $reg_date->diff($today);
            $expected_months = ($diff->y * 12) + $diff->m;
            if ($today->format('d') >= $reg_date->format('d')) {
                $expected_months += 1;
            }
            $expected = $expected_months;
        }
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>{$scenario['description']}</td>";
    echo "<td style='padding: 8px;'>{$date_in}</td>";
    echo "<td style='padding: 8px;'>{$current_date_str}</td>";
    echo "<td style='padding: 8px;'>{$months}</td>";
    echo "<td style='padding: 8px;'>{$expected}</td>";
    echo "<td style='padding: 8px; color: {$status_color};'>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>System Information</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p><strong>PHP Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Server Time:</strong> " . date('c') . "</p>";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "</div>";

echo "<h3>Recommendations</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<ol>";
echo "<li>Fix invalid registration dates (0000-00-00) using the fix tool</li>";
echo "<li>Verify system date is correct (should be 2024, not 2025)</li>";
echo "<li>Test calculations with realistic registration dates</li>";
echo "</ol>";
echo "</div>";
?>
