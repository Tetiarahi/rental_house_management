<!DOCTYPE html>
<html>
<head>
    <title>AJAX Endpoints Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border: 1px solid #dee2e6; border-radius: 5px; }
        .result { background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        button { background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 3px; margin: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .loading { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
    </style>
</head>
<body>
    <h1>🔍 AJAX Endpoints Test</h1>
    
    <div class="test-section">
        <h3>1. 🎯 Individual Email Tests (ajax.php)</h3>
        <p>These test the same endpoints used by the individual "Send" buttons in Email Notifications.</p>
        
        <div>
            <label>Tenant ID: </label>
            <input type="number" id="tenant-id" placeholder="Enter tenant ID" value="1">
            <button onclick="testIndividualRentDue()">Test Rent Due Notice</button>
            <button onclick="testIndividualReminder()">Test Payment Reminder</button>
        </div>
        
        <div id="individual-results" class="result" style="display: none;"></div>
    </div>
    
    <div class="test-section">
        <h3>2. 📊 Bulk Email Tests (email_notifications.php)</h3>
        <p>These test the same endpoints used by the "Send All" buttons.</p>
        
        <div>
            <button onclick="testBulkRentDue()">Test Bulk Rent Due Notices</button>
            <button onclick="testBulkReminders()">Test Bulk Payment Reminders</button>
        </div>
        
        <div id="bulk-results" class="result" style="display: none;"></div>
    </div>
    
    <div class="test-section">
        <h3>3. 🧪 Test Email Configuration</h3>
        <div>
            <input type="email" id="test-email" placeholder="your-email@example.com">
            <button onclick="testEmailConfig()">Send Test Email</button>
        </div>
        
        <div id="config-results" class="result" style="display: none;"></div>
    </div>
    
    <div class="test-section">
        <h3>4. 👥 Available Tenants</h3>
        <div id="tenants-list">Loading...</div>
    </div>

    <script>
        // Load tenants on page load
        $(document).ready(function() {
            loadTenants();
        });
        
        function showResult(containerId, message, isSuccess = true) {
            const container = document.getElementById(containerId);
            container.style.display = 'block';
            container.className = 'result ' + (isSuccess ? 'success' : 'error');
            container.innerHTML = message;
        }
        
        function showLoading(containerId, message = 'Loading...') {
            const container = document.getElementById(containerId);
            container.style.display = 'block';
            container.className = 'result loading';
            container.innerHTML = '⏳ ' + message;
        }
        
        function testIndividualRentDue() {
            const tenantId = document.getElementById('tenant-id').value;
            if (!tenantId) {
                showResult('individual-results', '❌ Please enter a tenant ID', false);
                return;
            }
            
            showLoading('individual-results', 'Sending rent due notice...');
            
            $.ajax({
                url: 'ajax.php?action=send_rent_due_notice',
                method: 'POST',
                data: { tenant_id: tenantId },
                success: function(resp) {
                    console.log('Response:', resp);
                    if (resp == 1) {
                        showResult('individual-results', '✅ Rent due notice sent successfully!');
                    } else {
                        showResult('individual-results', '❌ Failed to send notice. Response: ' + resp, false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    showResult('individual-results', '❌ Connection error: ' + error + '<br>Response: ' + xhr.responseText, false);
                }
            });
        }
        
        function testIndividualReminder() {
            const tenantId = document.getElementById('tenant-id').value;
            if (!tenantId) {
                showResult('individual-results', '❌ Please enter a tenant ID', false);
                return;
            }
            
            showLoading('individual-results', 'Sending payment reminder...');
            
            $.ajax({
                url: 'ajax.php?action=send_payment_reminder',
                method: 'POST',
                data: { tenant_id: tenantId },
                success: function(resp) {
                    console.log('Response:', resp);
                    if (resp == 1) {
                        showResult('individual-results', '✅ Payment reminder sent successfully!');
                    } else {
                        showResult('individual-results', '❌ Failed to send reminder. Response: ' + resp, false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    showResult('individual-results', '❌ Connection error: ' + error + '<br>Response: ' + xhr.responseText, false);
                }
            });
        }
        
        function testBulkRentDue() {
            showLoading('bulk-results', 'Sending bulk rent due notices...');
            
            $.ajax({
                url: 'email_notifications.php',
                method: 'POST',
                data: { action: 'send_rent_due_notices' },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        showResult('bulk-results', `✅ Bulk send completed!<br>Sent: ${response.sent}<br>Failed: ${response.failed}<br>Total: ${response.total}`);
                    } else {
                        showResult('bulk-results', '❌ Failed to send bulk notices.', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    showResult('bulk-results', '❌ Connection error: ' + error + '<br>Response: ' + xhr.responseText, false);
                }
            });
        }
        
        function testBulkReminders() {
            showLoading('bulk-results', 'Sending bulk payment reminders...');
            
            $.ajax({
                url: 'email_notifications.php',
                method: 'POST',
                data: { action: 'send_payment_reminders' },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        showResult('bulk-results', `✅ Bulk send completed!<br>Sent: ${response.sent}<br>Failed: ${response.failed}<br>Total: ${response.total}`);
                    } else {
                        showResult('bulk-results', '❌ Failed to send bulk reminders.', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    showResult('bulk-results', '❌ Connection error: ' + error + '<br>Response: ' + xhr.responseText, false);
                }
            });
        }
        
        function testEmailConfig() {
            const testEmail = document.getElementById('test-email').value;
            if (!testEmail) {
                showResult('config-results', '❌ Please enter an email address', false);
                return;
            }
            
            showLoading('config-results', 'Sending test email...');
            
            $.ajax({
                url: 'ajax.php?action=send_test_email',
                method: 'POST',
                data: { test_email: testEmail },
                success: function(resp) {
                    console.log('Response:', resp);
                    if (resp == 1) {
                        showResult('config-results', '✅ Test email sent successfully! Check your inbox.');
                    } else {
                        showResult('config-results', '❌ Failed to send test email. Response: ' + resp, false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    showResult('config-results', '❌ Connection error: ' + error + '<br>Response: ' + xhr.responseText, false);
                }
            });
        }
        
        function loadTenants() {
            $.ajax({
                url: 'ajax.php?action=get_tenants',
                method: 'GET',
                success: function(resp) {
                    // If no specific endpoint exists, show manual list
                    document.getElementById('tenants-list').innerHTML = `
                        <p>📝 <strong>Manual Tenant List:</strong></p>
                        <p>Go to your admin panel to see the list of tenants, or use tenant ID 1, 2, 3, etc. for testing.</p>
                        <p><a href="index.php?page=tenants" target="_blank" style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">View Tenants</a></p>
                    `;
                },
                error: function() {
                    document.getElementById('tenants-list').innerHTML = `
                        <p>📝 <strong>Manual Testing:</strong></p>
                        <p>Use tenant IDs like 1, 2, 3, etc. for testing individual emails.</p>
                        <p><a href="index.php?page=tenants" target="_blank" style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">View Tenants in Admin</a></p>
                    `;
                }
            });
        }
    </script>
</body>
</html>
