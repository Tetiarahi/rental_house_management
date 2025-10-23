<?php 
include 'db_connect.php'; 

echo "<h2>Test Date Input - Simplified</h2>";

// Get a tenant for testing
$tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 LIMIT 1");
if ($tenant && $tenant->num_rows > 0) {
    $data = $tenant->fetch_assoc();
    $date_in = $data['date_in'];
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    
    echo "<h3>Testing with: $firstname $lastname</h3>";
    echo "<p><strong>Database date_in:</strong> $date_in</p>";
    
    // Test the exact logic from manage_tenant.php
    echo "<h4>Date Processing Steps:</h4>";
    echo "<ol>";
    
    echo "<li><strong>isset(\$date_in):</strong> " . (isset($date_in) ? 'TRUE' : 'FALSE') . "</li>";
    echo "<li><strong>!empty(\$date_in):</strong> " . (!empty($date_in) ? 'TRUE' : 'FALSE') . "</li>";
    echo "<li><strong>\$date_in != '0000-00-00':</strong> " . ($date_in != '0000-00-00' ? 'TRUE' : 'FALSE') . "</li>";
    
    if (isset($date_in) && !empty($date_in) && $date_in != '0000-00-00') {
        echo "<li><strong>Condition met - processing valid date</strong></li>";
        $timestamp = strtotime($date_in);
        echo "<li><strong>strtotime('$date_in'):</strong> " . ($timestamp !== false ? $timestamp . " (" . date('Y-m-d H:i:s', $timestamp) . ")" : 'FALSE') . "</li>";
        
        if ($timestamp !== false) {
            $final_value = date("Y-m-d", $timestamp);
            echo "<li><strong>Final value:</strong> $final_value</li>";
        } else {
            $final_value = date('Y-m-d');
            echo "<li><strong>Using current date:</strong> $final_value</li>";
        }
    } else {
        $final_value = date('Y-m-d');
        echo "<li><strong>Using current date (invalid/empty):</strong> $final_value</li>";
    }
    
    echo "</ol>";
    
    echo "<h4>HTML Output Test:</h4>";
    echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<p><strong>The HTML input would be:</strong></p>";
    echo "<code>&lt;input type=\"date\" value=\"$final_value\"&gt;</code>";
    echo "<p><strong>Actual input:</strong></p>";
    echo "<input type='date' value='$final_value' style='padding: 5px; font-size: 14px;'>";
    echo "</div>";
    
} else {
    echo "<p>No tenants found for testing.</p>";
}

echo "<h3>Test Different Scenarios</h3>";
$test_scenarios = [
    ['Valid Date', '2024-05-01'],
    ['Invalid Date', '0000-00-00'],
    ['Empty String', ''],
    ['Null Value', null],
    ['Invalid Format', 'invalid-date'],
    ['Future Date', '2026-01-01']
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th style='padding: 8px;'>Scenario</th>";
echo "<th style='padding: 8px;'>Input Value</th>";
echo "<th style='padding: 8px;'>Processing Result</th>";
echo "<th style='padding: 8px;'>HTML Input</th>";
echo "</tr>";

foreach ($test_scenarios as $scenario) {
    $label = $scenario[0];
    $test_date = $scenario[1];
    
    // Apply the same logic
    if (isset($test_date) && !empty($test_date) && $test_date != '0000-00-00') {
        $timestamp = strtotime($test_date);
        if ($timestamp !== false) {
            $result = date("Y-m-d", $timestamp);
            $status = "✅ Valid";
        } else {
            $result = date('Y-m-d');
            $status = "🔧 Invalid format → current date";
        }
    } else {
        $result = date('Y-m-d');
        $status = "🔧 Empty/Invalid → current date";
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='padding: 8px;'>" . ($test_date ?? 'null') . "</td>";
    echo "<td style='padding: 8px;'>$result ($status)</td>";
    echo "<td style='padding: 8px;'><input type='date' value='$result' style='padding: 3px;'></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Troubleshooting Steps</h3>";
echo "<div style='background-color: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb;'>";
echo "<h4>If you're still seeing 'Invalid Date' in the edit form:</h4>";
echo "<ol>";
echo "<li><strong>Hard Refresh:</strong> Press Ctrl+F5 (or Cmd+Shift+R on Mac) to clear cache</li>";
echo "<li><strong>Check Browser Console:</strong> Press F12 → Console tab → look for JavaScript errors</li>";
echo "<li><strong>Test Direct Access:</strong> Try accessing the edit form directly instead of through modal</li>";
echo "<li><strong>Check Network Tab:</strong> F12 → Network tab → see if manage_tenant.php is loading correctly</li>";
echo "<li><strong>Disable Browser Extensions:</strong> Try in incognito/private mode</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Alternative Simple Fix</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p>If the issue persists, we can try a simpler approach:</p>";
echo "<code>";
echo "value=\"<?php echo isset(\$date_in) && \$date_in != '0000-00-00' && \$date_in != '' ? \$date_in : date('Y-m-d'); ?>\"";
echo "</code>";
echo "<p>This bypasses the strtotime() function entirely for valid-looking dates.</p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>
