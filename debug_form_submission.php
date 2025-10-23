<?php
// This file will help us debug the actual form submission process
include 'db_connect.php';

echo "<h1>🔍 Debug Form Submission Process</h1>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📝 Test Form Submission</h2>";

// Create a test form that mimics the actual tenant form
?>
<div style="background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; margin: 20px 0;">
    <h3>🧪 Test Tenant Form</h3>
    <p>This form will help us see exactly what data is being sent when you submit.</p>
    
    <form id="manage-tenant" method="POST" enctype="multipart/form-data">
        <div style="margin: 10px 0;">
            <label><strong>First Name:</strong></label><br>
            <input type="text" name="firstname" value="Test" required style="width: 200px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>Last Name:</strong></label><br>
            <input type="text" name="lastname" value="User" required style="width: 200px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>Middle Name:</strong></label><br>
            <input type="text" name="middlename" value="" style="width: 200px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>Email:</strong></label><br>
            <input type="email" name="email" value="test<?php echo time(); ?>@example.com" required style="width: 200px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>Contact:</strong></label><br>
            <input type="text" name="contact" value="1234567890" required style="width: 200px; padding: 5px;">
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>House:</strong></label><br>
            <select name="house_id" required style="width: 200px; padding: 5px;">
                <option value="">Select House</option>
                <?php
                $houses = $conn->query("SELECT * FROM houses ORDER BY id");
                while ($house = $houses->fetch_assoc()) {
                    echo "<option value='{$house['id']}'>{$house['house_no']} - $" . number_format($house['price'], 2) . "</option>";
                }
                ?>
            </select>
        </div>
        
        <div style="margin: 10px 0;">
            <label><strong>Registration Date:</strong></label><br>
            <input type="date" name="date_in" value="2025-08-15" 
                   min="<?php echo date('Y-m-d', strtotime('-10 years')); ?>" 
                   max="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" 
                   required style="width: 200px; padding: 5px;">
            <br><small>Current value will be: <span id="date-display">2025-08-15</span></small>
        </div>
        
        <div style="margin: 20px 0;">
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                🧪 Test Submit
            </button>
        </div>
    </form>
</div>

<script>
// Monitor the date input for changes
document.querySelector('input[name="date_in"]').addEventListener('change', function() {
    document.getElementById('date-display').textContent = this.value;
    console.log('Date input changed to:', this.value);
});

// Intercept form submission to see what data is being sent
document.getElementById('manage-tenant').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent actual submission
    
    console.log('🚀 Form submission intercepted');
    
    // Collect all form data
    const formData = new FormData(this);
    
    console.log('📝 Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Show the data on the page
    let output = '<div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;">';
    output += '<h3>📊 Form Data Captured:</h3>';
    output += '<table border="1" style="border-collapse: collapse; width: 100%;">';
    output += '<tr style="background: #f2f2f2;"><th>Field</th><th>Value</th></tr>';
    
    for (let [key, value] of formData.entries()) {
        const color = key === 'date_in' ? (value ? 'green' : 'red') : 'black';
        output += `<tr><td><strong>${key}</strong></td><td style="color: ${color}; font-weight: bold;">${value}</td></tr>`;
    }
    
    output += '</table>';
    output += '<p><strong>Date field status:</strong> ' + (formData.get('date_in') ? '✅ Has value' : '❌ Empty') + '</p>';
    output += '</div>';
    
    // Add the output to the page
    document.getElementById('form-output').innerHTML = output;
    
    // Now actually submit to the server
    console.log('📤 Sending to server...');
    
    fetch('ajax.php?action=save_tenant', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        console.log('📥 Server response:', result);
        
        let serverOutput = '<div style="background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;">';
        serverOutput += '<h3>📥 Server Response:</h3>';
        serverOutput += '<pre style="background: white; padding: 10px; border: 1px solid #ddd;">' + result + '</pre>';
        
        // Try to parse the response
        try {
            const parsed = parseInt(result);
            const codes = {
                0: 'Missing required fields or general error',
                1: 'Success ✅',
                2: 'House already assigned to an active tenant',
                3: 'Invalid house ID',
                4: 'Invalid email format',
                8: 'Invalid date format',
                9: 'Date more than 1 month in future',
                10: 'Date more than 10 years ago'
            };
            serverOutput += '<p><strong>Response Code:</strong> ' + parsed + '</p>';
            serverOutput += '<p><strong>Meaning:</strong> ' + (codes[parsed] || 'Unknown') + '</p>';
        } catch (e) {
            serverOutput += '<p><strong>Note:</strong> Response is not a numeric code</p>';
        }
        
        serverOutput += '</div>';
        
        document.getElementById('server-output').innerHTML = serverOutput;
        
        // Check what was actually saved in the database
        checkDatabase();
    })
    .catch(error => {
        console.error('❌ Error:', error);
        document.getElementById('server-output').innerHTML = 
            '<div style="background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;"><h3>❌ Error:</h3><p>' + error + '</p></div>';
    });
});

function checkDatabase() {
    console.log('🔍 Checking database...');
    
    fetch('debug_form_submission.php?check_db=1')
    .then(response => response.text())
    .then(result => {
        document.getElementById('db-check').innerHTML = result;
    });
}
</script>

<div id="form-output"></div>
<div id="server-output"></div>
<div id="db-check"></div>

<?php
// Handle database check request
if (isset($_GET['check_db'])) {
    echo '<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;">';
    echo '<h3>🗄️ Database Check:</h3>';
    
    $latest = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1");
    if ($latest && $latest->num_rows > 0) {
        $tenant = $latest->fetch_assoc();
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr style="background: #f2f2f2;"><th>Field</th><th>Value</th></tr>';
        foreach ($tenant as $key => $value) {
            $color = $key === 'date_in' ? ($value === '0000-00-00' ? 'red' : 'green') : 'black';
            echo "<tr><td><strong>$key</strong></td><td style='color: $color; font-weight: bold;'>$value</td></tr>";
        }
        echo '</table>';
        
        echo '<p><strong>Date Status:</strong> ' . ($tenant['date_in'] === '0000-00-00' ? '❌ Invalid (0000-00-00)' : '✅ Valid (' . $tenant['date_in'] . ')') . '</p>';
    } else {
        echo '<p>❌ No tenants found in database</p>';
    }
    echo '</div>';
    exit;
}

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 Instructions:</h2>";
echo "<ol>";
echo "<li><strong>Fill out the form above</strong> (most fields are pre-filled)</li>";
echo "<li><strong>Select a house</strong> from the dropdown</li>";
echo "<li><strong>Verify the registration date</strong> shows 2025-08-15</li>";
echo "<li><strong>Click 'Test Submit'</strong> to see what data is sent</li>";
echo "<li><strong>Check the outputs below</strong> to see where the problem occurs</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
echo "<h2>🔍 What This Will Show:</h2>";
echo "<ul>";
echo "<li><strong>Form Data Captured:</strong> What the browser sends</li>";
echo "<li><strong>Server Response:</strong> What the PHP script returns</li>";
echo "<li><strong>Database Check:</strong> What actually gets saved</li>";
echo "</ul>";
echo "<p>This will help us identify if the problem is in the form, the AJAX submission, the PHP processing, or the database save.</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
