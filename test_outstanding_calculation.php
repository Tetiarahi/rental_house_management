<?php
include 'db_connect.php';

echo "<h2>Outstanding Balance Calculation Test</h2>";
echo "<p>This test shows the corrected outstanding balance calculation.</p>";

// Test with sample data
$test_cases = [
    ['date_in' => '2024-01-15', 'price' => 1000, 'description' => 'Registered Jan 15, 2024'],
    ['date_in' => '2023-06-01', 'price' => 1500, 'description' => 'Registered Jun 1, 2023'],
    ['date_in' => '2024-10-01', 'price' => 2000, 'description' => 'Registered Oct 1, 2024'],
];

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Description</th>";
echo "<th style='padding: 10px;'>Registration Date</th>";
echo "<th style='padding: 10px;'>Monthly Rate</th>";
echo "<th style='padding: 10px;'>Months Owed</th>";
echo "<th style='padding: 10px;'>Total Payable</th>";
echo "<th style='padding: 10px;'>Calculation Details</th>";
echo "</tr>";

foreach ($test_cases as $case) {
    $date_in = $case['date_in'];
    $price = $case['price'];
    $description = $case['description'];
    
    // Calculate months from registration date to current date
    $start_date = new DateTime($date_in);
    $current_date = new DateTime(date('Y-m-d'));
    $interval = $start_date->diff($current_date);
    $months = ($interval->y * 12) + $interval->m;

    // If we're past the day of the month when they registered, add 1 more month
    if ($current_date->format('d') >= $start_date->format('d')) {
        $months += 1;
    }

    $payable = $price * $months;
    
    $calculation_details = "Years: {$interval->y}, Months: {$interval->m}, ";
    $calculation_details .= "Current day: {$current_date->format('d')}, Registration day: {$start_date->format('d')}";
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>{$description}</td>";
    echo "<td style='padding: 10px;'>{$date_in}</td>";
    echo "<td style='padding: 10px;'>₱" . number_format($price, 2) . "</td>";
    echo "<td style='padding: 10px;'>{$months} months</td>";
    echo "<td style='padding: 10px;'>₱" . number_format($payable, 2) . "</td>";
    echo "<td style='padding: 10px; font-size: 12px;'>{$calculation_details}</td>";
    echo "</tr>";
}

echo "</table>";

// Test with actual tenant data
echo "<h3>Actual Tenant Data</h3>";
$tenants = $conn->query("SELECT t.*,concat(t.lastname,', ',t.firstname,' ',t.middlename) as name,h.house_no,h.price FROM tenants t inner join houses h on h.id = t.house_id where t.status = 1 order by h.house_no desc LIMIT 5");

if ($tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Tenant</th>";
    echo "<th style='padding: 10px;'>House #</th>";
    echo "<th style='padding: 10px;'>Registration Date</th>";
    echo "<th style='padding: 10px;'>Monthly Rate</th>";
    echo "<th style='padding: 10px;'>Months Owed</th>";
    echo "<th style='padding: 10px;'>Total Payable</th>";
    echo "<th style='padding: 10px;'>Total Paid</th>";
    echo "<th style='padding: 10px;'>Outstanding Balance</th>";
    echo "</tr>";
    
    while ($row = $tenants->fetch_assoc()) {
        // Calculate months from registration date to current date
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
        $paid_query = $conn->query("SELECT SUM(amount) as paid FROM payments where tenant_id = " . $row['id']);
        $paid = $paid_query->num_rows > 0 ? $paid_query->fetch_array()['paid'] : 0;
        $outstanding = $payable - $paid;
        
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . ucwords($row['name']) . "</td>";
        echo "<td style='padding: 10px;'>{$row['house_no']}</td>";
        echo "<td style='padding: 10px;'>{$row['date_in']}</td>";
        echo "<td style='padding: 10px;'>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td style='padding: 10px;'>{$months} months</td>";
        echo "<td style='padding: 10px;'>₱" . number_format($payable, 2) . "</td>";
        echo "<td style='padding: 10px;'>₱" . number_format($paid, 2) . "</td>";
        echo "<td style='padding: 10px;'>₱" . number_format($outstanding, 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No active tenants found.</p>";
}

echo "<h3>Calculation Logic Explanation</h3>";
echo "<div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<p><strong>How Outstanding Balance is Calculated:</strong></p>";
echo "<ol>";
echo "<li><strong>Calculate Months Owed:</strong> From registration date to current date</li>";
echo "<li><strong>Calculate Total Payable:</strong> Monthly Rate × Months Owed</li>";
echo "<li><strong>Get Total Paid:</strong> Sum of all payments made</li>";
echo "<li><strong>Outstanding Balance:</strong> Total Payable - Total Paid</li>";
echo "</ol>";
echo "<p><strong>Month Calculation:</strong> Uses proper DateTime difference calculation instead of simple day division.</p>";
echo "<p><strong>Current Date:</strong> " . date('Y-m-d') . "</p>";
echo "</div>";
?>
