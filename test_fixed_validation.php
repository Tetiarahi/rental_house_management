<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🧪 Test Fixed Validation Logic</h1>";

$admin = new Admin_Class();

// Test data for different scenarios
$test_scenarios = [
    'August 2025 (past date in current year)' => '2025-08-15',
    'June 2025 (past date in current year)' => '2025-06-01', 
    'Current month (October 2025)' => '2025-10-15',
    'Next month (November 2025)' => '2025-11-15',
    'Too far future (January 2026)' => '2026-01-15',
    'Very old date (2010)' => '2010-01-01'
];

echo "<h2>📊 Validation Test Results:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f2f2f2;'><th>Scenario</th><th>Date</th><th>Result</th><th>Status</th></tr>";

foreach ($test_scenarios as $scenario => $test_date) {
    // Set up minimal POST data for testing
    $_POST = [
        'firstname' => 'Test',
        'lastname' => 'User',
        'middlename' => '',
        'email' => 'test' . time() . '@example.com', // Unique email
        'contact' => '1234567890',
        'house_id' => 1, // Assuming house 1 exists
        'date_in' => $test_date
    ];
    
    $result = $admin->save_tenant();
    
    $error_codes = [
        0 => 'Missing required fields or general error',
        1 => 'Success ✅',
        2 => 'House already assigned',
        3 => 'Invalid house ID', 
        4 => 'Invalid email format',
        8 => 'Invalid date format',
        9 => 'Date more than 1 month in future',
        10 => 'Date more than 10 years ago'
    ];
    
    $status_color = $result == 1 ? 'green' : 'red';
    $status_icon = $result == 1 ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td>$scenario</td>";
    echo "<td style='font-family: monospace;'>$test_date</td>";
    echo "<td>" . ($error_codes[$result] ?? "Unknown ($result)") . "</td>";
    echo "<td style='color: $status_color; font-weight: bold;'>$status_icon</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
echo "<h2>✅ Expected Results:</h2>";
echo "<ul>";
echo "<li><strong>August 2025:</strong> Should now be ✅ SUCCESS (was previously rejected)</li>";
echo "<li><strong>June 2025:</strong> Should be ✅ SUCCESS</li>";
echo "<li><strong>Current/Next month:</strong> Should be ✅ SUCCESS</li>";
echo "<li><strong>January 2026:</strong> Should be ❌ REJECTED (too far in future)</li>";
echo "<li><strong>2010:</strong> Should be ❌ REJECTED (too old)</li>";
echo "</ul>";
echo "</div>";

// Now test updating the existing tenant with the correct date
echo "<h2>🔧 Fix Existing Tenant</h2>";

$existing_tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($existing_tenant && $existing_tenant['date_in'] == '0000-00-00') {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
    echo "<h3>Found tenant with invalid date: {$existing_tenant['firstname']} {$existing_tenant['lastname']}</h3>";
    echo "<p><strong>Current date_in:</strong> {$existing_tenant['date_in']}</p>";
    
    // Update with August 2025 date
    $update_query = "UPDATE tenants SET date_in = '2025-08-15' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $existing_tenant['id']);
    
    if ($stmt->execute()) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully updated tenant date to August 15, 2025</p>";
        
        // Verify the update
        $updated = $conn->query("SELECT date_in FROM tenants WHERE id = {$existing_tenant['id']}")->fetch_assoc();
        echo "<p><strong>New date_in:</strong> {$updated['date_in']}</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to update tenant date</p>";
    }
    $stmt->close();
    echo "</div>";
}

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Clear browser cache</strong> (Ctrl+F5)</li>";
echo "<li><strong>Try adding a new tenant</strong> with August 2025 date</li>";
echo "<li><strong>Check that it saves successfully</strong> (no more 0000-00-00)</li>";
echo "<li><strong>View the tenant's payment details</strong> - should show proper 'Rent Started' date</li>";
echo "</ol>";
echo "</div>";

// Show current date limits
echo "<h2>📅 Current Date Limits:</h2>";
$current = new DateTime();
$min_date = clone $current;
$min_date->sub(new DateInterval('P10Y'));
$max_date = clone $current;
$max_date->add(new DateInterval('P1M'));

echo "<p><strong>Minimum allowed date:</strong> " . $min_date->format('Y-m-d') . "</p>";
echo "<p><strong>Maximum allowed date:</strong> " . $max_date->format('Y-m-d') . "</p>";
echo "<p><strong>Current date:</strong> " . $current->format('Y-m-d') . "</p>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
