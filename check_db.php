<?php
include 'db_connect.php';

echo "<h2>Database Structure Check</h2>";

// Check if contract_file column exists
$result = $conn->query("DESCRIBE tenants");
echo "<h3>Tenants table structure:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

$contract_field_exists = false;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
    
    if ($row['Field'] == 'contract_file') {
        $contract_field_exists = true;
    }
}
echo "</table>";

if ($contract_field_exists) {
    echo "<p style='color: green;'>✓ contract_file column exists</p>";
} else {
    echo "<p style='color: red;'>✗ contract_file column does not exist</p>";
    echo "<p>You need to run the migration: database/add_contract_field.sql</p>";
}

// Check upload directory
$upload_dir = 'assets/uploads/contracts/';
if (is_dir($upload_dir)) {
    echo "<p style='color: green;'>✓ Upload directory exists: $upload_dir</p>";
} else {
    echo "<p style='color: red;'>✗ Upload directory does not exist: $upload_dir</p>";
}

if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✓ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Upload directory is not writable</p>";
}

// Test file upload settings
echo "<h3>PHP Upload Settings:</h3>";
echo "<p>file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "</p>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";

$conn->close();
?>
