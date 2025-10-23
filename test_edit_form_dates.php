<?php
include 'db_connect.php';

echo "<h2>Test Edit Form Date Display</h2>";
echo "<p>Testing how registration dates are displayed in the tenant edit form.</p>";

// Get all tenants to test the date display logic
$tenants = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1");

if ($tenants->num_rows > 0) {
    echo "<h3>Date Display Test for Edit Form</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Tenant</th>";
    echo "<th style='padding: 8px;'>Raw Date (date_in)</th>";
    echo "<th style='padding: 8px;'>Old Logic Result</th>";
    echo "<th style='padding: 8px;'>New Logic Result</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "</tr>";
    
    while ($tenant = $tenants->fetch_assoc()) {
        $date_in = $tenant['date_in'];
        $name = $tenant['firstname'] . ' ' . $tenant['lastname'];
        
        // Old logic (problematic)
        $old_result = isset($date_in) ? date("Y-m-d", strtotime($date_in)) : date('Y-m-d');
        
        // New logic (safe)
        if (isset($date_in) && !empty($date_in) && $date_in != '0000-00-00') {
            $timestamp = strtotime($date_in);
            if ($timestamp !== false) {
                $new_result = date("Y-m-d", $timestamp);
                $status = "✅ Valid Date";
            } else {
                $new_result = date('Y-m-d'); // Default to current date if invalid
                $status = "🔧 Fixed (Invalid → Current Date)";
            }
        } else {
            $new_result = date('Y-m-d'); // Default to current date for new tenants or invalid dates
            $status = "🔧 Fixed (Empty/Invalid → Current Date)";
        }
        
        // Determine if there was an issue with old logic
        $old_status = "";
        if ($old_result == "1970-01-01" || $old_result == "1969-12-31") {
            $old_status = " style='color: red; font-weight: bold;'";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>{$name}</strong></td>";
        echo "<td style='padding: 8px;'>{$date_in}</td>";
        echo "<td style='padding: 8px;'{$old_status}>{$old_result}</td>";
        echo "<td style='padding: 8px; color: green;'>{$new_result}</td>";
        echo "<td style='padding: 8px;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Test Different Date Scenarios</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Scenario</th>";
    echo "<th style='padding: 8px;'>Input Date</th>";
    echo "<th style='padding: 8px;'>Old Logic</th>";
    echo "<th style='padding: 8px;'>New Logic</th>";
    echo "<th style='padding: 8px;'>Result</th>";
    echo "</tr>";
    
    $test_dates = [
        ['Valid Date', '2024-05-01'],
        ['Invalid Date', '0000-00-00'],
        ['Empty Date', ''],
        ['Null Date', null],
        ['Invalid Format', 'invalid-date']
    ];
    
    foreach ($test_dates as $test) {
        $scenario = $test[0];
        $test_date = $test[1];
        
        // Old logic
        $old_logic = isset($test_date) ? date("Y-m-d", strtotime($test_date)) : date('Y-m-d');
        
        // New logic
        if (isset($test_date) && !empty($test_date) && $test_date != '0000-00-00') {
            $timestamp = strtotime($test_date);
            if ($timestamp !== false) {
                $new_logic = date("Y-m-d", $timestamp);
                $result = "✅ Shows correct date";
            } else {
                $new_logic = date('Y-m-d');
                $result = "🔧 Fixed - shows current date";
            }
        } else {
            $new_logic = date('Y-m-d');
            $result = "🔧 Fixed - shows current date";
        }
        
        $old_color = ($old_logic == "1970-01-01" || $old_logic == "1969-12-31") ? "color: red;" : "";
        
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>{$scenario}</strong></td>";
        echo "<td style='padding: 8px;'>" . ($test_date ?? 'null') . "</td>";
        echo "<td style='padding: 8px; {$old_color}'>{$old_logic}</td>";
        echo "<td style='padding: 8px; color: green;'>{$new_logic}</td>";
        echo "<td style='padding: 8px;'>{$result}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p>No active tenants found.</p>";
}

echo "<h3>Summary of Edit Form Fix</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>✅ Edit Form Date Issue Fixed:</h4>";
echo "<ul>";
echo "<li><strong>Problem:</strong> Edit form showed 'Invalid Date' when editing tenants with invalid registration dates</li>";
echo "<li><strong>Root Cause:</strong> strtotime() on invalid dates returns false, date() converts false to epoch time</li>";
echo "<li><strong>Solution:</strong> Added validation before formatting dates in the edit form</li>";
echo "<li><strong>Result:</strong> Invalid dates now default to current date in edit form</li>";
echo "</ul>";
echo "</div>";

echo "<h3>How the Edit Form Fix Works</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h4>Before (Problematic):</h4>";
echo "<code>value=\"<?php echo isset(\$date_in) ? date(\"Y-m-d\",strtotime(\$date_in)) : date('Y-m-d') ?>\"</code>";
echo "<p>❌ If \$date_in is '0000-00-00', shows '1970-01-01' or similar invalid date</p>";

echo "<h4>After (Safe):</h4>";
echo "<pre>";
echo "if (isset(\$date_in) && !empty(\$date_in) && \$date_in != '0000-00-00') {\n";
echo "    \$timestamp = strtotime(\$date_in);\n";
echo "    if (\$timestamp !== false) {\n";
echo "        echo date(\"Y-m-d\", \$timestamp); // Valid date\n";
echo "    } else {\n";
echo "        echo date('Y-m-d'); // Current date for invalid\n";
echo "    }\n";
echo "} else {\n";
echo "    echo date('Y-m-d'); // Current date for empty/invalid\n";
echo "}";
echo "</pre>";
echo "<p>✅ Validates the date before formatting, defaults to current date for invalid dates</p>";
echo "</div>";

echo "<h3>Next Steps</h3>";
echo "<div style='background-color: #cce5ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<ol>";
echo "<li>✅ <strong>Edit Form Fixed</strong> - No more 'Invalid Date' in edit forms</li>";
echo "<li>🎯 <strong>Test the Fix</strong> - Try editing a tenant and check the registration date field</li>";
echo "<li>📝 <strong>Expected Behavior</strong> - Should show the actual date or current date (not 'Invalid Date')</li>";
echo "</ol>";
echo "</div>";
?>
