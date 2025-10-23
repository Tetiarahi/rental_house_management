<?php
include 'db_connect.php';
include 'admin_class.php';

$admin = new Admin_Class();

echo "<h1>🧪 Test Rent Started Fix</h1>";

// Get the newest tenant (the one you just added)
$newest_tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($newest_tenant) {
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 20px 0;'>";
    echo "<h2>Testing Newest Tenant: {$newest_tenant['firstname']} {$newest_tenant['lastname']}</h2>";
    echo "<p><strong>Tenant ID:</strong> {$newest_tenant['id']}</p>";
    echo "<p><strong>Raw date_in:</strong> '{$newest_tenant['date_in']}'</p>";
    
    // Test the new formatDateSafely method
    echo "<h3>🔧 New formatDateSafely Method Test:</h3>";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($admin);
    $method = $reflection->getMethod('formatDateSafely');
    $method->setAccessible(true);
    
    $formatted_date = $method->invoke($admin, $newest_tenant['date_in']);
    echo "<p><strong>Result:</strong> <span style='color: " . ($formatted_date == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>$formatted_date</span></p>";
    
    // Test the get_tdetails method
    echo "<h3>📊 get_tdetails Method Test:</h3>";
    $_POST['id'] = $newest_tenant['id'];
    $details_json = $admin->get_tdetails();
    $details = json_decode($details_json, true);
    
    if ($details && $details['status'] == 1) {
        echo "<p><strong>Rent Started from get_tdetails:</strong> <span style='color: " . ($details['rent_started'] == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>{$details['rent_started']}</span></p>";
        echo "<p><strong>Outstanding Balance:</strong> {$details['outstanding']}</p>";
        echo "<p><strong>Months:</strong> {$details['months']}</p>";
    } else {
        echo "<p style='color: red;'>❌ get_tdetails failed: " . ($details['error'] ?? 'Unknown error') . "</p>";
    }
    
    echo "</div>";
    
    // Test all recent tenants
    echo "<h2>📋 All Recent Tenants Test:</h2>";
    $recent_tenants = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 5");
    
    if ($recent_tenants && $recent_tenants->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Name</th><th>Raw Date</th><th>Formatted Date</th><th>Status</th></tr>";
        
        while ($row = $recent_tenants->fetch_assoc()) {
            $formatted = $method->invoke($admin, $row['date_in']);
            $status = $formatted == 'Invalid Date' ? '❌ Invalid' : '✅ Valid';
            $color = $formatted == 'Invalid Date' ? 'red' : 'green';
            
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['firstname']} {$row['lastname']}</td>";
            echo "<td style='font-family: monospace;'>{$row['date_in']}</td>";
            echo "<td style='color: $color; font-weight: bold;'>$formatted</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test various date formats
    echo "<h2>🧪 Date Format Tests:</h2>";
    $test_dates = [
        '2025-08-15' => 'Valid August 2025 date',
        '2024-06-01' => 'Valid June 2024 date',
        '2025-12-31' => 'Valid December 2025 date',
        '0000-00-00' => 'Invalid MySQL date',
        '' => 'Empty string',
        '2025-13-01' => 'Invalid month',
        '2025-08-32' => 'Invalid day',
        'invalid' => 'Invalid format'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Input</th><th>Description</th><th>Result</th><th>Status</th></tr>";
    
    foreach ($test_dates as $test_date => $description) {
        $result = $method->invoke($admin, $test_date);
        $status = $result == 'Invalid Date' ? '❌ Invalid' : '✅ Valid';
        $color = $result == 'Invalid Date' ? 'red' : 'green';
        
        echo "<tr>";
        echo "<td style='font-family: monospace;'>$test_date</td>";
        echo "<td>$description</td>";
        echo "<td style='color: $color; font-weight: bold;'>$result</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>❌ No tenants found in database.</p>";
}

echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 Expected Results:</h2>";
echo "<ul>";
echo "<li>✅ <strong>August 2025 dates</strong> should show as 'Aug 15, 2025' (or similar)</li>";
echo "<li>✅ <strong>All valid dates</strong> should display properly formatted</li>";
echo "<li>✅ <strong>No 'Invalid Date'</strong> should appear for valid dates</li>";
echo "<li>✅ <strong>Rent Started</strong> in payment view should now work correctly</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h2>🧪 Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Check the results above</strong> - your August 2025 tenant should show a valid date</li>";
echo "<li><strong>Go to your main system</strong> and view the tenant's payment details</li>";
echo "<li><strong>Look for 'Rent Started'</strong> - it should now show the correct date instead of 'Invalid Date'</li>";
echo "</ol>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
