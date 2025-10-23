<?php
include 'db_connect.php';
session_start();

echo "<h1>📧 Test PDF Email Attachment System</h1>";

// Check if user is logged in
if (!isset($_SESSION['login_id'])) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Not Logged In</h3>";
    echo "<p>Please log in to test the email system.</p>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Login</a>";
    echo "</div>";
    exit;
}

// Handle test email sending
if (isset($_POST['action']) && $_POST['action'] == 'test_email') {
    $tenant_id = $_POST['tenant_id'];
    $email_type = $_POST['email_type'];
    
    require_once 'email_class.php';
    $emailManager = new EmailManager();
    
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🔄 Sending Test Email...</h3>";
    
    if ($email_type == 'rent_due') {
        $result = $emailManager->sendRentDueNotice($tenant_id);
        $email_name = "Rent Due Notice";
    } else {
        $result = $emailManager->sendPaymentReminder($tenant_id);
        $email_name = "Payment Reminder";
    }
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ $email_name sent successfully with PDF attachment!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send $email_name</p>";
    }
    echo "</div>";
}

echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ PDF Email Attachment Test</h3>";
echo "<p>This page allows you to test sending emails with PDF invoice attachments.</p>";
echo "<p><strong>What happens when you send a test email:</strong></p>";
echo "<ul>";
echo "<li>📧 Email is sent to the tenant's email address</li>";
echo "<li>📄 PDF invoice is automatically generated and attached</li>";
echo "<li>🏢 Invoice includes BPA branding and professional layout</li>";
echo "<li>💰 Outstanding balance and due dates are calculated</li>";
echo "<li>📋 Email includes both HTML content and PDF attachment</li>";
echo "</ul>";
echo "</div>";

// Get active tenants for testing
echo "<h2>👥 Select Tenant for Email Test</h2>";

$tenants_query = "SELECT 
                    t.id,
                    t.firstname,
                    t.lastname,
                    t.email,
                    t.contact,
                    t.date_in,
                    h.house_no,
                    h.price as monthly_rent
                  FROM tenants t
                  INNER JOIN houses h ON h.id = t.house_id
                  WHERE t.status = 1 AND t.email != '' AND t.email IS NOT NULL
                  ORDER BY h.house_no";

$tenants_result = $conn->query($tenants_query);

