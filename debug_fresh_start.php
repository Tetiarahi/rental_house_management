<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🔍 Debug Fresh Database - Step by Step</h1>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📊 Current Database State</h2>";

// Check houses
$houses = $conn->query("SELECT * FROM houses ORDER BY id");
echo "<h3>🏠 Houses in Database:</h3>";
if ($houses && $houses->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>House No</th><th>Price</th></tr>";
    while ($house = $houses->fetch_assoc()) {
        echo "<tr><td>{$house['id']}</td><td>{$house['house_no']}</td><td>" . number_format($house['price'], 2) . "</td></tr>";
    }
    echo "</table>";
    
    // Get first house for testing
    $houses->data_seek(0);
    $first_house = $houses->fetch_assoc();
    $test_house_id = $first_house['id'];
} else {
    echo "<p style='color: red;'>❌ No houses found! You need to add houses first.</p>";
    $test_house_id = 1;
}

// Check tenants
$tenants = $conn->query("SELECT * FROM tenants ORDER BY id DESC");
echo "<h3>👥 Tenants in Database:</h3>";
if ($tenants && $tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Name</th><th>Email</th><th>House ID</th><th>Date In</th><th>Status</th></tr>";
    while ($tenant = $tenants->fetch_assoc()) {
        $date_color = $tenant['date_in'] == '0000-00-00' ? 'red' : 'green';
        echo "<tr>";
        echo "<td>{$tenant['id']}</td>";
        echo "<td>{$tenant['firstname']} {$tenant['lastname']}</td>";
        echo "<td>{$tenant['email']}</td>";
        echo "<td>{$tenant['house_id']}</td>";
        echo "<td style='color: $date_color; font-weight: bold;'>{$tenant['date_in']}</td>";
        echo "<td>{$tenant['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tenants found.</p>";
}

echo "<h2>🧪 Test Adding New Tenant</h2>";

// Test adding a tenant with current date validation
$test_data = [
    'firstname' => 'Fresh',
    'lastname' => 'Test',
    'middlename' => '',
    'email' => 'fresh' . time() . '@test.com',
    'contact' => '1234567890',
    'house_id' => $test_house_id,
    'date_in' => '2025-08-15'
];

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h3>📝 Test Data:</h3>";
foreach ($test_data as $key => $value) {
    echo "<p><strong>$key:</strong> $value</p>";
    $_POST[$key] = $value;
}
echo "</div>";

// Test the save
$admin = new Action();
$result = $admin->save_tenant();

$error_codes = [
    0 => 'Missing required fields or general error',
    1 => 'Success ✅',
    2 => 'House already assigned to an active tenant',
    3 => 'Invalid house ID',
    4 => 'Invalid email format',
    8 => 'Invalid date format',
    9 => 'Date more than 1 month in future',
    10 => 'Date more than 10 years ago'
];

echo "<div style='background: " . ($result == 1 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($result == 1 ? '#c3e6cb' : '#f5c6cb') . ";'>";
echo "<h3>💾 Save Result:</h3>";
echo "<p><strong>Result Code:</strong> $result</p>";
echo "<p><strong>Meaning:</strong> " . ($error_codes[$result] ?? "Unknown error") . "</p>";
echo "</div>";

// Check what was actually saved
echo "<h3>📋 Check What Was Saved:</h3>";
$latest = $conn->query("SELECT * FROM tenants ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($latest) {
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h4>Latest Tenant in Database:</h4>";
    echo "<p><strong>ID:</strong> {$latest['id']}</p>";
    echo "<p><strong>Name:</strong> {$latest['firstname']} {$latest['lastname']}</p>";
    echo "<p><strong>Email:</strong> {$latest['email']}</p>";
    echo "<p><strong>Date In:</strong> <span style='color: " . ($latest['date_in'] == '0000-00-00' ? 'red' : 'green') . "; font-weight: bold;'>{$latest['date_in']}</span></p>";
    echo "<p><strong>House ID:</strong> {$latest['house_id']}</p>";
    echo "</div>";
    
    // Test the view_payment.php logic with this tenant
    echo "<h3>🔍 Test view_payment.php Logic:</h3>";
    $date_in = $latest['date_in'];
    
    if (empty($date_in) || $date_in == '0000-00-00') {
        $view_result = "Invalid Date (empty/0000-00-00)";
    } else {
        $date_in = trim($date_in);
        try {
            $date = DateTime::createFromFormat('Y-m-d', $date_in);
            if ($date && $date->format('Y-m-d') === $date_in) {
                $view_result = $date->format('M d, Y');
            } else {
                throw new Exception('Invalid format');
            }
        } catch (Exception $e) {
            $timestamp = strtotime($date_in);
            if ($timestamp !== false) {
                $view_result = date("M d, Y", $timestamp);
            } else {
                $view_result = "Invalid Date (strtotime failed)";
            }
        }
    }
    
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
    echo "<p><strong>Raw date_in:</strong> '{$latest['date_in']}'</p>";
    echo "<p><strong>view_payment.php would show:</strong> <span style='color: " . ($view_result == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>$view_result</span></p>";
    echo "</div>";
    
    // Test direct link
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
    echo "<h3>🔗 Direct Test Link:</h3>";
    echo "<p><a href='view_payment.php?id={$latest['id']}' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>View This Tenant's Payment Details</a></p>";
    echo "</div>";
}

// Check if the save_tenant method is working correctly
echo "<h2>🔧 Debug save_tenant Method</h2>";

// Let's manually check the SQL that would be executed
if ($result == 1) {
    echo "<p style='color: green;'>✅ save_tenant returned success, so the issue might be in the display logic.</p>";
} else {
    echo "<p style='color: red;'>❌ save_tenant failed. Let's check why:</p>";
    
    // Manual validation checks
    echo "<h4>Manual Validation Checks:</h4>";
    
    // Check required fields
    $required_ok = !empty($test_data['firstname']) && !empty($test_data['lastname']) && 
                   !empty($test_data['email']) && !empty($test_data['contact']) && 
                   !empty($test_data['house_id']) && !empty($test_data['date_in']);
    echo "<p>Required fields: " . ($required_ok ? '✅ OK' : '❌ MISSING') . "</p>";
    
    // Check email
    $email_ok = filter_var($test_data['email'], FILTER_VALIDATE_EMAIL);
    echo "<p>Email format: " . ($email_ok ? '✅ OK' : '❌ INVALID') . "</p>";
    
    // Check date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $test_data['date_in']);
    $date_ok = $date_obj && $date_obj->format('Y-m-d') === $test_data['date_in'];
    echo "<p>Date format: " . ($date_ok ? '✅ OK' : '❌ INVALID') . "</p>";
    
    // Check house exists
    $house_check = $conn->query("SELECT id FROM houses WHERE id = {$test_data['house_id']}");
    $house_ok = $house_check && $house_check->num_rows > 0;
    echo "<p>House exists: " . ($house_ok ? '✅ OK' : '❌ NOT FOUND') . "</p>";
    
    // Check for duplicates
    $dup_check = $conn->query("SELECT id FROM tenants WHERE house_id = {$test_data['house_id']} AND status = 1");
    $no_dup = !$dup_check || $dup_check->num_rows == 0;
    echo "<p>No duplicates: " . ($no_dup ? '✅ OK' : '❌ DUPLICATE FOUND') . "</p>";
}

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
echo "<h2>📋 Summary:</h2>";
echo "<p>1. <strong>Database state:</strong> " . ($houses && $houses->num_rows > 0 ? 'Houses available' : 'No houses') . "</p>";
echo "<p>2. <strong>Save result:</strong> " . ($error_codes[$result] ?? "Unknown ($result)") . "</p>";
echo "<p>3. <strong>Date in database:</strong> " . ($latest ? $latest['date_in'] : 'No tenant saved') . "</p>";
echo "<p>4. <strong>Expected display:</strong> " . ($latest ? $view_result : 'N/A') . "</p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
