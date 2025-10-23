<?php
include 'db_connect.php';

echo "<h2>Running Database Migration</h2>";

// Check if contract_file column already exists
$result = $conn->query("SHOW COLUMNS FROM tenants LIKE 'contract_file'");
if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>contract_file column already exists. No migration needed.</p>";
} else {
    echo "<p>Adding contract_file column to tenants table...</p>";
    
    $sql = "ALTER TABLE `tenants` ADD COLUMN `contract_file` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded contract PDF'";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Migration completed successfully!</p>";
        echo "<p>contract_file column has been added to the tenants table.</p>";
    } else {
        echo "<p style='color: red;'>✗ Migration failed: " . $conn->error . "</p>";
    }
}

// Verify the column was added
$result = $conn->query("DESCRIBE tenants");
echo "<h3>Current tenants table structure:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    $style = ($row['Field'] == 'contract_file') ? "style='background-color: #d4edda;'" : "";
    echo "<tr $style>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Test the tenant creation/editing with file upload</li>";
echo "<li>Check that the contracts directory exists and is writable</li>";
echo "<li>Delete this migration script after successful testing</li>";
echo "</ul>";
?>