if ($tenants_result && $tenants_result->num_rows > 0) {
    echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo "<input type='hidden' name='action' value='test_email'>";
    
    echo "<div style='margin-bottom: 20px;'>";
    echo "<label style='font-weight: bold; display: block; margin-bottom: 10px;'>Select Tenant:</label>";
    echo "<select name='tenant_id' required style='padding: 8px; font-size: 14px; width: 100%; max-width: 400px;'>";
    echo "<option value=''>-- Choose a tenant --</option>";
    
    while ($tenant = $tenants_result->fetch_assoc()) {
        $full_name = $tenant['firstname'] . ' ' . $tenant['lastname'];
        $move_in_date = date('M d, Y', strtotime($tenant['date_in']));
        
        echo "<option value='{$tenant['id']}'>";
        echo "$full_name - {$tenant['house_no']} - {$tenant['email']} - $" . number_format($tenant['monthly_rent'], 2);
        echo "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 20px;'>";
    echo "<label style='font-weight: bold; display: block; margin-bottom: 10px;'>Email Type:</label>";
    echo "<div>";
    echo "<label style='margin-right: 20px;'>";
    echo "<input type='radio' name='email_type' value='rent_due' required style='margin-right: 5px;'>";
    echo "Rent Due Notice (with invoice attachment)";
    echo "</label>";
    echo "<label>";
    echo "<input type='radio' name='email_type' value='payment_reminder' required style='margin-right: 5px;'>";
    echo "Payment Reminder (with outstanding invoice attachment)";
    echo "</label>";
    echo "</div>";
    echo "</div>";
    
    echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
    echo "📧 Send Test Email with PDF Attachment";
    echo "</button>";
    echo "</form>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4>❌ No Tenants Available</h4>";
    echo "<p>No active tenants with email addresses found. Please:</p>";
    echo "<ul>";
    echo "<li>Add some tenants with valid email addresses</li>";
    echo "<li>Make sure tenants have status = 1 (active)</li>";
    echo "<li>Ensure email addresses are not empty</li>";
    echo "</ul>";
    echo "<a href='index.php?page=tenants' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Tenants</a>";
    echo "</div>";
}

// Show email configuration status
echo "<h2>⚙️ Email Configuration Status</h2>";

$settings_query = "SELECT * FROM email_settings LIMIT 1";
$settings_result = $conn->query($settings_query);

if ($settings_result && $settings_result->num_rows > 0) {
    $settings = $settings_result->fetch_assoc();
    
    echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
    echo "<h4>📧 SMTP Configuration</h4>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><td style='padding: 5px; font-weight: bold;'>SMTP Host:</td><td style='padding: 5px;'>{$settings['smtp_host']}</td></tr>";
    echo "<tr><td style='padding: 5px; font-weight: bold;'>SMTP Port:</td><td style='padding: 5px;'>{$settings['smtp_port']}</td></tr>";
    echo "<tr><td style='padding: 5px; font-weight: bold;'>Encryption:</td><td style='padding: 5px;'>{$settings['smtp_encryption']}</td></tr>";
    echo "<tr><td style='padding: 5px; font-weight: bold;'>Username:</td><td style='padding: 5px;'>{$settings['smtp_username']}</td></tr>";
    echo "<tr><td style='padding: 5px; font-weight: bold;'>From Name:</td><td style='padding: 5px;'>{$settings['email_from_name']}</td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4>❌ Email Not Configured</h4>";
    echo "<p>SMTP email settings are not configured. Please configure email settings first.</p>";
    echo "<a href='index.php?page=email_settings' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Configure Email</a>";
    echo "</div>";
}

// Show recent email logs
echo "<h2>📋 Recent Email Logs</h2>";

$logs_query = "SELECT el.*, t.firstname, t.lastname 
               FROM email_logs el 
               LEFT JOIN tenants t ON t.id = el.tenant_id 
               ORDER BY el.created_at DESC 
               LIMIT 10";
$logs_result = $conn->query($logs_query);

if ($logs_result && $logs_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th style='padding: 10px;'>Date</th>";
    echo "<th style='padding: 10px;'>Tenant</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Subject</th>";
    echo "<th style='padding: 10px;'>Type</th>";
    echo "<th style='padding: 10px;'>Status</th>";
    echo "<th style='padding: 10px;'>Message</th>";
    echo "</tr>";
    
    while ($log = $logs_result->fetch_assoc()) {
        $status_color = $log['status'] == 'sent' ? 'green' : 'red';
        $tenant_name = $log['firstname'] ? $log['firstname'] . ' ' . $log['lastname'] : 'N/A';
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . date('M d, Y H:i', strtotime($log['created_at'])) . "</td>";
        echo "<td style='padding: 8px;'>$tenant_name</td>";
        echo "<td style='padding: 8px;'>{$log['recipient_email']}</td>";
        echo "<td style='padding: 8px;'>{$log['subject']}</td>";
        echo "<td style='padding: 8px;'>{$log['email_type']}</td>";
        echo "<td style='padding: 8px; color: $status_color; font-weight: bold;'>{$log['status']}</td>";
        echo "<td style='padding: 8px;'>{$log['error_message']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #666;'>No email logs found.</p>";
}

// Instructions
echo "<h2>📖 How to Test PDF Email Attachments</h2>";
echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px;'>";
echo "<h4>🔧 Testing Steps</h4>";
echo "<ol>";
echo "<li><strong>Select a Tenant:</strong> Choose a tenant with a valid email address from the dropdown</li>";
echo "<li><strong>Choose Email Type:</strong> Select either 'Rent Due Notice' or 'Payment Reminder'</li>";
echo "<li><strong>Send Test Email:</strong> Click the send button to send the email with PDF attachment</li>";
echo "<li><strong>Check Email:</strong> Go to the tenant's email inbox to verify the email was received</li>";
echo "<li><strong>Verify PDF:</strong> Check that the PDF invoice is attached to the email</li>";
echo "<li><strong>Review Logs:</strong> Check the email logs table above for sending status</li>";
echo "</ol>";

echo "<h4>✅ What to Expect</h4>";
echo "<ul>";
echo "<li>📧 Email will be sent to the tenant's email address</li>";
echo "<li>📄 PDF file will be attached with filename like 'BPA-INV-2025-0001-10.pdf'</li>";
echo "<li>🏢 PDF will contain BPA branding and professional invoice layout</li>";
echo "<li>💰 Invoice will show outstanding balance and due dates</li>";
echo "<li>📋 Email body will mention that invoice is attached</li>";
echo "</ul>";
echo "</div>";

// Navigation
echo "<h2>🔗 Navigation</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='index.php?page=email_notifications' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Email Notifications</a>";
echo "<a href='index.php?page=email_settings' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Email Settings</a>";
echo "<a href='index.php?page=tenants' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Tenants</a>";
echo "<a href='index.php?page=home' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
ul, ol { margin: 10px 0; padding-left: 20px; }
</style>
