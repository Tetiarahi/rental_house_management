<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
require_once 'email_class.php';

echo "<h1>🔍 Email Debug & Troubleshooting</h1>";

// Check if database setup was run
echo "<h2>1. 📊 Database Setup Check</h2>";

// Check if email columns exist
$settings_check = $conn->query("SHOW COLUMNS FROM system_settings LIKE 'smtp_host'");
if (!$settings_check || $settings_check->num_rows == 0) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
    echo "<h3>❌ Database Not Set Up</h3>";
    echo "<p>The email system database tables haven't been created yet.</p>";
    echo "<p><strong>Solution:</strong> <a href='setup_email_database.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Run Database Setup First</a></p>";
    echo "</div>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database setup completed</p>";
}

// Get current email settings
echo "<h2>2. ⚙️ Current Email Configuration</h2>";
$settings_query = "SELECT * FROM system_settings LIMIT 1";
$settings_result = $conn->query($settings_query);

if ($settings_result && $settings_result->num_rows > 0) {
    $settings = $settings_result->fetch_assoc();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    // Check each setting
    $smtp_host = $settings['smtp_host'] ?? '';
    $smtp_port = $settings['smtp_port'] ?? '';
    $smtp_username = $settings['smtp_username'] ?? '';
    $smtp_password = $settings['smtp_password'] ?? '';
    $smtp_encryption = $settings['smtp_encryption'] ?? '';
    $email_from_name = $settings['email_from_name'] ?? '';
    $notifications_enabled = $settings['email_notifications_enabled'] ?? 0;
    
    $config_issues = [];
    
    // SMTP Host
    $host_status = !empty($smtp_host) ? 'OK' : 'MISSING';
    $host_color = !empty($smtp_host) ? 'green' : 'red';
    if (empty($smtp_host)) $config_issues[] = 'SMTP Host not configured';
    echo "<tr><td>SMTP Host</td><td>$smtp_host</td><td style='color: $host_color;'>$host_status</td></tr>";
    
    // SMTP Port
    $port_status = !empty($smtp_port) ? 'OK' : 'MISSING';
    $port_color = !empty($smtp_port) ? 'green' : 'red';
    if (empty($smtp_port)) $config_issues[] = 'SMTP Port not configured';
    echo "<tr><td>SMTP Port</td><td>$smtp_port</td><td style='color: $port_color;'>$port_status</td></tr>";
    
    // SMTP Username
    $user_status = !empty($smtp_username) ? 'OK' : 'MISSING';
    $user_color = !empty($smtp_username) ? 'green' : 'red';
    if (empty($smtp_username)) $config_issues[] = 'SMTP Username not configured';
    echo "<tr><td>SMTP Username</td><td>$smtp_username</td><td style='color: $user_color;'>$user_status</td></tr>";
    
    // SMTP Password
    $pass_status = !empty($smtp_password) ? 'OK' : 'MISSING';
    $pass_color = !empty($smtp_password) ? 'green' : 'red';
    $pass_display = !empty($smtp_password) ? '***Hidden***' : 'Not Set';
    if (empty($smtp_password)) $config_issues[] = 'SMTP Password not configured';
    echo "<tr><td>SMTP Password</td><td>$pass_display</td><td style='color: $pass_color;'>$pass_status</td></tr>";
    
    // Encryption
    $enc_status = !empty($smtp_encryption) ? 'OK' : 'DEFAULT';
    $enc_color = !empty($smtp_encryption) ? 'green' : 'orange';
    echo "<tr><td>SMTP Encryption</td><td>$smtp_encryption</td><td style='color: $enc_color;'>$enc_status</td></tr>";
    
    // From Name
    $name_status = !empty($email_from_name) ? 'OK' : 'DEFAULT';
    $name_color = !empty($email_from_name) ? 'green' : 'orange';
    echo "<tr><td>From Name</td><td>$email_from_name</td><td style='color: $name_color;'>$name_status</td></tr>";
    
    // Notifications Enabled
    $notif_status = $notifications_enabled ? 'ENABLED' : 'DISABLED';
    $notif_color = $notifications_enabled ? 'green' : 'red';
    $notif_display = $notifications_enabled ? 'Yes' : 'No';
    if (!$notifications_enabled) $config_issues[] = 'Email notifications are disabled';
    echo "<tr><td>Notifications Enabled</td><td>$notif_display</td><td style='color: $notif_color;'>$notif_status</td></tr>";
    
    echo "</table>";
    
    // Show configuration issues
    if (!empty($config_issues)) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
        echo "<h3>❌ Configuration Issues Found:</h3>";
        echo "<ul>";
        foreach ($config_issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "<p><strong>Solution:</strong> <a href='index.php?page=email_settings' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>⚙️ Fix Email Settings</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
        echo "<h3>✅ Configuration Looks Good!</h3>";
        echo "<p>All required email settings are configured.</p>";
        echo "</div>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No system settings found in database</p>";
    exit;
}

// Test PHP mail function
echo "<h2>3. 📧 PHP Mail Function Test</h2>";

if (function_exists('mail')) {
    echo "<p style='color: green;'>✅ PHP mail() function is available</p>";
    
    // Check if mail is configured
    $mail_config = [
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from'),
        'sendmail_path' => ini_get('sendmail_path')
    ];
    
    echo "<h4>PHP Mail Configuration:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Setting</th><th>Value</th></tr>";
    foreach ($mail_config as $key => $value) {
        $display_value = !empty($value) ? $value : 'Not Set';
        echo "<tr><td>$key</td><td>$display_value</td></tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>❌ PHP mail() function is not available</p>";
}

// Test EmailManager class
echo "<h2>4. 🔧 EmailManager Class Test</h2>";

try {
    $emailManager = new EmailManager();
    echo "<p style='color: green;'>✅ EmailManager class loaded successfully</p>";
    
    // Test getting settings
    $email_settings = $emailManager->getEmailSettings();
    if ($email_settings) {
        echo "<p style='color: green;'>✅ Email settings loaded from database</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to load email settings</p>";
    }
    
    // Test template loading
    $test_template = $emailManager->getTemplate('rent_due');
    if ($test_template) {
        echo "<p style='color: green;'>✅ Email templates are accessible</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to load email templates</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EmailManager error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check email logs for recent attempts
echo "<h2>5. 📋 Recent Email Attempts</h2>";

$recent_logs = $conn->query("SELECT * FROM email_logs ORDER BY created_date DESC LIMIT 5");
if ($recent_logs && $recent_logs->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Date</th><th>To</th><th>Type</th><th>Status</th><th>Error</th></tr>";
    
    while ($log = $recent_logs->fetch_assoc()) {
        $status_color = $log['status'] == 'sent' ? 'green' : ($log['status'] == 'failed' ? 'red' : 'orange');
        $error_msg = !empty($log['error_message']) ? htmlspecialchars($log['error_message']) : 'None';
        
        echo "<tr>";
        echo "<td>" . date('M d, H:i', strtotime($log['created_date'])) . "</td>";
        echo "<td>{$log['email_to']}</td>";
        echo "<td>{$log['email_type']}</td>";
        echo "<td style='color: $status_color;'>" . ucfirst($log['status']) . "</td>";
        echo "<td style='font-size: 12px;'>$error_msg</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No email attempts found in logs</p>";
}

// Manual test email form
echo "<h2>6. 🧪 Manual Email Test</h2>";

if (isset($_POST['test_email_debug'])) {
    $test_email = $_POST['test_email'] ?? '';
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #ced4da; margin: 10px 0;'>";
        echo "<h4>🔍 Testing Email Send to: $test_email</h4>";
        
        try {
            // Enable error reporting for this test
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            $emailManager = new EmailManager();
            
            echo "<p>📤 Attempting to send test email...</p>";
            
            // Try to send test email with detailed error reporting
            $result = $emailManager->sendTestEmail($test_email);
            
            if ($result) {
                echo "<p style='color: green;'>✅ <strong>SUCCESS!</strong> Test email sent successfully!</p>";
                echo "<p>Check your inbox at <strong>$test_email</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>FAILED!</strong> Test email could not be sent.</p>";
                
                // Check the most recent log entry for error details
                $error_log = $conn->query("SELECT error_message FROM email_logs WHERE email_to = '$test_email' ORDER BY created_date DESC LIMIT 1");
                if ($error_log && $error_log->num_rows > 0) {
                    $error = $error_log->fetch_assoc();
                    if (!empty($error['error_message'])) {
                        echo "<p><strong>Error Details:</strong> " . htmlspecialchars($error['error_message']) . "</p>";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ <strong>EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; margin: 10px 0;'>";
        echo "<p>⚠️ Please enter a valid email address</p>";
        echo "</div>";
    }
}

echo "<form method='post' style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; margin: 10px 0;'>";
echo "<h4>🧪 Send Test Email</h4>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Test Email Address:</strong></label><br>";
echo "<input type='email' name='test_email' placeholder='your-email@example.com' required style='padding: 8px; width: 300px; margin: 5px;'>";
echo "<input type='hidden' name='test_email_debug' value='1'>";
echo "<button type='submit' style='background: #17a2b8; color: white; padding: 8px 15px; border: none; border-radius: 3px; margin: 5px;'>📧 Send Debug Test Email</button>";
echo "</div>";
echo "</form>";

// Common solutions
echo "<h2>7. 🛠️ Common Solutions</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da;'>";

echo "<h3>📧 Gmail SMTP Configuration:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> smtp.gmail.com</li>";
echo "<li><strong>SMTP Port:</strong> 587 (TLS) or 465 (SSL)</li>";
echo "<li><strong>Username:</strong> your-email@gmail.com</li>";
echo "<li><strong>Password:</strong> Use App Password (not regular password)</li>";
echo "<li><strong>Encryption:</strong> tls</li>";
echo "</ul>";

echo "<h3>📧 Outlook/Hotmail SMTP Configuration:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> smtp-mail.outlook.com</li>";
echo "<li><strong>SMTP Port:</strong> 587</li>";
echo "<li><strong>Username:</strong> your-email@outlook.com</li>";
echo "<li><strong>Password:</strong> Your account password</li>";
echo "<li><strong>Encryption:</strong> tls</li>";
echo "</ul>";

echo "<h3>🔧 Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check SMTP Settings:</strong> Make sure all fields are filled correctly</li>";
echo "<li><strong>Enable 2-Factor Authentication:</strong> For Gmail, use App Passwords</li>";
echo "<li><strong>Check Firewall:</strong> Ensure ports 587/465 are not blocked</li>";
echo "<li><strong>Test with Different Email:</strong> Try a different email provider</li>";
echo "<li><strong>Check Server Logs:</strong> Look for PHP/Apache error logs</li>";
echo "<li><strong>Enable Notifications:</strong> Make sure email notifications are enabled</li>";
echo "</ol>";

echo "<h3>🎯 Quick Actions:</h3>";
echo "<p><a href='index.php?page=email_settings' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Configure Email Settings</a></p>";
echo "<p><a href='setup_email_database.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 Re-run Database Setup</a></p>";

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
