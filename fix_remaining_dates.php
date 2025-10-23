<?php
include 'db_connect.php';

echo "Fixing remaining invalid dates...\n";

$result = $conn->query("UPDATE tenants SET date_in = '2024-06-01' WHERE date_in = '0000-00-00'");

if ($result) {
    echo "✅ Fixed all remaining invalid dates\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Verify
$check = $conn->query("SELECT COUNT(*) as count FROM tenants WHERE date_in = '0000-00-00'");
$row = $check->fetch_assoc();
echo "Remaining invalid dates: " . $row['count'] . "\n";
?>
