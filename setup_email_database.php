<?php
include 'db_connect.php';

echo "<h1>🔧 Setting Up Email Configuration Database</h1>";

// Check if email settings columns already exist
$check_columns = $conn->query("SHOW COLUMNS FROM system_settings LIKE 'smtp_host'");
if ($check_columns->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Email settings columns already exist. Skipping column creation.</p>";
} else {
    echo "<h2>📧 Adding Email Configuration Columns...</h2>";
    
    $email_columns = [
        "ALTER TABLE `system_settings` ADD COLUMN `smtp_host` varchar(255) DEFAULT 'smtp.gmail.com' COMMENT 'SMTP server hostname'",
        "ALTER TABLE `system_settings` ADD COLUMN `smtp_port` int(11) DEFAULT 587 COMMENT 'SMTP server port'",
        "ALTER TABLE `system_settings` ADD COLUMN `smtp_username` varchar(255) DEFAULT '' COMMENT 'SMTP username/email'",
        "ALTER TABLE `system_settings` ADD COLUMN `smtp_password` varchar(255) DEFAULT '' COMMENT 'SMTP password or app password'",
        "ALTER TABLE `system_settings` ADD COLUMN `smtp_encryption` varchar(10) DEFAULT 'tls' COMMENT 'SMTP encryption: tls, ssl, or none'",
        "ALTER TABLE `system_settings` ADD COLUMN `email_from_name` varchar(255) DEFAULT 'Rental Management System' COMMENT 'From name for emails'",
        "ALTER TABLE `system_settings` ADD COLUMN `email_notifications_enabled` tinyint(1) DEFAULT 1 COMMENT 'Enable/disable email notifications'",
        "ALTER TABLE `system_settings` ADD COLUMN `rent_due_days_notice` int(11) DEFAULT 3 COMMENT 'Days before rent due to send notice'",
        "ALTER TABLE `system_settings` ADD COLUMN `payment_reminder_days` int(11) DEFAULT 7 COMMENT 'Days after due date to send reminder'"
    ];
    
    foreach ($email_columns as $sql) {
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Added column successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . $conn->error . "</p>";
        }
    }
}

// Check if email_logs table exists
$check_logs_table = $conn->query("SHOW TABLES LIKE 'email_logs'");
if ($check_logs_table->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ email_logs table already exists. Skipping creation.</p>";
} else {
    echo "<h2>📋 Creating Email Logs Table...</h2>";
    
    $create_logs_table = "CREATE TABLE `email_logs` (
      `id` int(30) NOT NULL AUTO_INCREMENT,
      `tenant_id` int(30) DEFAULT NULL,
      `email_to` varchar(255) NOT NULL,
      `email_subject` varchar(500) NOT NULL,
      `email_type` varchar(50) NOT NULL COMMENT 'rent_due, payment_reminder, payment_confirmation, etc.',
      `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, sent, failed',
      `error_message` text DEFAULT NULL,
      `sent_date` datetime DEFAULT NULL,
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `tenant_id` (`tenant_id`),
      KEY `email_type` (`email_type`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_logs_table)) {
        echo "<p style='color: green;'>✅ email_logs table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating email_logs table: " . $conn->error . "</p>";
    }
}

// Check if email_templates table exists
$check_templates_table = $conn->query("SHOW TABLES LIKE 'email_templates'");
if ($check_templates_table->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ email_templates table already exists. Skipping creation.</p>";
} else {
    echo "<h2>📝 Creating Email Templates Table...</h2>";
    
    $create_templates_table = "CREATE TABLE `email_templates` (
      `id` int(30) NOT NULL AUTO_INCREMENT,
      `template_name` varchar(100) NOT NULL,
      `template_type` varchar(50) NOT NULL COMMENT 'rent_due, payment_reminder, payment_confirmation',
      `subject` varchar(500) NOT NULL,
      `body_html` text NOT NULL,
      `body_text` text NOT NULL,
      `variables` text COMMENT 'JSON array of available variables',
      `is_active` tinyint(1) DEFAULT 1,
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `template_type` (`template_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_templates_table)) {
        echo "<p style='color: green;'>✅ email_templates table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating email_templates table: " . $conn->error . "</p>";
    }
}

// Check if templates already exist
$check_existing_templates = $conn->query("SELECT COUNT(*) as count FROM email_templates");
$template_count = $check_existing_templates->fetch_assoc()['count'];

