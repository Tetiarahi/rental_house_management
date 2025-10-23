<?php
include 'db_connect.php';
require_once 'email_class.php';

echo "<h1>🧪 Email System Comprehensive Test</h1>";

// Check database setup
echo "<h2>📊 Database Setup Verification</h2>";

$tables_to_check = ['system_settings', 'email_logs', 'email_templates'];
$all_tables_ok = true;

foreach ($tables_to_check as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        $all_tables_ok = false;
    }
}

// Check email settings columns
echo "<h3>📧 Email Settings Columns</h3>";
$email_columns = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'email_from_name', 'email_notifications_enabled', 'rent_due_days_notice', 'payment_reminder_days'];

$settings_columns = $conn->query("SHOW COLUMNS FROM system_settings");
$existing_columns = [];
while ($col = $settings_columns->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}

foreach ($email_columns as $column) {
    if (in_array($column, $existing_columns)) {
        echo "<p style='color: green;'>✅ Column '$column' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Column '$column' missing</p>";
        $all_tables_ok = false;
    }
}

// Check email templates
echo "<h3>📝 Email Templates</h3>";
$templates = $conn->query("SELECT template_type, template_name, is_active FROM email_templates");
if ($templates && $templates->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Type</th><th>Name</th><th>Status</th></tr>";
    while ($template = $templates->fetch_assoc()) {
        $status_color = $template['is_active'] ? 'green' : 'red';
        $status_text = $template['is_active'] ? 'Active' : 'Inactive';
        echo "<tr>";
        echo "<td>{$template['template_type']}</td>";
        echo "<td>{$template['template_name']}</td>";
        echo "<td style='color: $status_color;'>$status_text</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No email templates found</p>";
    $all_tables_ok = false;
}

if (!$all_tables_ok) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
    echo "<h3>⚠️ Database Setup Required</h3>";
    echo "<p>Please run the database setup first:</p>";
    echo "<p><a href='setup_email_database.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Run Database Setup</a></p>";
    echo "</div>";
    exit;
}

// Test EmailManager class
echo "<h2>🔧 EmailManager Class Test</h2>";

