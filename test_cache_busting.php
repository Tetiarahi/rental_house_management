<!DOCTYPE html>
<html>
<head>
    <title>Cache Busting Test - Tenant Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border: 1px solid #dee2e6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        input[type="date"] { padding: 8px; font-size: 14px; margin: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>🧪 Cache Busting Test - Tenant Form Date Issue</h1>

<?php 
include 'db_connect.php'; 

echo "<div class='test-section'>";
echo "<h2>Test 1: Current Database State</h2>";

$all_tenants = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1 ORDER BY id");

if ($all_tenants && $all_tenants->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Database Date</th><th>Form Value</th><th>Status</th></tr>";
    
    while ($row = $all_tenants->fetch_assoc()) {
        $tenant_id = $row['id'];
        $name = $row['firstname'] . ' ' . $row['lastname'];
        $date_in = $row['date_in'];
        
        // Apply the simplified logic
        $form_value = (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d');
        $status = ($date_in != '0000-00-00' && $date_in != '') ? "✅ Valid" : "🔧 Fixed";
        
        echo "<tr>";
        echo "<td>$tenant_id</td>";
        echo "<td>$name</td>";
        echo "<td>$date_in</td>";
        echo "<td class='success'>$form_value</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 2: Simulate Edit Form (Cache Buster)</h2>";
echo "<p>This simulates the exact logic from manage_tenant.php with cache busting:</p>";

// Get first tenant for testing
$tenant = $conn->query("SELECT * FROM tenants WHERE status = 1 LIMIT 1");
if ($tenant && $tenant->num_rows > 0) {
    $data = $tenant->fetch_assoc();
    foreach($data as $k => $val){
        if (!is_numeric($k)) {
            $$k = $val;
        }
    }
    
    echo "<h3>Testing with: $firstname $lastname</h3>";
    echo "<p><strong>Database date_in:</strong> $date_in</p>";
    
    // Cache busting timestamp
    $cache_buster = time();
    
    echo "<h4>Form with Cache Buster (timestamp: $cache_buster):</h4>";
    $form_value = (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d');
    
    echo "<form>";
    echo "<label>Registration Date:</label><br>";
    echo "<input type='date' name='date_in' value='$form_value' max='" . date('Y-m-d') . "' required>";
    echo "<input type='hidden' name='cache_buster' value='$cache_buster'>";
    echo "</form>";
    
    echo "<p><strong>Expected Value:</strong> <span class='success'>$form_value</span></p>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 3: JavaScript Override Test</h2>";
echo "<p>Testing if JavaScript can fix the issue:</p>";

echo "<form id='test-form'>";
echo "<label>Test Date Input (will be set by JavaScript):</label><br>";
echo "<input type='date' id='test-date-input' name='date_in' value=''>";
echo "</form>";

echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "    var dateInput = document.getElementById('test-date-input');";
echo "    var currentDate = new Date().toISOString().split('T')[0];";
echo "    dateInput.value = currentDate;";
echo "    console.log('JavaScript set date to:', currentDate);";
echo "});";
echo "</script>";

echo "<p><strong>This input should show today's date via JavaScript.</strong></p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 4: Direct HTML Test</h2>";
echo "<p>Testing different date values directly in HTML:</p>";

$test_dates = [
    ['Current Date', date('Y-m-d')],
    ['Valid Past Date', '2024-06-01'],
    ['Empty Value', ''],
    ['Invalid Value', 'invalid']
];

echo "<table>";
echo "<tr><th>Test Case</th><th>Value</th><th>HTML Input</th><th>Browser Display</th></tr>";

foreach ($test_dates as $test) {
    $label = $test[0];
    $value = $test[1];
    
    echo "<tr>";
    echo "<td>$label</td>";
    echo "<td>" . ($value ?: 'empty') . "</td>";
    echo "<td><code>&lt;input type=\"date\" value=\"$value\"&gt;</code></td>";
    echo "<td><input type='date' value='$value'></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 5: Browser Information</h2>";
echo "<p>Browser and server information:</p>";

echo "<ul>";
echo "<li><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "</li>";
echo "<li><strong>Cache Buster:</strong> " . time() . "</li>";
echo "</ul>";

echo "<script>";
echo "console.log('Browser:', navigator.userAgent);";
echo "console.log('Current Date:', new Date().toISOString().split('T')[0]);";
echo "console.log('Cache Buster:', " . time() . ");";
echo "</script>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🚨 Troubleshooting Instructions</h2>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h3>If you're still seeing 'Invalid Date':</h3>";
echo "<ol>";
echo "<li><strong>Hard Refresh:</strong> Press Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>";
echo "<li><strong>Clear All Cache:</strong> Browser Settings → Clear browsing data → All time</li>";
echo "<li><strong>Try Incognito Mode:</strong> Open a private/incognito window</li>";
echo "<li><strong>Check Browser Console:</strong> Press F12 → Console tab → look for errors</li>";
echo "<li><strong>Disable Extensions:</strong> Try with all browser extensions disabled</li>";
echo "<li><strong>Try Different Browser:</strong> Test in Chrome, Firefox, Edge</li>";
echo "<li><strong>Check Network Tab:</strong> F12 → Network → see if files are loading from cache</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔧 Alternative Fix</h2>";
echo "<div style='background-color: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb;'>";
echo "<h3>If the issue persists, try this JavaScript fix:</h3>";
echo "<p>Add this to the bottom of manage_tenant.php:</p>";
echo "<pre style='background-color: #e9ecef; padding: 10px; border-radius: 5px;'>";
echo "&lt;script&gt;\n";
echo "document.addEventListener('DOMContentLoaded', function() {\n";
echo "    var dateInput = document.querySelector('input[name=\"date_in\"]');\n";
echo "    if (dateInput && (dateInput.value === '' || dateInput.value === 'Invalid Date')) {\n";
echo "        dateInput.value = new Date().toISOString().split('T')[0];\n";
echo "    }\n";
echo "});\n";
echo "&lt;/script&gt;";
echo "</pre>";
echo "</div>";
echo "</div>";
?>

</body>
</html>
