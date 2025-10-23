<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🔍 Debug save_tenant Issue</h1>";

// Simulate the exact POST data that would be sent when saving a tenant
$test_data = [
    'firstname' => 'Test',
    'lastname' => 'User', 
    'middlename' => '',
    'email' => 'test@example.com',
    'contact' => '1234567890',
    'house_id' => 1, // Assuming house ID 1 exists
    'date_in' => '2025-08-15' // August 2025 date
];

echo "<h2>🧪 Testing save_tenant with August 2025 date</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h3>Test Data:</h3>";
foreach ($test_data as $key => $value) {
    echo "<p><strong>$key:</strong> $value</p>";
}
echo "</div>";

// Set up POST data
foreach ($test_data as $key => $value) {
    $_POST[$key] = $value;
}

$admin = new Admin_Class();

echo "<h3>🔧 Validation Tests:</h3>";

// Test each validation step
$date_in = $test_data['date_in'];

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0;'>";
echo "<h4>1. Date Format Validation:</h4>";
$date_obj = DateTime::createFromFormat('Y-m-d', $date_in);
if ($date_obj) {
    echo "<p>✅ DateTime::createFromFormat('Y-m-d', '$date_in') = SUCCESS</p>";
    echo "<p>Parsed date: " . $date_obj->format('Y-m-d') . "</p>";
} else {
    echo "<p>❌ DateTime::createFromFormat('Y-m-d', '$date_in') = FAILED</p>";
}
echo "</div>";

echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
echo "<h4>2. Future Date Check:</h4>";
$reg_date = new DateTime($date_in);
$current_date = new DateTime(date('Y-m-d'));
echo "<p><strong>Registration date:</strong> " . $reg_date->format('Y-m-d') . "</p>";
echo "<p><strong>Current date:</strong> " . $current_date->format('Y-m-d') . "</p>";
echo "<p><strong>Is future date:</strong> " . ($reg_date > $current_date ? 'YES ❌' : 'NO ✅') . "</p>";

if ($reg_date > $current_date) {
    echo "<p style='color: red; font-weight: bold;'>🚨 THIS IS THE PROBLEM! August 2025 is in the future compared to October 2025!</p>";
    echo "<p>The validation is rejecting the date because August 2025 < October 2025</p>";
}
echo "</div>";

echo "<h3>📊 save_tenant Result:</h3>";
$result = $admin->save_tenant();

$error_codes = [
    0 => 'Missing required fields or general error',
    1 => 'Success',
    2 => 'House already assigned to an active tenant', 
    3 => 'Invalid house ID',
    4 => 'Invalid email format',
    8 => 'Invalid date format',
    9 => 'Registration date cannot be in the future'
];

echo "<div style='background: " . ($result == 1 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($result == 1 ? '#c3e6cb' : '#f5c6cb') . ";'>";
echo "<p><strong>Result Code:</strong> $result</p>";
echo "<p><strong>Meaning:</strong> " . ($error_codes[$result] ?? 'Unknown error') . "</p>";
echo "</div>";

// Test what happens if we use a past date
echo "<h2>🧪 Test with Past Date (June 2025)</h2>";
$_POST['date_in'] = '2025-06-15';
$result2 = $admin->save_tenant();
echo "<p><strong>June 2025 result:</strong> $result2 (" . ($error_codes[$result2] ?? 'Unknown') . ")</p>";

// Test current month
echo "<h2>🧪 Test with Current Month (October 2025)</h2>";
$_POST['date_in'] = '2025-10-01';
$result3 = $admin->save_tenant();
echo "<p><strong>October 2025 result:</strong> $result3 (" . ($error_codes[$result3] ?? 'Unknown') . ")</p>";

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 Solution:</h2>";
echo "<p>The issue is that the validation is preventing you from entering <strong>past dates within the current year</strong>.</p>";
echo "<p>Since we're in October 2025, August 2025 is considered a 'past date' and gets rejected.</p>";
echo "<p><strong>We need to modify the validation logic</strong> to allow past dates within reasonable limits.</p>";
echo "</div>";

// Check what date was actually saved for the tenant
echo "<h2>📋 Check Actual Saved Data:</h2>";
$saved_tenant = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($saved_tenant) {
    echo "<p><strong>Last saved tenant:</strong> {$saved_tenant['firstname']} {$saved_tenant['lastname']}</p>";
    echo "<p><strong>Saved date_in:</strong> {$saved_tenant['date_in']}</p>";
    echo "<p>This explains why you see '0000-00-00' - the save failed and MySQL inserted the default invalid date.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
