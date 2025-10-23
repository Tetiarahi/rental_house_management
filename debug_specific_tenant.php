<?php
include 'db_connect.php';

echo "<h1>🔍 Debug Specific Tenant Issue</h1>";

// Get the newest tenant (the one you just added)
$newest_tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($newest_tenant) {
    $tenant_id = $newest_tenant['id'];
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 20px 0;'>";
    echo "<h2>Debugging Tenant: {$newest_tenant['firstname']} {$newest_tenant['lastname']} (ID: $tenant_id)</h2>";
    
    echo "<h3>📊 Raw Database Data:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    foreach ($newest_tenant as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td style='font-family: monospace;'>$value</td></tr>";
    }
    echo "</table>";
    
    $date_in = $newest_tenant['date_in'];
    echo "<h3>🔍 Date Analysis:</h3>";
    echo "<p><strong>Raw date_in:</strong> '$date_in'</p>";
    echo "<p><strong>Length:</strong> " . strlen($date_in) . " characters</p>";
    echo "<p><strong>Hex dump:</strong> " . bin2hex($date_in) . "</p>";
    echo "<p><strong>Is empty:</strong> " . (empty($date_in) ? 'YES' : 'NO') . "</p>";
    echo "<p><strong>Equals '0000-00-00':</strong> " . ($date_in == '0000-00-00' ? 'YES' : 'NO') . "</p>";
    
    // Test strtotime
    echo "<h3>⏰ strtotime() Test:</h3>";
    $timestamp = strtotime($date_in);
    echo "<p><strong>strtotime('$date_in'):</strong> ";
    if ($timestamp === false) {
        echo "<span style='color: red; font-weight: bold;'>FALSE (failed)</span>";
    } else {
        echo "<span style='color: green; font-weight: bold;'>$timestamp</span>";
        echo "<br><strong>Formatted:</strong> " . date("M d, Y", $timestamp);
    }
    echo "</p>";
    
    // Test DateTime
    echo "<h3>📅 DateTime Test:</h3>";
    try {
        $dt = DateTime::createFromFormat('Y-m-d', $date_in);
        if ($dt && $dt->format('Y-m-d') === $date_in) {
            echo "<p><strong>DateTime::createFromFormat:</strong> <span style='color: green; font-weight: bold;'>SUCCESS</span></p>";
            echo "<p><strong>Formatted:</strong> " . $dt->format('M d, Y') . "</p>";
        } else {
            echo "<p><strong>DateTime::createFromFormat:</strong> <span style='color: red; font-weight: bold;'>FAILED (format mismatch)</span></p>";
        }
    } catch (Exception $e) {
        echo "<p><strong>DateTime::createFromFormat:</strong> <span style='color: red; font-weight: bold;'>EXCEPTION - " . $e->getMessage() . "</span></p>";
    }
    
    // Test the exact logic from view_payment.php
    echo "<h3>🔧 view_payment.php Logic Test:</h3>";
    if (empty($date_in) || $date_in == '0000-00-00') {
        $view_result = "Invalid Date (empty/0000-00-00)";
    } else {
        // Clean the date string
        $clean_date = trim($date_in);
        
        // Try DateTime class first
        try {
            $date = DateTime::createFromFormat('Y-m-d', $clean_date);
            if ($date && $date->format('Y-m-d') === $clean_date) {
                $view_result = $date->format('M d, Y');
            } else {
                throw new Exception('Invalid format');
            }
        } catch (Exception $e) {
            // Fallback to strtotime
            $timestamp = strtotime($clean_date);
            if ($timestamp !== false) {
                $view_result = date("M d, Y", $timestamp);
            } else {
                $view_result = "Invalid Date (strtotime failed)";
            }
        }
    }
    echo "<p><strong>view_payment.php result:</strong> <span style='color: " . ($view_result == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>$view_result</span></p>";
    
    echo "</div>";
    
    // Now test the actual view_payment.php by simulating the exact conditions
    echo "<h2>🧪 Simulate Actual view_payment.php</h2>";
    
    // Get the exact data that view_payment.php would get
    $qry = $conn->query("SELECT t.*,concat(t.firstname,' ',t.lastname) as name,h.house_no,h.price FROM tenants t inner join houses h on h.id = t.house_id where t.id = $tenant_id");
    if ($qry && $qry->num_rows > 0) {
        $view_data = $qry->fetch_array();
        foreach($view_data as $k => $val){
            if (!is_numeric($k)) {
                $$k = $val;
            }
        }
        
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
        echo "<h3>Data that view_payment.php receives:</h3>";
        echo "<p><strong>\$date_in:</strong> '$date_in'</p>";
        echo "<p><strong>\$name:</strong> '$name'</p>";
        echo "<p><strong>\$price:</strong> '$price'</p>";
        
        // Test the exact code from view_payment.php lines 54-80
        echo "<h4>Exact view_payment.php logic (lines 54-80):</h4>";
        ob_start();
        ?>
        <p>Rent Started: <b><?php
            // Robust date formatting
            if (empty($date_in) || $date_in == '0000-00-00') {
                echo "Invalid Date";
            } else {
                // Clean the date string
                $date_in = trim($date_in);
                
                // Try DateTime class first (more reliable)
                try {
                    $date = DateTime::createFromFormat('Y-m-d', $date_in);
                    if ($date && $date->format('Y-m-d') === $date_in) {
                        echo $date->format('M d, Y');
                    } else {
                        throw new Exception('Invalid format');
                    }
                } catch (Exception $e) {
                    // Fallback to strtotime
                    $timestamp = strtotime($date_in);
                    if ($timestamp !== false) {
                        echo date("M d, Y", $timestamp);
                    } else {
                        echo "Invalid Date";
                    }
                }
            }
        ?></b></p>
        <?php
        $output = ob_get_clean();
        echo "<div style='background: white; padding: 10px; border: 1px solid #ddd;'>$output</div>";
        echo "</div>";
    }
    
    // Test direct access to view_payment.php
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
    echo "<h3>🔗 Direct Test Links:</h3>";
    echo "<p><a href='view_payment.php?id=$tenant_id' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Open view_payment.php directly</a></p>";
    echo "<p><a href='debug_new_tenant_date.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Open date debug page</a></p>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ No tenants found in database.</p>";
}

// Test if there are any PHP errors or warnings
echo "<h2>🚨 Error Check:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Timezone: " . date_default_timezone_get() . "</p>";

// Test some known working dates
echo "<h2>✅ Control Test (Known Working Dates):</h2>";
$test_dates = ['2024-01-01', '2024-06-15', '2024-12-31'];
foreach ($test_dates as $test_date) {
    $ts = strtotime($test_date);
    echo "<p>$test_date → " . ($ts === false ? 'FAILED' : date("M d, Y", $ts)) . "</p>";
}
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
