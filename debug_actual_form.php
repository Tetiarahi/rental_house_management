<?php 
include 'db_connect.php'; 

// Check if we're editing a specific tenant
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Actual Form - Date Issue</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <style>
        .debug-info { background: #f8f9fa; padding: 15px; margin: 10px 0; border: 1px solid #dee2e6; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>

<div class="container-fluid">
    <h1>🔍 Debug Actual Form - Date Issue</h1>
    
    <div class="debug-info">
        <h3>Current State:</h3>
        <ul>
            <li><strong>Editing Tenant:</strong> <?php echo $editing_tenant ? 'YES' : 'NO'; ?></li>
            <?php if ($editing_tenant): ?>
                <li><strong>Tenant ID:</strong> <?php echo $tenant_id; ?></li>
                <li><strong>Tenant Name:</strong> <?php echo ($firstname ?? '') . ' ' . ($lastname ?? ''); ?></li>
                <li><strong>Database date_in:</strong> <?php echo $date_in ?? 'NULL'; ?></li>
            <?php endif; ?>
            <li><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
            <li><strong>Cache Buster:</strong> <?php echo time(); ?></li>
        </ul>
    </div>

    <div class="debug-info">
        <h3>PHP Logic Test:</h3>
        <?php
        // Test the exact logic from manage_tenant.php
        if ($editing_tenant) {
            echo "<p><strong>Testing with existing tenant data:</strong></p>";
            echo "<p>isset(\$date_in): " . (isset($date_in) ? 'TRUE' : 'FALSE') . "</p>";
            echo "<p>\$date_in value: '" . ($date_in ?? 'NULL') . "'</p>";
            echo "<p>\$date_in != '0000-00-00': " . (($date_in ?? '') != '0000-00-00' ? 'TRUE' : 'FALSE') . "</p>";
            echo "<p>\$date_in != '': " . (($date_in ?? '') != '' ? 'TRUE' : 'FALSE') . "</p>";
            
            $form_value = (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d');
            echo "<p class='success'><strong>Final form value: '$form_value'</strong></p>";
        } else {
            echo "<p><strong>Testing with new tenant (no data):</strong></p>";
            echo "<p>No \$date_in variable set</p>";
            $form_value = date('Y-m-d');
            echo "<p class='success'><strong>Final form value: '$form_value'</strong></p>";
        }
        ?>
    </div>

    <!-- This is the EXACT form structure from manage_tenant.php -->
    <form action="" id="manage-tenant">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        
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
                
                <!-- DEBUG: Show what PHP is generating -->
                <div class="debug-info">
                    <small>
                        <strong>PHP Debug:</strong><br>
                        Raw value: <?php echo isset($date_in) ? "'" . $date_in . "'" : 'NOT SET'; ?><br>
                        Logic result: <?php 
                        $debug_value = (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d');
                        echo "'" . $debug_value . "'"; 
                        ?><br>
                        HTML will be: &lt;input type="date" value="<?php echo $debug_value; ?>"&gt;
                    </small>
                </div>
                
                <!-- The actual input field -->
                <input type="date" class="form-control" name="date_in" id="date_in_field" 
                       value="<?php echo (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d'); ?>" 
                       max="<?php echo date('Y-m-d') ?>" required>
                
                <small class="text-muted">Date when tenant moved in (cannot be in the future)</small>
                
                <!-- Real-time debug info -->
                <div class="debug-info mt-2">
                    <small id="debug-output">
                        <strong>JavaScript Debug:</strong><br>
                        <span id="js-debug">Loading...</span>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="form-group row">
            <div class="col-md-12">
                <button type="button" onclick="debugDateField()" class="btn btn-info">🔍 Debug Date Field</button>
                <button type="button" onclick="fixDateField()" class="btn btn-warning">🔧 Fix Date Field</button>
            </div>
        </div>
    </form>

    <div class="debug-info">
        <h3>Manual Tests:</h3>
        <p>Test different date values manually:</p>
        <table class="table table-bordered">
            <tr>
                <th>Test Case</th>
                <th>HTML Input</th>
                <th>What You See</th>
            </tr>
            <tr>
                <td>Current Date</td>
                <td><input type="date" value="<?php echo date('Y-m-d'); ?>" class="form-control"></td>
                <td>Should show today's date</td>
            </tr>
            <tr>
                <td>Valid Past Date</td>
                <td><input type="date" value="2024-06-01" class="form-control"></td>
                <td>Should show June 1, 2024</td>
            </tr>
            <tr>
                <td>Empty Value</td>
                <td><input type="date" value="" class="form-control"></td>
                <td>Should be empty</td>
            </tr>
            <tr>
                <td>Invalid Value</td>
                <td><input type="date" value="invalid" class="form-control"></td>
                <td>Should be empty or show error</td>
            </tr>
        </table>
    </div>
</div>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script>
function debugDateField() {
    var dateField = document.getElementById('date_in_field');
    var debugOutput = document.getElementById('js-debug');
    
    var info = [];
    info.push('Field exists: ' + (dateField ? 'YES' : 'NO'));
    
    if (dateField) {
        info.push('Current value: "' + dateField.value + '"');
        info.push('Value length: ' + dateField.value.length);
        info.push('Value type: ' + typeof dateField.value);
        info.push('Is empty: ' + (dateField.value === ''));
        info.push('Is "Invalid Date": ' + (dateField.value === 'Invalid Date'));
        info.push('HTML value attribute: "' + dateField.getAttribute('value') + '"');
        info.push('Max attribute: "' + dateField.getAttribute('max') + '"');
        info.push('Required: ' + dateField.required);
        
        // Check if browser supports date input
        var testInput = document.createElement('input');
        testInput.type = 'date';
        info.push('Browser supports date input: ' + (testInput.type === 'date'));
    }
    
    debugOutput.innerHTML = info.join('<br>');
    
    console.log('🔍 Date Field Debug:', info);
}

function fixDateField() {
    var dateField = document.getElementById('date_in_field');
    if (dateField) {
        var today = new Date().toISOString().split('T')[0];
        var oldValue = dateField.value;
        dateField.value = today;
        
        console.log('🔧 Fixed date field from "' + oldValue + '" to "' + today + '"');
        alert('Fixed date field from "' + oldValue + '" to "' + today + '"');
        
        // Re-run debug
        debugDateField();
    }
}

// Auto-debug on page load
$(document).ready(function() {
    console.log('🚀 Page loaded, running auto-debug...');
    
    setTimeout(function() {
        debugDateField();
    }, 500);
    
    // Monitor for changes
    $('#date_in_field').on('change input', function() {
        console.log('📅 Date field changed to:', this.value);
        setTimeout(debugDateField, 100);
    });
});

// Additional debugging
console.log('🧪 Debug script loaded');
console.log('📅 Current date:', new Date().toISOString().split('T')[0]);
console.log('🌐 User agent:', navigator.userAgent);
</script>

</body>
</html>
