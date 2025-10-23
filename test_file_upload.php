<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>File Upload Test Results</h2>";
    
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES Data:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['contract_file'])) {
        $file = $_FILES['contract_file'];
        
        echo "<h3>File Analysis:</h3>";
        echo "<p>Name: " . $file['name'] . "</p>";
        echo "<p>Type: " . $file['type'] . "</p>";
        echo "<p>Size: " . $file['size'] . " bytes (" . round($file['size']/1024/1024, 2) . " MB)</p>";
        echo "<p>Error: " . $file['error'] . "</p>";
        echo "<p>Temp Name: " . $file['tmp_name'] . "</p>";
        
        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                echo "<p style='color: green;'>✓ No upload errors</p>";
                break;
            case UPLOAD_ERR_INI_SIZE:
                echo "<p style='color: red;'>✗ File exceeds upload_max_filesize</p>";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "<p style='color: red;'>✗ File exceeds MAX_FILE_SIZE</p>";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "<p style='color: red;'>✗ File was only partially uploaded</p>";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "<p style='color: red;'>✗ No file was uploaded</p>";
                break;
            default:
                echo "<p style='color: red;'>✗ Unknown upload error: " . $file['error'] . "</p>";
                break;
        }
        
        // Test file move
        if ($file['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/contracts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'test_' . time() . '_' . $file['name'];
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                echo "<p style='color: green;'>✓ File uploaded successfully to: $upload_path</p>";
                // Clean up test file
                unlink($upload_path);
                echo "<p style='color: green;'>✓ Test file cleaned up</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to move uploaded file</p>";
            }
        }
    }
    
    echo "<hr>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"] { padding: 5px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>File Upload Test</h1>
    <p>This form tests the basic file upload functionality.</p>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="contract_file">Select PDF File:</label>
            <input type="file" name="contract_file" id="contract_file" accept=".pdf" required>
        </div>
        
        <div class="form-group">
            <label for="test_name">Test Name:</label>
            <input type="text" name="test_name" id="test_name" value="Test Tenant" required>
        </div>
        
        <button type="submit">Test Upload</button>
    </form>
    
    <h3>PHP Upload Settings:</h3>
    <ul>
        <li>file_uploads: <?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?></li>
        <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
        <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
        <li>max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
        <li>max_execution_time: <?php echo ini_get('max_execution_time'); ?></li>
        <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
    </ul>
</body>
</html>
