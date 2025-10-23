<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
require_once 'email_class.php';

echo "<h1>🔍 Email Endpoints Debug</h1>";

// Test individual email endpoint (ajax.php)
echo "<h2>1. 🎯 Individual Email Endpoint Test (ajax.php)</h2>";

if (isset($_POST['test_individual'])) {
    $tenant_id = $_POST['test_tenant_id'] ?? 0;
    $email_type = $_POST['email_type'] ?? 'rent_due';
    
    echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #ced4da; margin: 10px 0;'>";
    echo "<h4>🧪 Testing Individual Email Send</h4>";
    echo "<p><strong>Tenant ID:</strong> $tenant_id</p>";
    echo "<p><strong>Email Type:</strong> $email_type</p>";
    
    if ($tenant_id > 0) {
        try {
            // Simulate the ajax.php endpoint
            $emailManager = new EmailManager();
            
            if ($email_type == 'rent_due') {
                echo "<p>📤 Calling sendRentDueNotice($tenant_id)...</p>";
                $result = $emailManager->sendRentDueNotice($tenant_id);
            } else {
                echo "<p>📤 Calling sendPaymentReminder($tenant_id)...</p>";
                $result = $emailManager->sendPaymentReminder($tenant_id);
            }
            
            echo "<p><strong>Result:</strong> " . ($result ? "✅ SUCCESS (1)" : "❌ FAILED (0)") . "</p>";
            
            if (!$result) {
                // Check the most recent log entry for this tenant
                $log_query = "SELECT * FROM email_logs WHERE tenant_id = ? ORDER BY created_date DESC LIMIT 1";
                $stmt = $conn->prepare($log_query);
                $stmt->bind_param("i", $tenant_id);
                $stmt->execute();
                $log_result = $stmt->get_result();
                
                if ($log_result && $log_result->num_rows > 0) {
                    $log = $log_result->fetch_assoc();
                    echo "<p><strong>Error Details:</strong></p>";
                    echo "<ul>";
                    echo "<li><strong>Status:</strong> {$log['status']}</li>";
                    echo "<li><strong>Error Message:</strong> " . htmlspecialchars($log['error_message'] ?? 'None') . "</li>";
                    echo "<li><strong>Email To:</strong> {$log['email_to']}</li>";
                    echo "<li><strong>Created:</strong> {$log['created_date']}</li>";
                    echo "</ul>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>❌ EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid tenant ID</p>";
    }
    
    echo "</div>";
}

// Test bulk email endpoint (email_notifications.php)
echo "<h2>2. 📊 Bulk Email Endpoint Test (email_notifications.php)</h2>";

if (isset($_POST['test_bulk'])) {
    $email_type = $_POST['bulk_email_type'] ?? 'rent_due';
    
    echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #ced4da; margin: 10px 0;'>";
    echo "<h4>🧪 Testing Bulk Email Send</h4>";
    echo "<p><strong>Email Type:</strong> $email_type</p>";
    
    try {
        // Include the functions from email_notifications.php
        include_once 'email_notifications.php';
        
        $emailManager = new EmailManager();
        
        if ($email_type == 'rent_due') {
            echo "<p>📤 Getting tenants due for notice...</p>";
            $tenants = getTenantsDueForNotice();
            echo "<p><strong>Tenants Found:</strong> " . count($tenants) . "</p>";
            
            if (count($tenants) > 0) {
                $sent_count = 0;
                $failed_count = 0;
                
                foreach ($tenants as $tenant) {
                    echo "<p>📧 Sending to: {$tenant['firstname']} {$tenant['lastname']} ({$tenant['email']})</p>";
                    if ($emailManager->sendRentDueNotice($tenant['id'])) {
                        $sent_count++;
                        echo "<span style='color: green;'>✅ Sent</span><br>";
                    } else {
                        $failed_count++;
                        echo "<span style='color: red;'>❌ Failed</span><br>";
                    }
                }
                
                echo "<p><strong>📊 Results:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Sent:</strong> $sent_count</li>";
                echo "<li><strong>Failed:</strong> $failed_count</li>";
                echo "<li><strong>Total:</strong> " . count($tenants) . "</li>";
                echo "</ul>";
            }
            
        } else {
            echo "<p>📤 Getting tenants overdue...</p>";
            $tenants = getTenantsOverdue();
            echo "<p><strong>Tenants Found:</strong> " . count($tenants) . "</p>";
            
            if (count($tenants) > 0) {
                $sent_count = 0;
                $failed_count = 0;
                
                foreach ($tenants as $tenant) {
                    echo "<p>📧 Sending to: {$tenant['firstname']} {$tenant['lastname']} ({$tenant['email']})</p>";
                    if ($emailManager->sendPaymentReminder($tenant['id'])) {
                        $sent_count++;
                        echo "<span style='color: green;'>✅ Sent</span><br>";
                    } else {
                        $failed_count++;
                        echo "<span style='color: red;'>❌ Failed</span><br>";
                    }
                }
                
                echo "<p><strong>📊 Results:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Sent:</strong> $sent_count</li>";
                echo "<li><strong>Failed:</strong> $failed_count</li>";
                echo "<li><strong>Total:</strong> " . count($tenants) . "</li>";
                echo "</ul>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

// Get list of tenants for testing
echo "<h2>3. 👥 Available Tenants for Testing</h2>";

$tenant_query = "SELECT t.*, h.house_no FROM tenants t INNER JOIN houses h ON h.id = t.house_id WHERE t.status = 1 ORDER BY t.firstname";
$tenant_result = $conn->query($tenant_query);

if ($tenant_result && $tenant_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Name</th><th>Email</th><th>House</th><th>Registration Date</th></tr>";
    
    while ($tenant = $tenant_result->fetch_assoc()) {
        $email_status = !empty($tenant['email']) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$tenant['id']}</td>";
        echo "<td>{$tenant['firstname']} {$tenant['lastname']}</td>";
        echo "<td>{$email_status} {$tenant['email']}</td>";
        echo "<td>{$tenant['house_no']}</td>";
        echo "<td>{$tenant['date_in']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No active tenants found</p>";
}

// Test forms
echo "<h2>4. 🧪 Test Forms</h2>";

echo "<div style='display: flex; gap: 20px;'>";

// Individual test form
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; flex: 1;'>";
echo "<h4>🎯 Test Individual Email</h4>";
echo "<form method='post'>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Tenant ID:</strong></label><br>";
echo "<input type='number' name='test_tenant_id' placeholder='Enter tenant ID' required style='padding: 5px; width: 100px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Email Type:</strong></label><br>";
echo "<select name='email_type' style='padding: 5px;'>";
echo "<option value='rent_due'>Rent Due Notice</option>";
echo "<option value='payment_reminder'>Payment Reminder</option>";
echo "</select>";
echo "</div>";
echo "<input type='hidden' name='test_individual' value='1'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 3px;'>🧪 Test Individual</button>";
echo "</form>";
echo "</div>";

// Bulk test form
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; flex: 1;'>";
echo "<h4>📊 Test Bulk Email</h4>";
echo "<form method='post'>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Email Type:</strong></label><br>";
echo "<select name='bulk_email_type' style='padding: 5px;'>";
echo "<option value='rent_due'>Rent Due Notices</option>";
echo "<option value='payment_reminder'>Payment Reminders</option>";
echo "</select>";
echo "</div>";
echo "<input type='hidden' name='test_bulk' value='1'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 3px;'>🧪 Test Bulk</button>";
echo "</form>";
echo "</div>";

echo "</div>";

// Recent email logs
echo "<h2>5. 📋 Recent Email Logs</h2>";

$recent_logs = $conn->query("SELECT el.*, t.firstname, t.lastname FROM email_logs el LEFT JOIN tenants t ON t.id = el.tenant_id ORDER BY el.created_date DESC LIMIT 10");
if ($recent_logs && $recent_logs->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Date</th><th>Tenant</th><th>To</th><th>Type</th><th>Status</th><th>Error</th></tr>";
    
    while ($log = $recent_logs->fetch_assoc()) {
        $status_color = $log['status'] == 'sent' ? 'green' : ($log['status'] == 'failed' ? 'red' : 'orange');
        $error_msg = !empty($log['error_message']) ? htmlspecialchars($log['error_message']) : 'None';
        $tenant_name = $log['firstname'] ? "{$log['firstname']} {$log['lastname']}" : 'N/A';
        
        echo "<tr>";
        echo "<td>" . date('M d, H:i', strtotime($log['created_date'])) . "</td>";
        echo "<td>$tenant_name</td>";
        echo "<td>{$log['email_to']}</td>";
        echo "<td>{$log['email_type']}</td>";
        echo "<td style='color: $status_color;'>" . ucfirst($log['status']) . "</td>";
        echo "<td style='font-size: 12px; max-width: 200px; word-wrap: break-word;'>$error_msg</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No email logs found</p>";
}

// Quick actions
echo "<h2>6. 🎯 Quick Actions</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da;'>";
echo "<p><a href='index.php?page=email_settings' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Email Settings</a></p>";
echo "<p><a href='index.php?page=email_notifications' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Email Notifications</a></p>";
echo "<p><a href='debug_email_issue.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Email Configuration Debug</a></p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
</style>