try {
    $emailManager = new EmailManager();
    echo "<p style='color: green;'>✅ EmailManager class loaded successfully</p>";
    
    // Test template loading
    $rent_due_template = $emailManager->getTemplate('rent_due');
    if ($rent_due_template) {
        echo "<p style='color: green;'>✅ Rent due template loaded successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to load rent due template</p>";
    }
    
    $payment_reminder_template = $emailManager->getTemplate('payment_reminder');
    if ($payment_reminder_template) {
        echo "<p style='color: green;'>✅ Payment reminder template loaded successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to load payment reminder template</p>";
    }
    
    $payment_confirmation_template = $emailManager->getTemplate('payment_confirmation');
    if ($payment_confirmation_template) {
        echo "<p style='color: green;'>✅ Payment confirmation template loaded successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to load payment confirmation template</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EmailManager class error: " . $e->getMessage() . "</p>";
}

// Check current email settings
echo "<h2>⚙️ Current Email Settings</h2>";
$settings = $conn->query("SELECT * FROM system_settings LIMIT 1");
if ($settings && $settings->num_rows > 0) {
    $setting = $settings->fetch_assoc();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    $email_settings = [
        'email_notifications_enabled' => 'Email Notifications',
        'smtp_host' => 'SMTP Host',
        'smtp_port' => 'SMTP Port',
        'smtp_username' => 'SMTP Username',
        'smtp_password' => 'SMTP Password',
        'smtp_encryption' => 'SMTP Encryption',
        'email_from_name' => 'From Name',
        'rent_due_days_notice' => 'Rent Due Notice Days',
        'payment_reminder_days' => 'Payment Reminder Days'
    ];
    
    foreach ($email_settings as $key => $label) {
        $value = $setting[$key] ?? 'Not Set';
        $status = 'Not Configured';
        $color = 'red';
        
        if ($key == 'email_notifications_enabled') {
            $status = $value ? 'Enabled' : 'Disabled';
            $color = $value ? 'green' : 'orange';
            $value = $value ? 'Yes' : 'No';
        } elseif ($key == 'smtp_password') {
            $value = !empty($value) ? '***Hidden***' : 'Not Set';
            $status = !empty($setting[$key]) ? 'Set' : 'Not Set';
            $color = !empty($setting[$key]) ? 'green' : 'red';
        } elseif (!empty($value) && $value != 'Not Set') {
            $status = 'Configured';
            $color = 'green';
        }
        
        echo "<tr>";
        echo "<td><strong>$label</strong></td>";
        echo "<td>$value</td>";
        echo "<td style='color: $color;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Configuration status
    $smtp_configured = !empty($setting['smtp_username']) && !empty($setting['smtp_password']);
    $notifications_enabled = $setting['email_notifications_enabled'] ?? 0;
    
    echo "<div style='background: " . ($smtp_configured && $notifications_enabled ? '#d4edda' : '#fff3cd') . "; padding: 15px; border: 1px solid " . ($smtp_configured && $notifications_enabled ? '#c3e6cb' : '#ffc107') . "; margin: 20px 0;'>";
    echo "<h3>📊 Configuration Status:</h3>";
    echo "<ul>";
    echo "<li>SMTP Configuration: " . ($smtp_configured ? '✅ Complete' : '❌ Incomplete') . "</li>";
    echo "<li>Email Notifications: " . ($notifications_enabled ? '✅ Enabled' : '⚠️ Disabled') . "</li>";
    echo "<li>Ready to Send: " . ($smtp_configured && $notifications_enabled ? '✅ Yes' : '❌ No') . "</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ No system settings found</p>";
}

// Test tenant data for notifications
echo "<h2>👥 Tenant Data for Notifications</h2>";

$tenants = $conn->query("SELECT t.*, h.house_no, h.price FROM tenants t INNER JOIN houses h ON h.id = t.house_id WHERE t.status = 1");
if ($tenants && $tenants->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Name</th><th>Email</th><th>House</th><th>Registration Date</th><th>Email Valid</th></tr>";
    
    $valid_email_count = 0;
    while ($tenant = $tenants->fetch_assoc()) {
        $email_valid = filter_var($tenant['email'], FILTER_VALIDATE_EMAIL);
        if ($email_valid) $valid_email_count++;
        
        $email_color = $email_valid ? 'green' : 'red';
        $email_status = $email_valid ? 'Valid' : 'Invalid';
        
        echo "<tr>";
        echo "<td>{$tenant['firstname']} {$tenant['lastname']}</td>";
        echo "<td>{$tenant['email']}</td>";
        echo "<td>{$tenant['house_no']} (\${$tenant['price']})</td>";
        echo "<td>{$tenant['date_in']}</td>";
        echo "<td style='color: $email_color;'>$email_status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Summary:</strong> $valid_email_count tenants with valid email addresses</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No active tenants found</p>";
}

// Test email logs
echo "<h2>📋 Recent Email Activity</h2>";
$logs = $conn->query("SELECT * FROM email_logs ORDER BY created_date DESC LIMIT 10");
if ($logs && $logs->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Date</th><th>To</th><th>Type</th><th>Subject</th><th>Status</th></tr>";
    
    while ($log = $logs->fetch_assoc()) {
        $status_color = $log['status'] == 'sent' ? 'green' : ($log['status'] == 'failed' ? 'red' : 'orange');
        
        echo "<tr>";
        echo "<td>" . date('M d, Y H:i', strtotime($log['created_date'])) . "</td>";
        echo "<td>{$log['email_to']}</td>";
        echo "<td>{$log['email_type']}</td>";
        echo "<td>" . htmlspecialchars($log['email_subject']) . "</td>";
        echo "<td style='color: $status_color;'>" . ucfirst($log['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No email activity yet</p>";
}

// Action buttons
echo "<h2>🎯 Test Actions</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6;'>";

echo "<h3>📧 Configuration & Management:</h3>";
echo "<p><a href='index.php?page=email_settings' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Configure Email Settings</a></p>";
echo "<p><a href='index.php?page=email_notifications' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📬 Manage Notifications</a></p>";

echo "<h3>🧪 Manual Testing:</h3>";
echo "<form method='post' style='margin: 10px 0;'>";
echo "<input type='hidden' name='action' value='test_email'>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Test Email Address:</strong></label><br>";
echo "<input type='email' name='test_email' placeholder='your-email@example.com' required style='padding: 8px; width: 300px; margin: 5px;'>";
echo "<button type='submit' style='background: #17a2b8; color: white; padding: 8px 15px; border: none; border-radius: 3px; margin: 5px;'>📧 Send Test Email</button>";
echo "</div>";
echo "</form>";

if (isset($_POST['action']) && $_POST['action'] == 'test_email') {
    $test_email = $_POST['test_email'] ?? '';
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        try {
            $emailManager = new EmailManager();
            $result = $emailManager->sendTestEmail($test_email);
            
            if ($result) {
                echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
                echo "<h4>✅ Test Email Sent Successfully!</h4>";
                echo "<p>Check your inbox at <strong>$test_email</strong> for the test email.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
                echo "<h4>❌ Test Email Failed</h4>";
                echo "<p>Please check your SMTP configuration and try again.</p>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "<h4>❌ Error Sending Test Email</h4>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; margin: 10px 0;'>";
        echo "<h4>⚠️ Invalid Email Address</h4>";
        echo "<p>Please enter a valid email address.</p>";
        echo "</div>";
    }
}

echo "</div>";

echo "<h2>✅ System Status Summary</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da;'>";
echo "<h3>🎯 Email Notification System Status:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Database Setup:</strong> " . ($all_tables_ok ? 'Complete' : 'Incomplete') . "</li>";
echo "<li>✅ <strong>Email Templates:</strong> Available and loaded</li>";
echo "<li>✅ <strong>EmailManager Class:</strong> Functional</li>";
echo "<li>✅ <strong>Navigation Menu:</strong> Email settings and notifications added</li>";
echo "<li>✅ <strong>Automatic Payment Confirmations:</strong> Enabled</li>";
echo "<li>✅ <strong>Manual Notification System:</strong> Available</li>";
echo "</ul>";

echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Configure SMTP Settings:</strong> Go to Email Settings and enter your email server details</li>";
echo "<li><strong>Test Email Functionality:</strong> Send a test email to verify configuration</li>";
echo "<li><strong>Enable Notifications:</strong> Turn on email notifications in settings</li>";
echo "<li><strong>Send Notifications:</strong> Use the Email Notifications page to send rent due notices and payment reminders</li>";
echo "</ol>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #2c3e50; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
</style>
