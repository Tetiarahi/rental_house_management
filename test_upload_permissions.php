<?php
// Simple test script to verify upload directory permissions
// Run this script to ensure the contracts directory is writable

$upload_dir = 'assets/uploads/contracts/';

echo "<h2>Upload Directory Test</h2>";

// Check if directory exists
if (is_dir($upload_dir)) {
    echo "<p style='color: green;'>✓ Directory exists: $upload_dir</p>";
} else {
    echo "<p style='color: red;'>✗ Directory does not exist: $upload_dir</p>";
    echo "<p>Creating directory...</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✓ Directory created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create directory</p>";
    }
}

// Check if directory is writable
if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✓ Directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Directory is not writable</p>";
    echo "<p>Please check directory permissions</p>";
}

// Test file creation
$test_file = $upload_dir . 'test_' . time() . '.txt';
if (file_put_contents($test_file, 'Test content')) {
    echo "<p style='color: green;'>✓ Test file created successfully</p>";
    // Clean up test file
    unlink($test_file);
    echo "<p style='color: green;'>✓ Test file deleted successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to create test file</p>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tests pass, the contract upload feature should work correctly.</p>";
echo "<p>If any tests fail, please check directory permissions or contact your system administrator.</p>";

// Delete this test file after use
echo "<p><strong>Note:</strong> Remember to delete this test file (test_upload_permissions.php) after testing.</p>";
?>
