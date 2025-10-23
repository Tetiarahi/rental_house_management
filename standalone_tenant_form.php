<?php 
include 'db_connect.php'; 

// Force no caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$editing_tenant = false;
$tenant_data = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $tenant_id = $_GET['id'];
    $qry = $conn->query("SELECT * FROM tenants WHERE id = $tenant_id");
    if ($qry && $qry->num_rows > 0) {
        $tenant_data = $qry->fetch_array();
        foreach($tenant_data as $k => $val){
            if (!is_numeric($k)) {
                $$k = $val;
            }
        }
        $editing_tenant = true;
    }
}

// Cache buster
$cache_buster = time();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Standalone Tenant Form - No Cache (<?php echo $cache_buster; ?>)</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css?v=<?php echo $cache_buster; ?>">
    <style>
        body { padding: 20px; }
        .alert { margin: 10px 0; }
        .debug { background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #dee2e6; font-family: monospace; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Standalone Tenant Form (Cache Buster: <?php echo $cache_buster; ?>)</h1>
    
    <div class="alert alert-info">
        <strong>Status:</strong> 
        <?php if ($editing_tenant): ?>
            Editing tenant ID <?php echo $tenant_id; ?> (<?php echo ($firstname ?? '') . ' ' . ($lastname ?? ''); ?>)
        <?php else: ?>
            Adding new tenant
        <?php endif; ?>
    </div>

    <div class="debug">
        <strong>PHP Debug Info:</strong><br>
        <?php if ($editing_tenant): ?>
            Raw date_in from DB: "<?php echo $date_in ?? 'NULL'; ?>"<br>
            isset($date_in): <?php echo isset($date_in) ? 'TRUE' : 'FALSE'; ?><br>
            $date_in != '0000-00-00': <?php echo (($date_in ?? '') != '0000-00-00') ? 'TRUE' : 'FALSE'; ?><br>
            $date_in != '': <?php echo (($date_in ?? '') != '') ? 'TRUE' : 'FALSE'; ?><br>
        <?php else: ?>
            New tenant - no date_in variable<br>
        <?php endif; ?>
        
        <?php
        $final_value = (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d');
        ?>
        Final PHP value: "<?php echo $final_value; ?>"<br>
        Current server time: <?php echo date('Y-m-d H:i:s'); ?>
    </div>

    <!-- EXACT COPY of the form from manage_tenant.php -->
    <form action="" id="manage-tenant" method="POST">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="cache_buster" value="<?php echo $cache_buster; ?>">
        
        <div class="row form-group">
            <div class="col-md-4">
                <label for="" class="control-label">Last Name</label>
                <input type="text" class="form-control" name="lastname" value="<?php echo isset($lastname) ? $lastname :'' ?>" required>
            </div>
            <div class="col-md-4">
                <label for="" class="control-label">First Name</label>
                <input type="text" class="form-control" name="firstname" value="<?php echo isset($firstname) ? $firstname :'' ?>" required>
            </div>
            <div class="col-md-4">
                <label for="" class="control-label">Registration Date</label>
                
                <!-- THE PROBLEMATIC INPUT - EXACT COPY -->
                <input type="date" 
                       class="form-control" 
                       name="date_in" 
                       id="date_in_field"
                       value="<?php echo (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d'); ?>" 
                       max="<?php echo date('Y-m-d') ?>" 
                       required>
                
                <small class="text-muted">Date when tenant moved in (cannot be in the future)</small>
            </div>
        </div>
        
        <div class="form-group">
            <button type="button" class="btn btn-primary" onclick="testDateField()">🧪 Test Date Field</button>
            <button type="button" class="btn btn-warning" onclick="fixDateField()">🔧 Force Fix Date</button>
            <button type="submit" class="btn btn-success">💾 Save Tenant</button>
        </div>
    </form>

    <div class="debug" id="js-debug">
        <strong>JavaScript Debug:</strong><br>
        <span id="debug-output">Loading...</span>
    </div>

    <div class="alert alert-warning">
        <h4>🔍 What to check:</h4>
        <ol>
            <li><strong>Look at the date input above</strong> - does it show "Invalid Date" or a proper date?</li>
            <li><strong>Click "Test Date Field"</strong> - check the debug output</li>
            <li><strong>Press F12 → Console</strong> - look for any JavaScript errors</li>
            <li><strong>Right-click → View Source</strong> - search for 'value=' in the date input</li>
        </ol>
    </div>

    <div class="alert alert-info">
        <h4>🧪 Manual Test Inputs:</h4>
        <p>Compare with these manual inputs:</p>
        <table class="table table-bordered">
            <tr>
                <th>Test</th>
                <th>Input</th>
                <th>Expected</th>
            </tr>
            <tr>
                <td>Current Date</td>
                <td><input type="date" value="<?php echo date('Y-m-d'); ?>" class="form-control"></td>
                <td>Should show today</td>
            </tr>
            <tr>
                <td>Valid Date</td>
                <td><input type="date" value="2024-06-01" class="form-control"></td>
                <td>Should show June 1, 2024</td>
            </tr>
            <tr>
                <td>Empty</td>
                <td><input type="date" value="" class="form-control"></td>
                <td>Should be empty</td>
            </tr>
        </table>
    </div>
</div>

<script src="assets/js/jquery.js?v=<?php echo $cache_buster; ?>"></script>
<script>
function testDateField() {
    var field = document.getElementById('date_in_field');
    var output = document.getElementById('debug-output');
    
    var info = [];
    info.push('Field exists: ' + (field ? 'YES' : 'NO'));
    
    if (field) {
        info.push('Current value: "' + field.value + '"');
        info.push('Value length: ' + field.value.length);
        info.push('HTML value attribute: "' + field.getAttribute('value') + '"');
        info.push('Max attribute: "' + field.getAttribute('max') + '"');
        info.push('Field type: ' + field.type);
        info.push('Is required: ' + field.required);
        
        // Check if it shows as "Invalid Date"
        if (field.value === 'Invalid Date' || field.value === '') {
            info.push('⚠️ PROBLEM DETECTED: Field shows invalid/empty value');
        } else {
            info.push('✅ Field has valid value');
        }
    }
    
    output.innerHTML = info.join('<br>');
    console.log('🧪 Date Field Test Results:', info);
}

function fixDateField() {
    var field = document.getElementById('date_in_field');
    if (field) {
        var today = new Date().toISOString().split('T')[0];
        var oldValue = field.value;
        
        field.value = today;
        field.setAttribute('value', today);
        
        console.log('🔧 Fixed date field from "' + oldValue + '" to "' + today + '"');
        alert('Fixed date field from "' + oldValue + '" to "' + today + '"');
        
        testDateField();
    }
}

// Auto-run on page load
$(document).ready(function() {
    console.log('🚀 Standalone form loaded with cache buster: <?php echo $cache_buster; ?>');
    console.log('📅 Current date: ' + new Date().toISOString().split('T')[0]);
    
    setTimeout(function() {
        testDateField();
    }, 500);
    
    // Monitor changes
    $('#date_in_field').on('change input', function() {
        console.log('📅 Date field changed to: "' + this.value + '"');
    });
});

// Form submission test
$('#manage-tenant').on('submit', function(e) {
    e.preventDefault();
    
    var dateValue = $('#date_in_field').val();
    alert('Form submitted with date value: "' + dateValue + '"');
    
    console.log('📝 Form submission test - date value:', dateValue);
    return false;
});
</script>

</body>
</html>