if ($template_count > 0) {
    echo "<p style='color: orange;'>⚠️ Email templates already exist ($template_count templates). Skipping template insertion.</p>";
} else {
    echo "<h2>📧 Inserting Default Email Templates...</h2>";
    
    // Insert rent due template
    $rent_due_template = "INSERT INTO `email_templates` (`template_name`, `template_type`, `subject`, `body_html`, `body_text`, `variables`) VALUES
    ('Rent Due Notice', 'rent_due', 'Rent Due Notice - {house_no}', 
    '<html><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
    <div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">
        <h2 style=\"color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;\">
            🏠 Rent Due Notice
        </h2>
        
        <p>Dear {tenant_name},</p>
        
        <p>This is a friendly reminder that your rent payment for <strong>{house_no}</strong> is due on <strong>{due_date}</strong>.</p>
        
        <div style=\"background: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;\">
            <h3 style=\"margin-top: 0; color: #2c3e50;\">Payment Details:</h3>
            <ul style=\"margin: 10px 0;\">
                <li><strong>Property:</strong> {house_no}</li>
                <li><strong>Amount Due:</strong> \${rent_amount}</li>
                <li><strong>Due Date:</strong> {due_date}</li>
                <li><strong>Days Until Due:</strong> {days_until_due}</li>
            </ul>
        </div>
        
        <p>Please ensure your payment is submitted on time to avoid any late fees.</p>
        
        <p>If you have any questions or concerns, please contact us immediately.</p>
        
        <p>Thank you for your prompt attention to this matter.</p>
        
        <div style=\"margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;\">
            <p style=\"margin: 0;\"><strong>{system_name}</strong></p>
            <p style=\"margin: 5px 0; color: #666;\">📧 {system_email}</p>
            <p style=\"margin: 5px 0; color: #666;\">📞 {system_contact}</p>
        </div>
    </div>
    </body></html>',
    
    'Dear {tenant_name},

This is a friendly reminder that your rent payment for {house_no} is due on {due_date}.

Payment Details:
- Property: {house_no}
- Amount Due: \${rent_amount}
- Due Date: {due_date}
- Days Until Due: {days_until_due}

Please ensure your payment is submitted on time to avoid any late fees.

If you have any questions or concerns, please contact us immediately.

Thank you for your prompt attention to this matter.

{system_name}
Email: {system_email}
Phone: {system_contact}',
    
    '[\"tenant_name\", \"house_no\", \"rent_amount\", \"due_date\", \"days_until_due\", \"system_name\", \"system_email\", \"system_contact\"]')";
    
    if ($conn->query($rent_due_template)) {
        echo "<p style='color: green;'>✅ Rent Due template inserted successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error inserting Rent Due template: " . $conn->error . "</p>";
    }
    
    // Insert payment reminder template
    $payment_reminder_template = "INSERT INTO `email_templates` (`template_name`, `template_type`, `subject`, `body_html`, `body_text`, `variables`) VALUES
    ('Payment Reminder', 'payment_reminder', 'Payment Overdue - {house_no}', 
    '<html><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
    <div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">
        <h2 style=\"color: #e74c3c; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;\">
            ⚠️ Payment Overdue Notice
        </h2>
        
        <p>Dear {tenant_name},</p>
        
        <p>This is an urgent reminder that your rent payment for <strong>{house_no}</strong> was due on <strong>{due_date}</strong> and is now <strong>{days_overdue} days overdue</strong>.</p>
        
        <div style=\"background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;\">
            <h3 style=\"margin-top: 0; color: #856404;\">⚠️ Overdue Payment Details:</h3>
            <ul style=\"margin: 10px 0;\">
                <li><strong>Property:</strong> {house_no}</li>
                <li><strong>Amount Due:</strong> \${rent_amount}</li>
                <li><strong>Original Due Date:</strong> {due_date}</li>
                <li><strong>Days Overdue:</strong> {days_overdue}</li>
                <li><strong>Late Fees May Apply</strong></li>
            </ul>
        </div>
        
        <p><strong>Please submit your payment immediately to avoid further late fees and potential legal action.</strong></p>
        
        <p>If you are experiencing financial difficulties, please contact us immediately to discuss payment arrangements.</p>
        
        <p>Thank you for your immediate attention to this matter.</p>
        
        <div style=\"margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;\">
            <p style=\"margin: 0;\"><strong>{system_name}</strong></p>
            <p style=\"margin: 5px 0; color: #666;\">📧 {system_email}</p>
            <p style=\"margin: 5px 0; color: #666;\">📞 {system_contact}</p>
        </div>
    </div>
    </body></html>',
    
    'Dear {tenant_name},

This is an urgent reminder that your rent payment for {house_no} was due on {due_date} and is now {days_overdue} days overdue.

Overdue Payment Details:
- Property: {house_no}
- Amount Due: \${rent_amount}
- Original Due Date: {due_date}
- Days Overdue: {days_overdue}
- Late Fees May Apply

Please submit your payment immediately to avoid further late fees and potential legal action.

If you are experiencing financial difficulties, please contact us immediately to discuss payment arrangements.

Thank you for your immediate attention to this matter.

{system_name}
Email: {system_email}
Phone: {system_contact}',
    
    '[\"tenant_name\", \"house_no\", \"rent_amount\", \"due_date\", \"days_overdue\", \"system_name\", \"system_email\", \"system_contact\"]')";
    
    if ($conn->query($payment_reminder_template)) {
        echo "<p style='color: green;'>✅ Payment Reminder template inserted successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error inserting Payment Reminder template: " . $conn->error . "</p>";
    }
    
    // Insert payment confirmation template
    $payment_confirmation_template = "INSERT INTO `email_templates` (`template_name`, `template_type`, `subject`, `body_html`, `body_text`, `variables`) VALUES
    ('Payment Confirmation', 'payment_confirmation', 'Payment Received - {house_no}', 
    '<html><body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
    <div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">
        <h2 style=\"color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;\">
            ✅ Payment Received
        </h2>
        
        <p>Dear {tenant_name},</p>
        
        <p>Thank you! We have successfully received your rent payment for <strong>{house_no}</strong>.</p>
        
        <div style=\"background: #d4edda; padding: 15px; border-left: 4px solid #27ae60; margin: 20px 0;\">
            <h3 style=\"margin-top: 0; color: #155724;\">✅ Payment Confirmation:</h3>
            <ul style=\"margin: 10px 0;\">
                <li><strong>Property:</strong> {house_no}</li>
                <li><strong>Amount Paid:</strong> \${payment_amount}</li>
                <li><strong>Payment Date:</strong> {payment_date}</li>
                <li><strong>Invoice Number:</strong> {invoice_number}</li>
                <li><strong>Reference:</strong> {reference_number}</li>
            </ul>
        </div>
        
        <p>Your payment has been recorded in our system. Please keep this email as your receipt.</p>
        
        <p>Thank you for your prompt payment!</p>
        
        <div style=\"margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;\">
            <p style=\"margin: 0;\"><strong>{system_name}</strong></p>
            <p style=\"margin: 5px 0; color: #666;\">📧 {system_email}</p>
            <p style=\"margin: 5px 0; color: #666;\">📞 {system_contact}</p>
        </div>
    </div>
    </body></html>',
    
    'Dear {tenant_name},

Thank you! We have successfully received your rent payment for {house_no}.

Payment Confirmation:
- Property: {house_no}
- Amount Paid: \${payment_amount}
- Payment Date: {payment_date}
- Invoice Number: {invoice_number}
- Reference: {reference_number}

Your payment has been recorded in our system. Please keep this email as your receipt.

Thank you for your prompt payment!

{system_name}
Email: {system_email}
Phone: {system_contact}',
    
    '[\"tenant_name\", \"house_no\", \"payment_amount\", \"payment_date\", \"invoice_number\", \"reference_number\", \"system_name\", \"system_email\", \"system_contact\"]')";
    
    if ($conn->query($payment_confirmation_template)) {
        echo "<p style='color: green;'>✅ Payment Confirmation template inserted successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error inserting Payment Confirmation template: " . $conn->error . "</p>";
    }
}

echo "<h2>✅ Database Setup Complete!</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
echo "<h3>📊 Summary:</h3>";
echo "<ul>";
echo "<li>✅ Email configuration columns added to system_settings</li>";
echo "<li>✅ email_logs table created for tracking sent emails</li>";
echo "<li>✅ email_templates table created with default templates</li>";
echo "<li>✅ 3 default email templates inserted (rent due, payment reminder, payment confirmation)</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔗 Next Steps:</h3>";
echo "<p><a href='email_settings.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📧 Configure Email Settings</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #2c3e50; }
p { margin: 10px 0; }
</style>
