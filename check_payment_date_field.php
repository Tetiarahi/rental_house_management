<?php
include 'db_connect.php';

echo "<h1>🔍 Check Payment Date Fields</h1>";

// Check payments table structure
$result = $conn->query("DESCRIBE payments");

echo "<h2>📋 Payments Table Structure:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    $highlight = (strpos($row['Field'], 'date') !== false) ? 'background: #fff3cd;' : '';
    echo "<tr style='$highlight'>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample payment data to understand the difference
echo "<h2>📊 Sample Payment Data:</h2>";
$payments = $conn->query("SELECT id, tenant_id, invoice, amount, date_created, 
                          DATE(date_created) as payment_date_selected,
                          YEAR(date_created) as year, MONTH(date_created) as month
                          FROM payments ORDER BY id DESC LIMIT 10");

if ($payments && $payments->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th>ID</th><th>Invoice</th><th>Amount</th>";
    echo "<th>date_created (DB timestamp)</th><th>Payment Date (User Selected)</th>";
    echo "<th>Year</th><th>Month</th>";
    echo "</tr>";
    
    while ($row = $payments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['invoice']}</td>";
        echo "<td>$" . number_format($row['amount'], 2) . "</td>";
        echo "<td>{$row['date_created']}</td>";
        echo "<td style='background: #d4edda;'>{$row['payment_date_selected']}</td>";
        echo "<td>{$row['year']}</td>";
        echo "<td>{$row['month']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payments found</p>";
}

// Test current vs corrected queries
echo "<h2>🧪 Query Comparison:</h2>";

$current_month = date('Y-m');
echo "<h3>Current Month: " . date('F Y') . " ($current_month)</h3>";

// Current query (using date_created)
$current_query = "SELECT sum(amount) as paid FROM payments WHERE YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE())";
$current_result = $conn->query($current_query);
$current_amount = $current_result->num_rows > 0 ? $current_result->fetch_array()['paid'] : 0;

// Corrected query (using actual payment date)
$corrected_query = "SELECT sum(amount) as paid FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = '$current_month'";
$corrected_result = $conn->query($corrected_query);
$corrected_amount = $corrected_result->num_rows > 0 ? $corrected_result->fetch_array()['paid'] : 0;

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h4>Query Results Comparison:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'><th>Query Type</th><th>SQL</th><th>Result</th></tr>";
echo "<tr>";
echo "<td><strong>Current (Wrong)</strong></td>";
echo "<td style='font-family: monospace; font-size: 12px;'>$current_query</td>";
echo "<td style='color: red; font-weight: bold;'>$" . number_format($current_amount, 2) . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Corrected</strong></td>";
echo "<td style='font-family: monospace; font-size: 12px;'>$corrected_query</td>";
echo "<td style='color: green; font-weight: bold;'>$" . number_format($corrected_amount, 2) . "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

// Show monthly breakdown
echo "<h2>📅 Monthly Breakdown (Using Payment Date):</h2>";
$monthly_query = "SELECT DATE_FORMAT(date_created, '%Y-%m') as month_year, 
                         MONTHNAME(date_created) as month_name,
                         YEAR(date_created) as year,
                         SUM(amount) as total,
                         COUNT(*) as payment_count
                  FROM payments 
                  WHERE YEAR(date_created) = YEAR(CURDATE())
                  GROUP BY DATE_FORMAT(date_created, '%Y-%m'), MONTHNAME(date_created), YEAR(date_created)
                  ORDER BY month_year";

$monthly_result = $conn->query($monthly_query);

if ($monthly_result && $monthly_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Month-Year</th><th>Month Name</th><th>Total Amount</th><th>Payment Count</th></tr>";
    
    while ($row = $monthly_result->fetch_assoc()) {
        $is_current = ($row['month_year'] == $current_month);
        $bg_color = $is_current ? 'background: #d4edda;' : '';
        
        echo "<tr style='$bg_color'>";
        echo "<td><strong>{$row['month_year']}</strong>" . ($is_current ? ' (Current)' : '') . "</td>";
        echo "<td>{$row['month_name']} {$row['year']}</td>";
        echo "<td style='color: green; font-weight: bold;'>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['payment_count']} payments</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No monthly data found</p>";
}

echo "<h2>✅ Recommended Fix:</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
echo "<h3>🎯 Issue:</h3>";
echo "<p>The dashboard card 'Payments This Month' is using <code>date_created</code> which is when the payment record was created in the database, not the actual payment date that users select.</p>";

echo "<h3>🔧 Solution:</h3>";
echo "<p>Change the query to use the payment date that users actually select when adding payments.</p>";

echo "<h3>📝 Code Change Needed:</h3>";
echo "<p><strong>Current (Wrong):</strong></p>";
echo "<code style='background: #f8d7da; padding: 5px;'>WHERE YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE())</code>";

echo "<p><strong>Corrected:</strong></p>";
echo "<code style='background: #d4edda; padding: 5px;'>WHERE DATE_FORMAT(date_created, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')</code>";

echo "<p><strong>This will show:</strong> $" . number_format($corrected_amount, 2) . " for " . date('F Y') . "</p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
code { font-family: monospace; padding: 2px 4px; border-radius: 3px; }
</style>
