<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🧪 FULL TEST DEBUG - Complete Verification</h1>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 This test will verify:</h2>";
echo "<ol>";
echo "<li><strong>Parameter binding fix</strong> - Date should save correctly</li>";
echo "<li><strong>Form submission</strong> - Frontend to backend communication</li>";
echo "<li><strong>Database storage</strong> - Actual data in database</li>";
echo "<li><strong>Date display</strong> - Payment view shows correct date</li>";
echo "<li><strong>End-to-end functionality</strong> - Complete workflow</li>";
echo "</ol>";
echo "</div>";

// Step 1: Check current database state
echo "<h2>📊 Step 1: Current Database State</h2>";

$houses = $conn->query("SELECT * FROM houses ORDER BY id");
echo "<h3>🏠 Available Houses:</h3>";
if ($houses && $houses->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>House No</th><th>Price</th><th>Status</th></tr>";
    
    while ($house = $houses->fetch_assoc()) {
        // Check if house is occupied
        $tenant_check = $conn->query("SELECT id, firstname, lastname FROM tenants WHERE house_id = {$house['id']} AND status = 1");
        $status = $tenant_check && $tenant_check->num_rows > 0 ? 
                 "Occupied by " . $tenant_check->fetch_assoc()['firstname'] : 
                 "Available";
        $color = $status == "Available" ? "green" : "orange";
        
        echo "<tr>";
        echo "<td>{$house['id']}</td>";
        echo "<td>{$house['house_no']}</td>";
        echo "<td>$" . number_format($house['price'], 2) . "</td>";
        echo "<td style='color: $color; font-weight: bold;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Find an available house for testing
    $available_house = null;
    $houses->data_seek(0);
    while ($house = $houses->fetch_assoc()) {
        $tenant_check = $conn->query("SELECT id FROM tenants WHERE house_id = {$house['id']} AND status = 1");
        if (!$tenant_check || $tenant_check->num_rows == 0) {
            $available_house = $house;
            break;
        }
    }
    
    if ($available_house) {
        echo "<p style='color: green; font-weight: bold;'>✅ Found available house: {$available_house['house_no']} (ID: {$available_house['id']})</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ All houses are occupied. Will test with House ID 10.</p>";
        $available_house = ['id' => 10, 'house_no' => 'H02'];
    }
} else {
    echo "<p style='color: red;'>❌ No houses found!</p>";
    $available_house = ['id' => 1, 'house_no' => 'H01'];
}

// Step 2: Test the fixed save_tenant method
echo "<h2>🔧 Step 2: Test Fixed save_tenant Method</h2>";

$test_data = [
    'firstname' => 'FullTest',
    'lastname' => 'User',
    'middlename' => 'Debug',
    'email' => 'fulltest' . time() . '@example.com',
    'contact' => '9876543210',
    'house_id' => $available_house['id'],
    'date_in' => '2025-08-25'
];

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h3>📝 Test Data:</h3>";
foreach ($test_data as $key => $value) {
    echo "<p><strong>$key:</strong> $value</p>";
    $_POST[$key] = $value;
}
echo "</div>";

// Execute the save
$admin = new Action();
$save_result = $admin->save_tenant();

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

echo "<div style='background: " . ($save_result == 1 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($save_result == 1 ? '#c3e6cb' : '#f5c6cb') . ";'>";
echo "<h3>💾 Save Result:</h3>";
echo "<p><strong>Result Code:</strong> $save_result</p>";
echo "<p><strong>Meaning:</strong> " . ($error_codes[$save_result] ?? "Unknown error") . "</p>";
echo "</div>";

// Step 3: Verify what was saved in database
echo "<h2>📋 Step 3: Database Verification</h2>";

$latest_tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($latest_tenant) {
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h3>Latest Tenant in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Field</th><th>Value</th><th>Status</th></tr>";
    
    foreach ($latest_tenant as $key => $value) {
        $status = '✅ OK';
        $color = 'green';
        
        if ($key == 'date_in') {
            if ($value == '0000-00-00' || empty($value)) {
                $status = '❌ INVALID';
                $color = 'red';
            } else {
                $status = '✅ VALID DATE';
                $color = 'green';
            }
        }
        
        echo "<tr>";
        echo "<td><strong>$key</strong></td>";
        echo "<td style='font-family: monospace;'>$value</td>";
        echo "<td style='color: $color; font-weight: bold;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Step 4: Test date display logic
    echo "<h2>🎨 Step 4: Date Display Test</h2>";
    
    $date_in = $latest_tenant['date_in'];
    
    // Test the view_payment.php logic
    if (empty($date_in) || $date_in == '0000-00-00') {
        $display_result = "Invalid Date";
        $display_color = 'red';
    } else {
        $date_in_clean = trim($date_in);
        try {
            $date = DateTime::createFromFormat('Y-m-d', $date_in_clean);
            if ($date && $date->format('Y-m-d') === $date_in_clean) {
                $display_result = $date->format('M d, Y');
                $display_color = 'green';
            } else {
                throw new Exception('Invalid format');
            }
        } catch (Exception $e) {
            $timestamp = strtotime($date_in_clean);
            if ($timestamp !== false) {
                $display_result = date("M d, Y", $timestamp);
                $display_color = 'green';
            } else {
                $display_result = "Invalid Date";
                $display_color = 'red';
            }
        }
    }
    
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
    echo "<h3>Date Display Test Results:</h3>";
    echo "<p><strong>Raw database value:</strong> <code>$date_in</code></p>";
    echo "<p><strong>Formatted display:</strong> <span style='color: $display_color; font-weight: bold; font-size: 18px;'>$display_result</span></p>";
    echo "<p><strong>Status:</strong> " . ($display_color == 'green' ? '✅ SUCCESS - Date displays correctly' : '❌ FAILED - Date still shows as Invalid') . "</p>";
    echo "</div>";
    
    // Step 5: Test actual payment view
    echo "<h2>🔗 Step 5: Live Payment View Test</h2>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
    echo "<h3>Test Links:</h3>";
    echo "<p><a href='view_payment.php?id={$latest_tenant['id']}' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 View Payment Details</a></p>";
    echo "<p><a href='tenants.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 View All Tenants</a></p>";
    echo "<p><strong>Expected Result:</strong> The payment view should show 'Rent Started: Aug 25, 2025' instead of 'Invalid Date'</p>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;'>";
    echo "<h3>❌ No tenant found in database</h3>";
    echo "<p>The save operation may have failed completely.</p>";
    echo "</div>";
}

// Step 6: Summary and next steps
echo "<h2>📊 Step 6: Complete Test Summary</h2>";

$all_tests_passed = true;
$test_results = [];

// Check save result
if ($save_result == 1) {
    $test_results[] = "✅ Save operation: SUCCESS";
} else {
    $test_results[] = "❌ Save operation: FAILED (Code: $save_result)";
    $all_tests_passed = false;
}

// Check database storage
if ($latest_tenant && $latest_tenant['date_in'] != '0000-00-00' && !empty($latest_tenant['date_in'])) {
    $test_results[] = "✅ Database storage: SUCCESS (Date: {$latest_tenant['date_in']})";
} else {
    $test_results[] = "❌ Database storage: FAILED (Date: " . ($latest_tenant['date_in'] ?? 'NULL') . ")";
    $all_tests_passed = false;
}

// Check date display
if (isset($display_result) && $display_result != 'Invalid Date') {
    $test_results[] = "✅ Date display: SUCCESS ($display_result)";
} else {
    $test_results[] = "❌ Date display: FAILED (Shows: " . ($display_result ?? 'Unknown') . ")";
    $all_tests_passed = false;
}

echo "<div style='background: " . ($all_tests_passed ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($all_tests_passed ? '#c3e6cb' : '#f5c6cb') . ";'>";
echo "<h3>" . ($all_tests_passed ? "🎉 ALL TESTS PASSED!" : "⚠️ Some Tests Failed") . "</h3>";
echo "<ul>";
foreach ($test_results as $result) {
    echo "<li style='margin: 5px 0;'>$result</li>";
}
echo "</ul>";
echo "</div>";

if ($all_tests_passed) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
    echo "<h2>🎯 SUCCESS! The Fix is Working!</h2>";
    echo "<p><strong>The registration date issue is now completely resolved:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Dates are being saved correctly to the database</li>";
    echo "<li>✅ Payment view displays proper formatted dates</li>";
    echo "<li>✅ No more 'Invalid Date' issues</li>";
    echo "<li>✅ August 2025 dates work perfectly</li>";
    echo "</ul>";
    echo "<p><strong>You can now add tenants with any valid registration date!</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
    echo "<h2>🔧 Additional Debugging Needed</h2>";
    echo "<p>Some tests failed. Please check the specific error messages above and let me know which step is failing.</p>";
    echo "</div>";
}

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 20px 0;'>";
echo "<h2>📋 Test Completed</h2>";
echo "<p><strong>Test Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Test Tenant:</strong> " . ($latest_tenant ? "{$latest_tenant['firstname']} {$latest_tenant['lastname']}" : "None") . "</p>";
echo "<p><strong>Registration Date Tested:</strong> 2025-08-25</p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
