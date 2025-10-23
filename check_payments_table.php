<?php
include 'db_connect.php';

echo "<h1>🔍 Check Payments Table Structure</h1>";

// Check current table structure
$result = $conn->query("DESCRIBE payments");

echo "<h2>📋 Current Payments Table Structure:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check if ref_number column exists
$columns = $conn->query("SHOW COLUMNS FROM payments LIKE 'ref_number'");
$ref_number_exists = $columns->num_rows > 0;

echo "<h2>🔍 Reference Number Column Status:</h2>";
if ($ref_number_exists) {
    echo "<p style='color: green; font-weight: bold;'>✅ ref_number column already exists</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ ref_number column does not exist - needs to be added</p>";
    
    // Add the column
    echo "<h3>➕ Adding ref_number Column:</h3>";
    $add_column = "ALTER TABLE payments ADD COLUMN ref_number VARCHAR(100) NULL AFTER invoice";
    
    if ($conn->query($add_column)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully added ref_number column</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to add ref_number column: " . $conn->error . "</p>";
    }
}

// Show sample payments data
echo "<h2>📊 Sample Payments Data:</h2>";
$payments = $conn->query("SELECT * FROM payments ORDER BY id DESC LIMIT 5");

if ($payments && $payments->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'>";
    
    // Get column names
    $fields = $payments->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    
    // Reset result pointer and show data
    $payments->data_seek(0);
    while ($row = $payments->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . ($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payments found in database</p>";
}

echo "<h2>✅ Next Steps:</h2>";
echo "<ol>";
echo "<li>✅ Database column added (if needed)</li>";
echo "<li>🔄 Update manage_payment.php form to include ref_number field</li>";
echo "<li>🔄 Update admin_class.php save_payment method to handle ref_number</li>";
echo "<li>🔄 Update view_payment.php to display ref_number</li>";
echo "<li>🔄 Update payments list to show ref_number column</li>";
echo "</ol>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
