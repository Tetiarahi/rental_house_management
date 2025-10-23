<?php 
include 'db_connect.php'; 

echo "<h2>Debug Edit Form Date Issue</h2>";

// Test with existing tenant
if(isset($_GET['id'])){
    $tenant_id = $_GET['id'];
    echo "<h3>Editing Tenant ID: $tenant_id</h3>";
    
    $qry = $conn->query("SELECT * FROM tenants where id= ".$tenant_id);
    if ($qry && $qry->num_rows > 0) {
        $tenant_data = $qry->fetch_array();
        foreach($tenant_data as $k => $val){
            if (!is_numeric($k)) {
                $$k=$val;
            }
        }
        
        echo "<h4>Raw Database Data:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th style='padding: 8px;'>Field</th><th style='padding: 8px;'>Value</th></tr>";
        echo "<tr><td style='padding: 8px;'>date_in</td><td style='padding: 8px;'>" . ($date_in ?? 'NULL') . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>firstname</td><td style='padding: 8px;'>" . ($firstname ?? 'NULL') . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>lastname</td><td style='padding: 8px;'>" . ($lastname ?? 'NULL') . "</td></tr>";
        echo "</table>";
        
        echo "<h4>Date Processing Test:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th style='padding: 8px;'>Test</th><th style='padding: 8px;'>Result</th></tr>";
        
        echo "<tr><td style='padding: 8px;'>isset(\$date_in)</td><td style='padding: 8px;'>" . (isset($date_in) ? 'TRUE' : 'FALSE') . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>!empty(\$date_in)</td><td style='padding: 8px;'>" . (!empty($date_in) ? 'TRUE' : 'FALSE') . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>\$date_in != '0000-00-00'</td><td style='padding: 8px;'>" . ($date_in != '0000-00-00' ? 'TRUE' : 'FALSE') . "</td></tr>";
        
        if (isset($date_in)) {
            $timestamp = strtotime($date_in);
            echo "<tr><td style='padding: 8px;'>strtotime(\$date_in)</td><td style='padding: 8px;'>" . ($timestamp !== false ? $timestamp : 'FALSE') . "</td></tr>";
            
            if ($timestamp !== false) {
                $formatted = date("Y-m-d", $timestamp);
                echo "<tr><td style='padding: 8px;'>date('Y-m-d', timestamp)</td><td style='padding: 8px;'>$formatted</td></tr>";
            }
        }
        
        echo "</table>";
        
        echo "<h4>Final Form Value:</h4>";
        // Simulate the exact logic from the form
        if (isset($date_in) && !empty($date_in) && $date_in != '0000-00-00') {
            $timestamp = strtotime($date_in);
            if ($timestamp !== false) {
                $form_value = date("Y-m-d", $timestamp);
                $status = "✅ Valid date";
            } else {
                $form_value = date('Y-m-d');
                $status = "🔧 Invalid format - using current date";
            }
        } else {
            $form_value = date('Y-m-d');
            $status = "🔧 Empty/Invalid - using current date";
        }
        
        echo "<p><strong>Form Input Value:</strong> <span style='color: green;'>$form_value</span></p>";
        echo "<p><strong>Status:</strong> $status</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Tenant not found!</p>";
    }
} else {
    echo "<h3>Adding New Tenant</h3>";
    echo "<p>For new tenants, the form should show current date: <strong>" . date('Y-m-d') . "</strong></p>";
}

echo "<h3>Test All Active Tenants</h3>";
$all_tenants = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1");

if ($all_tenants && $all_tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Name</th>";
    echo "<th style='padding: 8px;'>Raw Date</th>";
    echo "<th style='padding: 8px;'>Form Would Show</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Test Edit</th>";
    echo "</tr>";
    
    while ($row = $all_tenants->fetch_assoc()) {
        $test_date_in = $row['date_in'];
        $name = $row['firstname'] . ' ' . $row['lastname'];
        
        // Test the form logic
        if (isset($test_date_in) && !empty($test_date_in) && $test_date_in != '0000-00-00') {
            $timestamp = strtotime($test_date_in);
            if ($timestamp !== false) {
                $form_value = date("Y-m-d", $timestamp);
                $status = "✅ Valid";
            } else {
                $form_value = date('Y-m-d');
                $status = "🔧 Invalid format";
            }
        } else {
            $form_value = date('Y-m-d');
            $status = "🔧 Empty/Invalid";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['id']}</td>";
        echo "<td style='padding: 8px;'>$name</td>";
        echo "<td style='padding: 8px;'>$test_date_in</td>";
        echo "<td style='padding: 8px; color: green;'>$form_value</td>";
        echo "<td style='padding: 8px;'>$status</td>";
        echo "<td style='padding: 8px;'><a href='?id={$row['id']}' style='color: blue;'>Test Edit</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Possible Issues & Solutions</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>If you're still seeing 'Invalid Date':</h4>";
echo "<ol>";
echo "<li><strong>Browser Cache:</strong> Clear your browser cache and refresh</li>";
echo "<li><strong>Modal Cache:</strong> The edit form might be cached in the modal</li>";
echo "<li><strong>JavaScript Issues:</strong> Check browser console for errors</li>";
echo "<li><strong>Server Cache:</strong> Restart your web server (Apache/Nginx)</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Quick Test Form</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<p>Test the date input directly:</p>";
echo "<input type='date' value='" . date('Y-m-d') . "' style='padding: 5px;'>";
echo "<p><small>This should show today's date: " . date('Y-m-d') . "</small></p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
</style>
