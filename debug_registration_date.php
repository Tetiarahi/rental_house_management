<?php
include 'db_connect.php';

echo "<h2>Registration Date Debug</h2>";
echo "<p>Let's examine the registration date issue and how it affects outstanding balance calculation.</p>";

// Get all tenants and their registration dates
$tenants = $conn->query("SELECT t.*,concat(t.lastname,', ',t.firstname,' ',t.middlename) as name,h.house_no,h.price FROM tenants t inner join houses h on h.id = t.house_id where t.status = 1 order by t.date_in desc");

if ($tenants->num_rows > 0) {
    echo "<h3>Current Tenant Registration Dates</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Tenant Name</th>";
    echo "<th style='padding: 10px;'>House #</th>";
    echo "<th style='padding: 10px;'>Registration Date (date_in)</th>";
    echo "<th style='padding: 10px;'>Monthly Rate</th>";
    echo "<th style='padding: 10px;'>Days Since Registration</th>";
    echo "<th style='padding: 10px;'>Old Calculation (Days/30)</th>";
    echo "<th style='padding: 10px;'>New Calculation (Proper Months)</th>";
    echo "<th style='padding: 10px;'>Difference</th>";
    echo "</tr>";
    
    while ($row = $tenants->fetch_assoc()) {
        $date_in = $row['date_in'];
        $price = $row['price'];
        $name = $row['name'];
        $house_no = $row['house_no'];
        
        // Current date
        $current_date = new DateTime(date('Y-m-d'));
        $registration_date = new DateTime($date_in);
        
        // Calculate days since registration
        $days_diff = $current_date->diff($registration_date)->days;
        
        // Old calculation (incorrect)
        $old_months = floor($days_diff / 30);
        $old_payable = $price * $old_months;
        
        // New calculation (correct)
        $interval = $registration_date->diff($current_date);
        $new_months = ($interval->y * 12) + $interval->m;
        
        // If we're past the day of the month when they registered, add 1 more month
        if ($current_date->format('d') >= $registration_date->format('d')) {
            $new_months += 1;
        }
        
        $new_payable = $price * $new_months;
        $difference = $new_payable - $old_payable;
        
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . ucwords($name) . "</td>";
        echo "<td style='padding: 10px;'>{$house_no}</td>";
        echo "<td style='padding: 10px;'>{$date_in}</td>";
        echo "<td style='padding: 10px;'>₱" . number_format($price, 2) . "</td>";
        echo "<td style='padding: 10px;'>{$days_diff} days</td>";
        echo "<td style='padding: 10px;'>{$old_months} months (₱" . number_format($old_payable, 2) . ")</td>";
        echo "<td style='padding: 10px;'>{$new_months} months (₱" . number_format($new_payable, 2) . ")</td>";
        echo "<td style='padding: 10px; " . ($difference > 0 ? "color: red;" : "color: green;") . "'>₱" . number_format($difference, 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No active tenants found.</p>";
}

// Show detailed breakdown for understanding
echo "<h3>Understanding the Registration Date Problem</h3>";
echo "<div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>What might be wrong with registration dates:</h4>";
echo "<ol>";
echo "<li><strong>Date Format Issues:</strong> Dates might be stored in wrong format</li>";
echo "<li><strong>Default Dates:</strong> System might be using current date instead of actual move-in date</li>";
echo "<li><strong>Timezone Issues:</strong> Date calculations might have timezone problems</li>";
echo "<li><strong>User Input Issues:</strong> Users might be entering wrong dates</li>";
echo "<li><strong>Calculation Logic:</strong> The month calculation logic might be flawed</li>";
echo "</ol>";
echo "</div>";

// Test different scenarios
echo "<h3>Test Scenarios</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Scenario</th>";
echo "<th style='padding: 10px;'>Registration Date</th>";
echo "<th style='padding: 10px;'>Current Date</th>";
echo "<th style='padding: 10px;'>Expected Months</th>";
echo "<th style='padding: 10px;'>Calculated Months</th>";
echo "<th style='padding: 10px;'>Status</th>";
echo "</tr>";

$test_scenarios = [
    ['desc' => 'Same month registration', 'reg_date' => '2024-10-01', 'expected' => 1],
    ['desc' => 'Previous month registration', 'reg_date' => '2024-09-15', 'expected' => 2],
    ['desc' => 'Two months ago', 'reg_date' => '2024-08-15', 'expected' => 3],
    ['desc' => 'Six months ago', 'reg_date' => '2024-04-15', 'expected' => 7],
    ['desc' => 'One year ago', 'reg_date' => '2023-10-15', 'expected' => 13],
];

$current_date = new DateTime(date('Y-m-d'));

foreach ($test_scenarios as $scenario) {
    $reg_date = new DateTime($scenario['reg_date']);
    $expected = $scenario['expected'];
    
    // Calculate using our new method
    $interval = $reg_date->diff($current_date);
    $calculated_months = ($interval->y * 12) + $interval->m;
    
    if ($current_date->format('d') >= $reg_date->format('d')) {
        $calculated_months += 1;
    }
    
    $status = ($calculated_months == $expected) ? "✅ Correct" : "❌ Wrong";
    
    echo "<tr>";
    echo "<td style='padding: 10px;'>{$scenario['desc']}</td>";
    echo "<td style='padding: 10px;'>{$scenario['reg_date']}</td>";
    echo "<td style='padding: 10px;'>" . $current_date->format('Y-m-d') . "</td>";
    echo "<td style='padding: 10px;'>{$expected} months</td>";
    echo "<td style='padding: 10px;'>{$calculated_months} months</td>";
    echo "<td style='padding: 10px;'>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

// Check for common issues
echo "<h3>Common Registration Date Issues</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>Potential Problems:</h4>";
echo "<ul>";
echo "<li><strong>Future Dates:</strong> Registration dates set in the future</li>";
echo "<li><strong>Very Old Dates:</strong> Dates that seem unrealistic (too far in the past)</li>";
echo "<li><strong>Default Dates:</strong> All tenants having the same registration date</li>";
echo "<li><strong>Wrong Month Calculation:</strong> Not accounting for partial months correctly</li>";
echo "</ul>";

// Check for these issues in actual data
$future_dates = $conn->query("SELECT COUNT(*) as count FROM tenants WHERE date_in > CURDATE()");
$future_count = $future_dates->fetch_assoc()['count'];

$old_dates = $conn->query("SELECT COUNT(*) as count FROM tenants WHERE date_in < '2020-01-01'");
$old_count = $old_dates->fetch_assoc()['count'];

$same_dates = $conn->query("SELECT date_in, COUNT(*) as count FROM tenants GROUP BY date_in HAVING count > 1");

echo "<p><strong>Analysis of Current Data:</strong></p>";
echo "<ul>";
echo "<li>Tenants with future registration dates: {$future_count}</li>";
echo "<li>Tenants with very old registration dates (before 2020): {$old_count}</li>";

if ($same_dates->num_rows > 0) {
    echo "<li>Duplicate registration dates found:</li>";
    echo "<ul>";
    while ($row = $same_dates->fetch_assoc()) {
        echo "<li>{$row['date_in']}: {$row['count']} tenants</li>";
    }
    echo "</ul>";
} else {
    echo "<li>No duplicate registration dates found</li>";
}
echo "</ul>";
echo "</div>";

echo "<h3>Recommendations</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>To fix registration date issues:</h4>";
echo "<ol>";
echo "<li><strong>Validate Input:</strong> Ensure registration dates cannot be in the future</li>";
echo "<li><strong>Review Existing Data:</strong> Check and correct any invalid registration dates</li>";
echo "<li><strong>Improve UI:</strong> Make the registration date field more prominent and clear</li>";
echo "<li><strong>Add Validation:</strong> Server-side validation for reasonable date ranges</li>";
echo "<li><strong>Default Behavior:</strong> Consider defaulting to current date but allow modification</li>";
echo "</ol>";
echo "</div>";
?>
