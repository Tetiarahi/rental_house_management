<?php
include 'db_connect.php';

echo "<h1>🔍 Debug New Tenant Date Issue</h1>";

// Get the most recently added tenant
$result = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    echo "<h2>Recent Tenants (Last 5):</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Database date_in</th><th>strtotime() Result</th><th>Date Display</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $name = $row['firstname'] . ' ' . $row['lastname'];
        $date_in = $row['date_in'];
        
        // Test strtotime
        $timestamp = strtotime($date_in);
        $strtotime_result = $timestamp === false ? 'FALSE' : $timestamp;
        
        // Test date display (same logic as admin_class.php)
        if (empty($date_in) || $date_in == '0000-00-00') {
            $display = 'Invalid Date (empty/0000-00-00)';
            $status = '❌ Invalid';
        } else {
            $timestamp = strtotime($date_in);
            if ($timestamp === false) {
                $display = 'Invalid Date (strtotime failed)';
                $status = '❌ strtotime failed';
            } else {
                $display = date("M d, Y", $timestamp);
                $status = '✅ Valid';
            }
        }
        
        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td>$name</td>";
        echo "<td style='font-family: monospace;'>$date_in</td>";
        echo "<td style='font-family: monospace;'>$strtotime_result</td>";
        echo "<td><strong>$display</strong></td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the specific tenant you just added
    echo "<h2>🧪 Test Your New Tenant:</h2>";
    $newest = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
    
    if ($newest) {
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
        echo "<h3>Newest Tenant: {$newest['firstname']} {$newest['lastname']} (ID: {$newest['id']})</h3>";
        echo "<p><strong>Raw date_in from database:</strong> '{$newest['date_in']}'</p>";
        echo "<p><strong>Date length:</strong> " . strlen($newest['date_in']) . " characters</p>";
        echo "<p><strong>Date format check:</strong> " . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $newest['date_in']) ? '✅ Valid YYYY-MM-DD format' : '❌ Invalid format') . "</p>";
        
        // Test strtotime step by step
        echo "<h4>strtotime() Debug:</h4>";
        $test_timestamp = strtotime($newest['date_in']);
        echo "<p>strtotime('{$newest['date_in']}') = ";
        if ($test_timestamp === false) {
            echo "<span style='color: red; font-weight: bold;'>FALSE (failed to parse)</span>";
        } else {
            echo "<span style='color: green; font-weight: bold;'>$test_timestamp</span>";
            echo "<br>Which converts to: <strong>" . date("M d, Y", $test_timestamp) . "</strong>";
        }
        echo "</p>";
        
        // Test DateTime class (alternative)
        echo "<h4>DateTime Class Test:</h4>";
        try {
            $dt = new DateTime($newest['date_in']);
            echo "<p>DateTime class: <span style='color: green; font-weight: bold;'>✅ Success</span></p>";
            echo "<p>DateTime format: <strong>" . $dt->format("M d, Y") . "</strong></p>";
        } catch (Exception $e) {
            echo "<p>DateTime class: <span style='color: red; font-weight: bold;'>❌ Failed - " . $e->getMessage() . "</span></p>";
        }
        
        // Check for hidden characters
        echo "<h4>Hidden Characters Check:</h4>";
        $hex_dump = bin2hex($newest['date_in']);
        echo "<p>Hex dump: <code>$hex_dump</code></p>";
        
        // Test the exact logic from admin_class.php
        echo "<h4>admin_class.php Logic Test:</h4>";
        $rent_started = (empty($newest['date_in']) || $newest['date_in'] == '0000-00-00') ? 'Invalid Date' :
            (($timestamp = strtotime($newest['date_in'])) === false ? 'Invalid Date' : date("M d, Y", $timestamp));
        echo "<p>Result: <strong style='color: " . ($rent_started == 'Invalid Date' ? 'red' : 'green') . ";'>$rent_started</strong></p>";
        
        echo "</div>";
    }
} else {
    echo "<p>No tenants found.</p>";
}

// Test current date for comparison
echo "<h2>🕒 Current Date Test:</h2>";
echo "<p>Current date: " . date('Y-m-d') . "</p>";
echo "<p>strtotime(current date): " . strtotime(date('Y-m-d')) . "</p>";
echo "<p>Formatted: " . date("M d, Y", strtotime(date('Y-m-d'))) . "</p>";

// Test August 2025 dates
echo "<h2>🧪 August 2025 Date Tests:</h2>";
$test_dates = ['2025-08-01', '2025-08-15', '2025-08-31'];
foreach ($test_dates as $test_date) {
    $ts = strtotime($test_date);
    echo "<p>$test_date → " . ($ts === false ? 'FALSE' : date("M d, Y", $ts)) . "</p>";
}
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>
