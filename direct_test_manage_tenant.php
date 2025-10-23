<?php 
include 'db_connect.php'; 

echo "<h2>Direct Test of manage_tenant.php Logic</h2>";

// Test 1: New tenant (no ID)
echo "<h3>Test 1: Adding New Tenant (No ID)</h3>";
echo "<p>This simulates what happens when you click 'Add New Tenant'</p>";

// Simulate no $_GET['id'] - this is what happens for new tenants
unset($date_in); // Make sure no date_in is set

echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h4>Date Input Value for New Tenant:</h4>";

// This is the exact logic from manage_tenant.php for new tenants
if (isset($date_in) && $date_in != '0000-00-00' && $date_in != '' && strlen($date_in) == 10 && substr($date_in, 4, 1) == '-' && substr($date_in, 7, 1) == '-') {
    $new_tenant_value = $date_in;
    echo "<p><strong>Result:</strong> $new_tenant_value (using existing date)</p>";
} else {
    $new_tenant_value = date('Y-m-d');
    echo "<p><strong>Result:</strong> $new_tenant_value (using current date)</p>";
}

echo "<p><strong>HTML Output:</strong></p>";
echo "<code>&lt;input type=\"date\" value=\"$new_tenant_value\"&gt;</code>";
echo "<p><strong>Actual Input:</strong></p>";
echo "<input type='date' value='$new_tenant_value' style='padding: 8px; font-size: 14px;'>";
echo "</div>";

// Test 2: Editing existing tenant
echo "<h3>Test 2: Editing Existing Tenant</h3>";
$tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 LIMIT 1");
if ($tenant && $tenant->num_rows > 0) {
    $data = $tenant->fetch_assoc();
    
    // Simulate the exact process from manage_tenant.php
    foreach($data as $k => $val){
        if (!is_numeric($k)) {
            $$k = $val;
        }
    }
    
    echo "<p>Testing with tenant: <strong>$firstname $lastname</strong></p>";
    echo "<p>Database date_in: <strong>$date_in</strong></p>";
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h4>Date Input Value for Existing Tenant:</h4>";
    
    // Apply the exact logic from manage_tenant.php
    if (isset($date_in) && $date_in != '0000-00-00' && $date_in != '' && strlen($date_in) == 10 && substr($date_in, 4, 1) == '-' && substr($date_in, 7, 1) == '-') {
        $edit_tenant_value = $date_in;
        echo "<p><strong>Result:</strong> $edit_tenant_value (using database date)</p>";
        echo "<p style='color: green;'>✅ Should show actual registration date</p>";
    } else {
        $edit_tenant_value = date('Y-m-d');
        echo "<p><strong>Result:</strong> $edit_tenant_value (using current date)</p>";
        echo "<p style='color: orange;'>🔧 Database date was invalid, using current date</p>";
    }
    
    echo "<p><strong>HTML Output:</strong></p>";
    echo "<code>&lt;input type=\"date\" value=\"$edit_tenant_value\"&gt;</code>";
    echo "<p><strong>Actual Input:</strong></p>";
    echo "<input type='date' value='$edit_tenant_value' style='padding: 8px; font-size: 14px;'>";
    echo "</div>";
}

// Test 3: Check the actual file content
echo "<h3>Test 3: Current manage_tenant.php File Content</h3>";
echo "<p>Let's see what's actually in the file around the date input:</p>";

$file_content = file_get_contents('manage_tenant.php');
$lines = explode("\n", $file_content);

// Find the date input section
$start_line = 0;
$end_line = 0;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'name="date_in"') !== false) {
        $start_line = max(0, $i - 3);
        $end_line = min(count($lines) - 1, $i + 8);
        break;
    }
}

if ($start_line > 0) {
    echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h4>Lines " . ($start_line + 1) . " to " . ($end_line + 1) . " from manage_tenant.php:</h4>";
    echo "<pre style='background-color: #e9ecef; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    for ($i = $start_line; $i <= $end_line; $i++) {
        $line_num = $i + 1;
        $line_content = htmlspecialchars($lines[$i]);
        if (strpos($lines[$i], 'name="date_in"') !== false) {
            echo "<strong style='background-color: yellow;'>$line_num: $line_content</strong>\n";
        } else {
            echo "$line_num: $line_content\n";
        }
    }
    echo "</pre>";
    echo "</div>";
}

// Test 4: Browser compatibility test
echo "<h3>Test 4: Browser Compatibility Test</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>Different Date Input Tests:</h4>";

$test_values = [
    date('Y-m-d'),
    '2024-07-15',
    '',
    'invalid'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='padding: 8px;'>Test Value</th><th style='padding: 8px;'>HTML Input</th><th style='padding: 8px;'>Browser Display</th></tr>";

foreach ($test_values as $test_val) {
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . ($test_val ?: 'empty') . "</td>";
    echo "<td style='padding: 8px;'><code>&lt;input type=\"date\" value=\"$test_val\"&gt;</code></td>";
    echo "<td style='padding: 8px;'><input type='date' value='$test_val' style='padding: 5px;'></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test 5: Possible solutions
echo "<h3>Test 5: Alternative Solutions</h3>";
echo "<div style='background-color: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb;'>";
echo "<h4>If the issue persists, try these approaches:</h4>";
echo "<ol>";
echo "<li><strong>Ultra-Simple Approach:</strong> Use a basic ternary operator</li>";
echo "<li><strong>JavaScript Override:</strong> Set the value using JavaScript after page load</li>";
echo "<li><strong>Direct File Edit:</strong> Manually edit the file with a simple value</li>";
echo "<li><strong>Cache Bypass:</strong> Add a timestamp to force cache refresh</li>";
echo "</ol>";

echo "<h4>Ultra-Simple Code (Alternative):</h4>";
echo "<pre style='background-color: #e9ecef; padding: 10px; border-radius: 5px;'>";
echo 'value="<?php echo (isset($date_in) && $date_in != \'0000-00-00\' && $date_in != \'\') ? $date_in : date(\'Y-m-d\'); ?>"';
echo "</pre>";
echo "</div>";

echo "<div style='background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
echo "<h4>🚨 Debugging Steps:</h4>";
echo "<ol>";
echo "<li><strong>Check Browser Console:</strong> Press F12 → Console tab → look for JavaScript errors</li>";
echo "<li><strong>Check Network Tab:</strong> F12 → Network tab → see if manage_tenant.php loads correctly</li>";
echo "<li><strong>View Page Source:</strong> Right-click → View Source → search for 'date_in'</li>";
echo "<li><strong>Try Direct Access:</strong> Access manage_tenant.php directly (not through modal)</li>";
echo "<li><strong>Disable JavaScript:</strong> Temporarily disable JS to see if it's interfering</li>";
echo "</ol>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
pre { margin: 0; font-family: 'Courier New', monospace; }
code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: 'Courier New', monospace; }
</style>
