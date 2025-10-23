<?php
// Simple script to add the missing contract_file column
include 'db_connect.php';

echo "Fixing database structure...<br>";

// Add the contract_file column
$sql = "ALTER TABLE `tenants` ADD COLUMN `contract_file` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded contract PDF'";

if ($conn->query($sql) === TRUE) {
    echo "<span style='color: green;'>✓ SUCCESS: contract_file column added successfully!</span><br>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "<span style='color: orange;'>ℹ INFO: contract_file column already exists.</span><br>";
    } else {
        echo "<span style='color: red;'>✗ ERROR: " . $conn->error . "</span><br>";
    }
}

// Verify the column exists
$result = $conn->query("SHOW COLUMNS FROM tenants LIKE 'contract_file'");
if ($result->num_rows > 0) {
    echo "<span style='color: green;'>✓ VERIFIED: contract_file column exists in database.</span><br>";
    echo "<br><strong>You can now test the tenant creation with file upload!</strong><br>";
} else {
    echo "<span style='color: red;'>✗ PROBLEM: contract_file column still missing.</span><br>";
}

$conn->close();
?>

<br><br>
<a href="tenants.php">← Go back to Tenants page</a>
