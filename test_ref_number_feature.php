<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🧪 Test Reference Number Feature</h1>";

// Check if ref_number column exists
echo "<h2>📋 Step 1: Database Column Check</h2>";
$columns = $conn->query("SHOW COLUMNS FROM payments LIKE 'ref_number'");
$ref_number_exists = $columns->num_rows > 0;

if ($ref_number_exists) {
    echo "<p style='color: green; font-weight: bold;'>✅ ref_number column exists</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ ref_number column missing - adding it now...</p>";
    $add_column = "ALTER TABLE payments ADD COLUMN ref_number VARCHAR(100) NULL AFTER invoice";
    if ($conn->query($add_column)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully added ref_number column</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to add column: " . $conn->error . "</p>";
    }
}

// Test adding a payment with reference number
echo "<h2>💾 Step 2: Test Adding Payment with Reference Number</h2>";

// Get an active tenant for testing
$tenant_query = $conn->query("SELECT id, firstname, lastname FROM tenants WHERE status = 1 LIMIT 1");
if ($tenant_query && $tenant_query->num_rows > 0) {
    $tenant = $tenant_query->fetch_assoc();
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h3>📝 Test Payment Data:</h3>";
    
    $test_data = [
        'tenant_id' => $tenant['id'],
        'invoice' => 'INV-' . time(),
        'ref_number' => 'REF-' . time(),
        'amount' => 750.00,
        'payment_date' => date('Y-m-d')
    ];
    
    foreach ($test_data as $key => $value) {
        echo "<p><strong>$key:</strong> $value</p>";
        $_POST[$key] = $value;
    }
    echo "</div>";
    
    // Test the save_payment method
    $admin = new Action();
    $result = $admin->save_payment();
    
    echo "<div style='background: " . ($result == 1 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($result == 1 ? '#c3e6cb' : '#f5c6cb') . ";'>";
    echo "<h3>💾 Save Result:</h3>";
    echo "<p><strong>Result Code:</strong> $result</p>";
    echo "<p><strong>Status:</strong> " . ($result == 1 ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
    echo "</div>";
    
    if ($result == 1) {
        // Verify the payment was saved with reference number
        $latest_payment = $conn->query("SELECT * FROM payments ORDER BY id DESC LIMIT 1")->fetch_assoc();
        
        echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb;'>";
        echo "<h3>🔍 Verification - Latest Payment in Database:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f2f2f2;'><th>Field</th><th>Value</th><th>Status</th></tr>";
        
        foreach ($latest_payment as $key => $value) {
            $status = '✅ OK';
            $color = 'green';
            
            if ($key == 'ref_number') {
                if (empty($value)) {
                    $status = '❌ EMPTY';
                    $color = 'red';
                } else {
                    $status = '✅ HAS VALUE';
                    $color = 'green';
                }
            }
            
            echo "<tr>";
            echo "<td><strong>$key</strong></td>";
            echo "<td>" . ($value ?? 'NULL') . "</td>";
            echo "<td style='color: $color; font-weight: bold;'>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No active tenants found for testing</p>";
}

// Test the form display
echo "<h2>📝 Step 3: Test Form Display</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
echo "<h3>🔗 Test Links:</h3>";
echo "<p><a href='index.php?page=invoices' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📋 View Invoices/Payments List</a></p>";
echo "<p><a href='index.php?page=payment_report' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 View Payment Report</a></p>";
echo "<p><strong>Expected:</strong> Both pages should now show a 'Reference #' column</p>";
echo "</div>";

// Show current payments with reference numbers
echo "<h2>📊 Step 4: Current Payments with Reference Numbers</h2>";
$all_payments = $conn->query("SELECT p.*, CONCAT(t.firstname, ' ', t.lastname) as tenant_name FROM payments p LEFT JOIN tenants t ON t.id = p.tenant_id ORDER BY p.id DESC LIMIT 10");

if ($all_payments && $all_payments->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Date</th><th>Tenant</th><th>Invoice</th><th>Reference #</th><th>Amount</th></tr>";
    
    while ($payment = $all_payments->fetch_assoc()) {
        $ref_display = !empty($payment['ref_number']) ? $payment['ref_number'] : '<span style="color: #999;">-</span>';
        $ref_color = !empty($payment['ref_number']) ? 'green' : '#999';
        
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>" . date('M d, Y', strtotime($payment['date_created'])) . "</td>";
        echo "<td>{$payment['tenant_name']}</td>";
        echo "<td>{$payment['invoice']}</td>";
        echo "<td style='color: $ref_color; font-weight: bold;'>$ref_display</td>";
        echo "<td>$" . number_format($payment['amount'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payments found</p>";
}

// Summary
echo "<h2>✅ Feature Implementation Summary</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
echo "<h3>🎯 What's Been Added:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Database:</strong> Added 'ref_number' column to payments table (optional field)</li>";
echo "<li>✅ <strong>Form:</strong> Added reference number input field in manage_payment.php</li>";
echo "<li>✅ <strong>Backend:</strong> Updated save_payment() method to handle ref_number</li>";
echo "<li>✅ <strong>Display:</strong> Updated view_payment.php to show reference numbers</li>";
echo "<li>✅ <strong>Lists:</strong> Updated invoices.php to show reference number column</li>";
echo "<li>✅ <strong>Reports:</strong> Updated payment_report.php to include reference numbers</li>";
echo "</ul>";

echo "<h3>🎯 How to Use:</h3>";
echo "<ol>";
echo "<li><strong>Add Payment:</strong> Go to Payments → New Entry</li>";
echo "<li><strong>Fill Form:</strong> Enter tenant, invoice, amount, date</li>";
echo "<li><strong>Reference Number:</strong> Optionally enter check number, transaction ID, etc.</li>";
echo "<li><strong>Submit:</strong> Payment will be saved with reference number</li>";
echo "<li><strong>View:</strong> Reference numbers appear in all payment lists and reports</li>";
echo "</ol>";

echo "<h3>🛡️ Field Details:</h3>";
echo "<ul>";
echo "<li><strong>Field Name:</strong> Reference Number</li>";
echo "<li><strong>Type:</strong> Text input (up to 100 characters)</li>";
echo "<li><strong>Required:</strong> No (optional field)</li>";
echo "<li><strong>Purpose:</strong> Store check numbers, transaction IDs, or other payment references</li>";
echo "<li><strong>Display:</strong> Shows '-' when empty, actual value when filled</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🧪 Test Instructions:</h2>";
echo "<ol>";
echo "<li><strong>Click 'View Invoices/Payments List'</strong> → Should see 'Reference #' column</li>";
echo "<li><strong>Add a new payment</strong> → Form should have 'Reference Number' field</li>";
echo "<li><strong>Enter a reference number</strong> → Should save and display correctly</li>";
echo "<li><strong>View payment reports</strong> → Should include reference numbers</li>";
echo "</ol>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
