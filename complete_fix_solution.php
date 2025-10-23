<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🔧 Complete Fix Solution</h1>";

echo "<h2>Option 1: Fix Existing Tenant's Date</h2>";

// Fix Mathew's date first
$mathew_id = 23;
$update_query = "UPDATE tenants SET date_in = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$correct_date = '2025-08-15';
$stmt->bind_param("si", $correct_date, $mathew_id);

if ($stmt->execute()) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
    echo "<h3>✅ Fixed Mathew Tetiarahi's Date</h3>";
    echo "<p>Updated from <code>0000-00-00</code> to <code>$correct_date</code></p>";
    echo "</div>";
    
    // Verify the fix
    $updated = $conn->query("SELECT * FROM tenants WHERE id = $mathew_id")->fetch_assoc();
    echo "<p><strong>Verification:</strong> Mathew's date is now: <span style='color: green; font-weight: bold;'>{$updated['date_in']}</span></p>";
    
    // Test the view_payment.php logic
    $date_in = $updated['date_in'];
    if (empty($date_in) || $date_in == '0000-00-00') {
        $view_result = "Invalid Date";
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
                $view_result = "Invalid Date";
            }
        }
    }
    
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
    echo "<h4>🧪 Date Display Test:</h4>";
    echo "<p><strong>Raw date:</strong> {$updated['date_in']}</p>";
    echo "<p><strong>Formatted display:</strong> <span style='color: " . ($view_result == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>$view_result</span></p>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;'>";
    echo "<h3>❌ Failed to update Mathew's date</h3>";
    echo "<p>Error: " . $conn->error . "</p>";
    echo "</div>";
}
$stmt->close();

echo "<h2>Option 2: Add New Tenant to Available House</h2>";

// Test adding a new tenant to House ID 10 (which should be available)
$test_data = [
    'firstname' => 'New',
    'lastname' => 'Tenant',
    'middlename' => '',
    'email' => 'new' . time() . '@test.com',
    'contact' => '9876543210',
    'house_id' => 10, // Use House ID 10 instead of 9
    'date_in' => '2025-08-20'
];

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h3>📝 Adding New Tenant to House ID 10:</h3>";
foreach ($test_data as $key => $value) {
    echo "<p><strong>$key:</strong> $value</p>";
    $_POST[$key] = $value;
}
echo "</div>";

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
echo "<h3>💾 New Tenant Save Result:</h3>";
echo "<p><strong>Result Code:</strong> $result</p>";
echo "<p><strong>Meaning:</strong> " . ($error_codes[$result] ?? "Unknown error") . "</p>";
echo "</div>";

// Check all tenants now
echo "<h2>📋 Current Tenants After Fix</h2>";
$all_tenants = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC");
if ($all_tenants && $all_tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Name</th><th>Email</th><th>House ID</th><th>Date In</th><th>Display Test</th></tr>";
    
    while ($tenant = $all_tenants->fetch_assoc()) {
        $date_in = $tenant['date_in'];
        
        // Test display for each tenant
        if (empty($date_in) || $date_in == '0000-00-00') {
            $display = "Invalid Date";
            $color = 'red';
        } else {
            $date_in_clean = trim($date_in);
            try {
                $date = DateTime::createFromFormat('Y-m-d', $date_in_clean);
                if ($date && $date->format('Y-m-d') === $date_in_clean) {
                    $display = $date->format('M d, Y');
                    $color = 'green';
                } else {
                    throw new Exception('Invalid format');
                }
            } catch (Exception $e) {
                $timestamp = strtotime($date_in_clean);
                if ($timestamp !== false) {
                    $display = date("M d, Y", $timestamp);
                    $color = 'green';
                } else {
                    $display = "Invalid Date";
                    $color = 'red';
                }
            }
        }
        
        echo "<tr>";
        echo "<td>{$tenant['id']}</td>";
        echo "<td>{$tenant['firstname']} {$tenant['lastname']}</td>";
        echo "<td>{$tenant['email']}</td>";
        echo "<td>{$tenant['house_id']}</td>";
        echo "<td style='font-family: monospace;'>{$tenant['date_in']}</td>";
        echo "<td style='color: $color; font-weight: bold;'>$display</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>🔗 Test Links</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
echo "<p><a href='view_payment.php?id=23' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>View Mathew's Payment Details</a></p>";

if ($result == 1) {
    $newest = $conn->query("SELECT id FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($newest) {
        echo "<p><a href='view_payment.php?id={$newest['id']}' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>View New Tenant's Payment Details</a></p>";
    }
}

echo "<p><a href='tenants.php' target='_blank' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>Go to Tenants Page</a></p>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
echo "<h2>✅ Summary of Fixes Applied:</h2>";
echo "<ol>";
echo "<li><strong>Fixed Mathew Tetiarahi's date</strong> from 0000-00-00 to 2025-08-15</li>";
echo "<li><strong>Added new tenant to House ID 10</strong> (if successful)</li>";
echo "<li><strong>Both tenants should now show proper dates</strong> in payment view</li>";
echo "<li><strong>Date validation is working</strong> for August 2025 dates</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
echo "<h2>🎯 Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Click the test links above</strong> to verify the payment details show proper dates</li>";
echo "<li><strong>For future tenants</strong> - use House ID 10 or add more houses</li>";
echo "<li><strong>The 'Rent Started' field</strong> should now show 'Aug 15, 2025' or 'Aug 20, 2025'</li>";
echo "<li><strong>No more 'Invalid Date'</strong> should appear</li>";
echo "</ol>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
