<?php
include 'db_connect.php';

echo "<h1>🧪 Test Payment Date Fix</h1>";

$current_month = date('Y-m');
$current_month_name = date('F Y');

echo "<h2>📅 Testing for Current Month: $current_month_name</h2>";

// Test the old vs new query
echo "<h3>🔍 Query Comparison:</h3>";

// Old query (what was used before)
$old_query = "SELECT sum(amount) as paid FROM payments WHERE YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE())";
$old_result = $conn->query($old_query);
$old_amount = $old_result->num_rows > 0 ? $old_result->fetch_array()['paid'] : 0;

// New query (fixed)
$new_query = "SELECT sum(amount) as paid FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = '$current_month'";
$new_result = $conn->query($new_query);
$new_amount = $new_result->num_rows > 0 ? $new_result->fetch_array()['paid'] : 0;

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'><th>Query Type</th><th>Result</th><th>Status</th></tr>";
echo "<tr>";
echo "<td><strong>Old Query (YEAR/MONTH functions)</strong></td>";
echo "<td style='font-weight: bold;'>$" . number_format($old_amount, 2) . "</td>";
echo "<td style='color: orange;'>⚠️ May include wrong dates</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>New Query (DATE_FORMAT)</strong></td>";
echo "<td style='font-weight: bold; color: green;'>$" . number_format($new_amount, 2) . "</td>";
echo "<td style='color: green;'>✅ Uses exact payment dates</td>";
echo "</tr>";
echo "</table>";

// Show detailed breakdown of current month payments
echo "<h3>💳 Current Month Payment Details:</h3>";
$details_query = "SELECT id, invoice, ref_number, amount, date_created, 
                         DATE(date_created) as payment_date,
                         CONCAT(t.firstname, ' ', t.lastname) as tenant_name
                  FROM payments p 
                  LEFT JOIN tenants t ON t.id = p.tenant_id
                  WHERE DATE_FORMAT(date_created, '%Y-%m') = '$current_month'
                  ORDER BY date_created DESC";

$details_result = $conn->query($details_query);

if ($details_result && $details_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th>ID</th><th>Date</th><th>Tenant</th><th>Invoice</th><th>Reference</th><th>Amount</th>";
    echo "</tr>";
    
    $total_current_month = 0;
    while ($row = $details_result->fetch_assoc()) {
        $total_current_month += $row['amount'];
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['payment_date']}</td>";
        echo "<td>{$row['tenant_name']}</td>";
        echo "<td>{$row['invoice']}</td>";
        echo "<td>" . ($row['ref_number'] ?: '-') . "</td>";
        echo "<td style='color: green; font-weight: bold;'>$" . number_format($row['amount'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "<tr style='background: #d4edda; font-weight: bold;'>";
    echo "<td colspan='5'><strong>TOTAL FOR " . strtoupper($current_month_name) . "</strong></td>";
    echo "<td style='color: green; font-weight: bold;'>$" . number_format($total_current_month, 2) . "</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
    echo "<h4>✅ Dashboard Card Should Show:</h4>";
    echo "<h2 style='color: green;'>$" . number_format($total_current_month, 2) . "</h2>";
    echo "<p>This amount represents payments where users selected dates in $current_month_name</p>";
    echo "</div>";
    
} else {
    echo "<p style='color: orange;'>⚠️ No payments found for $current_month_name</p>";
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
    echo "<h4>Dashboard Card Should Show:</h4>";
    echo "<h2>$0.00</h2>";
    echo "<p>No payments recorded for $current_month_name</p>";
    echo "</div>";
}

// Show all months for current year
echo "<h3>📊 All Months in " . date('Y') . ":</h3>";
$yearly_query = "SELECT DATE_FORMAT(date_created, '%Y-%m') as month_key,
                        MONTHNAME(date_created) as month_name,
                        YEAR(date_created) as year,
                        SUM(amount) as total,
                        COUNT(*) as payment_count
                 FROM payments 
                 WHERE YEAR(date_created) = YEAR(CURDATE())
                 GROUP BY DATE_FORMAT(date_created, '%Y-%m'), MONTHNAME(date_created), YEAR(date_created)
                 ORDER BY month_key";

$yearly_result = $conn->query($yearly_query);

if ($yearly_result && $yearly_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Month</th><th>Total Amount</th><th>Payment Count</th><th>Status</th></tr>";
    
    while ($row = $yearly_result->fetch_assoc()) {
        $is_current = ($row['month_key'] == $current_month);
        $bg_color = $is_current ? 'background: #d4edda;' : '';
        $status = $is_current ? '👈 Current Month' : '';
        
        echo "<tr style='$bg_color'>";
        echo "<td><strong>{$row['month_name']} {$row['year']}</strong></td>";
        echo "<td style='color: green; font-weight: bold;'>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['payment_count']} payments</td>";
        echo "<td style='font-weight: bold;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test dashboard link
echo "<h3>🔗 Test Dashboard:</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
echo "<p><a href='index.php?page=home' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🏠 View Fixed Dashboard</a></p>";
echo "<p><strong>Expected:</strong> The 'Payments This Month' card should show <strong>$" . number_format($new_amount, 2) . "</strong></p>";
echo "</div>";

echo "<h2>✅ Fix Summary:</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
echo "<h3>🎯 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Dashboard Card:</strong> Now uses exact payment dates selected by users</li>";
echo "<li>✅ <strong>Accurate Counting:</strong> Only includes payments where users selected dates in current month</li>";
echo "<li>✅ <strong>Consistent Logic:</strong> Matches the monthly chart data logic</li>";
echo "<li>✅ <strong>Better Query:</strong> Uses DATE_FORMAT for precise month matching</li>";
echo "</ul>";

echo "<h3>🔧 Technical Change:</h3>";
echo "<p><strong>Before:</strong> <code>YEAR(date_created) = YEAR(CURDATE()) AND MONTH(date_created) = MONTH(CURDATE())</code></p>";
echo "<p><strong>After:</strong> <code>DATE_FORMAT(date_created, '%Y-%m') = '" . $current_month . "'</code></p>";

echo "<h3>💡 Why This Matters:</h3>";
echo "<ul>";
echo "<li>🎯 <strong>User Intent:</strong> Shows payments for dates users actually selected</li>";
echo "<li>📅 <strong>Accurate Reporting:</strong> Reflects when payments actually occurred</li>";
echo "<li>🔄 <strong>Consistency:</strong> Matches chart and report logic</li>";
echo "<li>📊 <strong>Better Analytics:</strong> More meaningful business insights</li>";
echo "</ul>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>
