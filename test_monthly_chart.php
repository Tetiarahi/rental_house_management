<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🧪 Test Monthly Payments Chart Data</h1>";

// Test the get_monthly_payments method
$admin = new Action();
$monthly_data = $admin->get_monthly_payments();

echo "<h2>📊 Raw Data from get_monthly_payments():</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<pre>" . $monthly_data . "</pre>";
echo "</div>";

// Parse and display the data
$data = json_decode($monthly_data, true);

if ($data) {
    echo "<h2>📋 Parsed Monthly Data for " . $data['year'] . ":</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Month</th><th>Amount</th><th>Formatted</th></tr>";
    
    $total_year = 0;
    $months_with_data = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $month = $data['months'][$i];
        $amount = $data['amounts'][$i];
        $total_year += $amount;
        
        if ($amount > 0) {
            $months_with_data++;
        }
        
        $color = $amount > 0 ? 'green' : '#999';
        $formatted = '$' . number_format($amount, 2);
        
        echo "<tr>";
        echo "<td><strong>$month</strong></td>";
        echo "<td style='color: $color; font-weight: bold;'>$amount</td>";
        echo "<td style='color: $color; font-weight: bold;'>$formatted</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Summary statistics
    $current_month_index = date('n') - 1; // 0-based index
    $current_month_amount = $data['amounts'][$current_month_index];
    $average_month = $months_with_data > 0 ? $total_year / $months_with_data : 0;
    
    echo "<h2>📈 Summary Statistics:</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
    echo "<div class='row'>";
    echo "<div class='col-md-4'>";
    echo "<h4>Total This Year</h4>";
    echo "<h3 style='color: #007bff;'>$" . number_format($total_year, 2) . "</h3>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<h4>This Month (" . date('M') . ")</h4>";
    echo "<h3 style='color: #28a745;'>$" . number_format($current_month_amount, 2) . "</h3>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<h4>Average Monthly</h4>";
    echo "<h3 style='color: #17a2b8;'>$" . number_format($average_month, 2) . "</h3>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Failed to parse JSON data</p>";
}

// Test actual database queries
echo "<h2>🗄️ Direct Database Verification:</h2>";

$current_year = date('Y');
$query = "SELECT MONTH(date_created) as month, MONTHNAME(date_created) as month_name, SUM(amount) as total, COUNT(*) as payment_count
          FROM payments 
          WHERE YEAR(date_created) = $current_year 
          GROUP BY MONTH(date_created), MONTHNAME(date_created)
          ORDER BY MONTH(date_created)";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Month #</th><th>Month Name</th><th>Total Amount</th><th>Payment Count</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['month']}</td>";
        echo "<td>{$row['month_name']}</td>";
        echo "<td style='color: green; font-weight: bold;'>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>{$row['payment_count']} payments</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No payments found for $current_year</p>";
}

// Show all payments for verification
echo "<h2>💳 All Payments in $current_year:</h2>";
$all_payments = $conn->query("SELECT DATE(date_created) as payment_date, amount, invoice, ref_number FROM payments WHERE YEAR(date_created) = $current_year ORDER BY date_created DESC LIMIT 20");

if ($all_payments && $all_payments->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Date</th><th>Invoice</th><th>Reference</th><th>Amount</th></tr>";
    
    while ($payment = $all_payments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$payment['payment_date']}</td>";
        echo "<td>{$payment['invoice']}</td>";
        echo "<td>" . ($payment['ref_number'] ?: '-') . "</td>";
        echo "<td style='color: green; font-weight: bold;'>$" . number_format($payment['amount'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No individual payments found for $current_year</p>";
}

echo "<h2>🔗 Test Dashboard:</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
echo "<p><a href='index.php?page=home' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🏠 View Dashboard with Chart</a></p>";
echo "<p><strong>Expected:</strong> You should see a line graph showing monthly payments for " . date('Y') . "</p>";
echo "</div>";

echo "<h2>✅ Implementation Summary:</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
echo "<h3>🎯 What's Been Added:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Chart.js Integration:</strong> Added Chart.js library to dashboard</li>";
echo "<li>✅ <strong>Monthly Data Endpoint:</strong> Created get_monthly_payments() method</li>";
echo "<li>✅ <strong>AJAX Route:</strong> Added ajax.php route for chart data</li>";
echo "<li>✅ <strong>Line Graph:</strong> Interactive chart showing monthly payment trends</li>";
echo "<li>✅ <strong>Summary Stats:</strong> Total year, current month, and average displays</li>";
echo "<li>✅ <strong>Fixed Dashboard Card:</strong> 'Payments This Month' now shows correct data</li>";
echo "</ul>";

echo "<h3>📊 Chart Features:</h3>";
echo "<ul>";
echo "<li>📈 <strong>Line Graph:</strong> Shows payment trends over 12 months</li>";
echo "<li>🎨 <strong>Interactive:</strong> Hover for detailed amounts</li>";
echo "<li>📱 <strong>Responsive:</strong> Works on all screen sizes</li>";
echo "<li>💰 <strong>Formatted Values:</strong> Currency formatting with commas</li>";
echo "<li>📅 <strong>Current Year:</strong> Automatically shows data for " . date('Y') . "</li>";
echo "</ul>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
.row { display: flex; }
.col-md-4 { flex: 1; padding: 10px; text-align: center; }
</style>
