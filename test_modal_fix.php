<!DOCTYPE html>
<html>
<head>
    <title>Test Modal Date Fix</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <style>
        body { padding: 20px; }
        .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border: 1px solid #dee2e6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Test Modal Date Fix</h1>
    
    <div class="test-section">
        <h3>Instructions:</h3>
        <ol>
            <li><strong>Click "Test New Tenant Modal"</strong> - should open modal with current date</li>
            <li><strong>Click "Test Edit Tenant Modal"</strong> - should open modal with tenant's registration date</li>
            <li><strong>Check the date input field</strong> - should show proper date (no "Invalid Date")</li>
            <li><strong>Press F12 → Console</strong> - should see "Fixed modal date input" messages</li>
        </ol>
    </div>

    <div class="test-section">
        <button class="btn btn-primary" onclick="testNewTenantModal()">🆕 Test New Tenant Modal</button>
        <button class="btn btn-info" onclick="testEditTenantModal()">✏️ Test Edit Tenant Modal</button>
        <button class="btn btn-warning" onclick="testDirectAccess()">🔗 Test Direct Access</button>
    </div>

    <div class="test-section">
        <h3>Expected Results:</h3>
        <ul>
            <li>✅ <strong>New Tenant Modal:</strong> Date field shows current date (<?php echo date('Y-m-d'); ?>)</li>
            <li>✅ <strong>Edit Tenant Modal:</strong> Date field shows tenant's registration date</li>
            <li>✅ <strong>No "Invalid Date":</strong> Should never appear in modal forms</li>
            <li>✅ <strong>Console Logs:</strong> Should see "Fixed modal date input" messages</li>
        </ul>
    </div>
</div>

<!-- Modal structure (copied from index.php) -->
<div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script>
// Copy the exact uni_modal function from index.php (with our fix)
window.uni_modal = function($title = '' , $url='',$size=""){
    console.log('🚀 Opening modal:', $title, 'URL:', $url);
    
    $.ajax({
        url:$url,
        error:err=>{
            console.log('❌ Modal load error:', err);
            alert("An error occured")
        },
        success:function(resp){
            if(resp){
                $('#uni_modal .modal-title').html($title)
                $('#uni_modal .modal-body').html(resp)
                if($size != ''){
                    $('#uni_modal .modal-dialog').addClass($size)
                }else{
                    $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md")
                }
                $('#uni_modal').modal({
                  show:true,
                  backdrop:'static',
                  keyboard:false,
                  focus:true
                })
                
                // Fix date inputs after modal content is loaded
                setTimeout(function() {
                    var dateInputs = $('#uni_modal input[type="date"]');
                    console.log('🔍 Found', dateInputs.length, 'date inputs in modal');
                    
                    dateInputs.each(function() {
                        var dateInput = $(this);
                        var currentValue = dateInput.val();
                        
                        console.log('🔧 Modal date input check:', dateInput.attr('name'), 'value:', currentValue);
                        
                        // If value is empty, invalid, or shows "Invalid Date"
                        if (!currentValue || currentValue === '' || currentValue === 'Invalid Date') {
                            var today = new Date().toISOString().split('T')[0];
                            dateInput.val(today);
                            console.log('✅ Fixed modal date input to:', today);
                        } else {
                            console.log('✅ Modal date input already has valid value:', currentValue);
                        }
                    });
                }, 100);
            }
        }
    })
}

function testNewTenantModal() {
    console.log('🧪 Testing New Tenant Modal...');
    uni_modal("New Tenant","manage_tenant.php","mid-large");
}

function testEditTenantModal() {
    console.log('🧪 Testing Edit Tenant Modal...');
    // Use tenant ID 16 (we know this exists)
    uni_modal("Manage Tenant Details","manage_tenant.php?id=16","mid-large");
}

function testDirectAccess() {
    console.log('🧪 Testing Direct Access...');
    window.open('manage_tenant.php', '_blank');
}

$(document).ready(function() {
    console.log('🚀 Test page loaded');
    console.log('📅 Current date:', new Date().toISOString().split('T')[0]);
});
</script>

</body>
</html>
