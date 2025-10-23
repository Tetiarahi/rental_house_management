<?php
require_once 'db_connect.php';

class EmailManager {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->loadSettings();
    }
    
    private function loadSettings() {
        $query = "SELECT * FROM system_settings LIMIT 1";
        $result = $this->db->query($query);
        if ($result && $result->num_rows > 0) {
            $this->settings = $result->fetch_assoc();
        } else {
            $this->settings = [];
        }
    }
    
    /**
     * Send email using PHP's built-in mail function with SMTP
     */
    public function sendEmail($to, $subject, $body_html, $body_text = '', $email_type = 'general', $tenant_id = null) {
        // Check if email notifications are enabled
        if (!isset($this->settings['email_notifications_enabled']) || !$this->settings['email_notifications_enabled']) {
            $this->logEmail($tenant_id, $to, $subject, $email_type, 'disabled', 'Email notifications are disabled');
            return false;
        }
        
        // Validate required settings
        if (empty($this->settings['smtp_username']) || empty($this->settings['smtp_password'])) {
            $this->logEmail($tenant_id, $to, $subject, $email_type, 'failed', 'SMTP credentials not configured');
            return false;
        }
        
        try {
            // Log email attempt
            $log_id = $this->logEmail($tenant_id, $to, $subject, $email_type, 'pending');
            
            // Prepare headers
            $from_name = $this->settings['email_from_name'] ?? 'Rental Management System';
            $from_email = $this->settings['smtp_username'];
            
            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "From: {$from_name} <{$from_email}>";
            $headers[] = "Reply-To: {$from_email}";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            // Use HTML body if available, otherwise text
            $email_body = !empty($body_html) ? $body_html : nl2br($body_text);
            
            // Send email
            $success = mail($to, $subject, $email_body, implode("\r\n", $headers));
            
            if ($success) {
                $this->updateEmailLog($log_id, 'sent', null);
                return true;
            } else {
                $this->updateEmailLog($log_id, 'failed', 'Mail function returned false');
                return false;
            }
            
        } catch (Exception $e) {
            $this->updateEmailLog($log_id ?? null, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using PHPMailer (if available) for better SMTP support
     */
    public function sendEmailSMTP($to, $subject, $body_html, $body_text = '', $email_type = 'general', $tenant_id = null) {
        // For now, fall back to basic mail function
        // TODO: Implement PHPMailer integration
        return $this->sendEmail($to, $subject, $body_html, $body_text, $email_type, $tenant_id);
    }
    
    /**
     * Get email template by type
     */
    public function getTemplate($template_type) {
        $query = "SELECT * FROM email_templates WHERE template_type = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $template_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Replace template variables with actual values
     */
    public function replaceTemplateVariables($template_content, $variables) {
        foreach ($variables as $key => $value) {
            $template_content = str_replace('{' . $key . '}', $value, $template_content);
        }
        return $template_content;
    }
    
    /**
     * Send rent due notice to tenant
     */
    public function sendRentDueNotice($tenant_id) {
        // Get tenant information
        $tenant_query = "SELECT t.*, h.house_no, h.price 
                        FROM tenants t 
                        INNER JOIN houses h ON h.id = t.house_id 
                        WHERE t.id = ? AND t.status = 1";
        $stmt = $this->db->prepare($tenant_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $tenant_result = $stmt->get_result();
        
        if (!$tenant_result || $tenant_result->num_rows == 0) {
            return false;
        }
        
        $tenant = $tenant_result->fetch_assoc();
        
        // Calculate due date (assuming monthly rent due on same day as registration)
        $registration_date = new DateTime($tenant['date_in']);
        $current_date = new DateTime();
        
        // Calculate next due date
        $due_day = $registration_date->format('d');
        $due_date = new DateTime($current_date->format('Y-m-') . $due_day);
        
        // If due date has passed this month, set for next month
        if ($due_date <= $current_date) {
            $due_date->modify('+1 month');
        }
        
        $days_until_due = $current_date->diff($due_date)->days;
        
        // Get template
        $template = $this->getTemplate('rent_due');
        if (!$template) {
            return false;
        }
        
        // Prepare variables
        $variables = [
            'tenant_name' => trim($tenant['firstname'] . ' ' . $tenant['lastname']),
            'house_no' => $tenant['house_no'],
            'rent_amount' => number_format($tenant['price'], 2),
            'due_date' => $due_date->format('F d, Y'),
            'days_until_due' => $days_until_due,
            'system_name' => $this->settings['name'] ?? 'Rental Management System',
            'system_email' => $this->settings['email'] ?? '',
            'system_contact' => $this->settings['contact'] ?? ''
        ];
        
        // Replace variables in template
        $subject = $this->replaceTemplateVariables($template['subject'], $variables);
        $body_html = $this->replaceTemplateVariables($template['body_html'], $variables);
        $body_text = $this->replaceTemplateVariables($template['body_text'], $variables);
        
        // Send email
        return $this->sendEmail($tenant['email'], $subject, $body_html, $body_text, 'rent_due', $tenant_id);
    }
    
    /**
     * Send payment reminder to tenant
     */
    public function sendPaymentReminder($tenant_id) {
        // Get tenant information
        $tenant_query = "SELECT t.*, h.house_no, h.price 
                        FROM tenants t 
                        INNER JOIN houses h ON h.id = t.house_id 
                        WHERE t.id = ? AND t.status = 1";
        $stmt = $this->db->prepare($tenant_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $tenant_result = $stmt->get_result();
        
        if (!$tenant_result || $tenant_result->num_rows == 0) {
            return false;
        }
        
        $tenant = $tenant_result->fetch_assoc();
        
        // Calculate overdue information
        $registration_date = new DateTime($tenant['date_in']);
        $current_date = new DateTime();
        
        // Calculate last due date
        $due_day = $registration_date->format('d');
        $last_due_date = new DateTime($current_date->format('Y-m-') . $due_day);
        
        // If current day is before due day, last due was previous month
        if ($current_date->format('d') < $due_day) {
            $last_due_date->modify('-1 month');
        }
        
        $days_overdue = $last_due_date->diff($current_date)->days;
        
        // Get template
        $template = $this->getTemplate('payment_reminder');
        if (!$template) {
            return false;
        }
        
        // Prepare variables
        $variables = [
            'tenant_name' => trim($tenant['firstname'] . ' ' . $tenant['lastname']),
            'house_no' => $tenant['house_no'],
            'rent_amount' => number_format($tenant['price'], 2),
            'due_date' => $last_due_date->format('F d, Y'),
            'days_overdue' => $days_overdue,
            'system_name' => $this->settings['name'] ?? 'Rental Management System',
            'system_email' => $this->settings['email'] ?? '',
            'system_contact' => $this->settings['contact'] ?? ''
        ];
        
        // Replace variables in template
        $subject = $this->replaceTemplateVariables($template['subject'], $variables);
        $body_html = $this->replaceTemplateVariables($template['body_html'], $variables);
        $body_text = $this->replaceTemplateVariables($template['body_text'], $variables);
        
        // Send email
        return $this->sendEmail($tenant['email'], $subject, $body_html, $body_text, 'payment_reminder', $tenant_id);
    }
    
    /**
     * Send payment confirmation to tenant
     */
    public function sendPaymentConfirmation($payment_id) {
        // Get payment and tenant information
        $payment_query = "SELECT p.*, t.firstname, t.lastname, t.email, h.house_no 
                         FROM payments p 
                         INNER JOIN tenants t ON t.id = p.tenant_id 
                         INNER JOIN houses h ON h.id = t.house_id 
                         WHERE p.id = ?";
        $stmt = $this->db->prepare($payment_query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        
        if (!$payment_result || $payment_result->num_rows == 0) {
            return false;
        }
        
        $payment = $payment_result->fetch_assoc();
        
        // Get template
        $template = $this->getTemplate('payment_confirmation');
        if (!$template) {
            return false;
        }
        
        // Prepare variables
        $variables = [
            'tenant_name' => trim($payment['firstname'] . ' ' . $payment['lastname']),
            'house_no' => $payment['house_no'],
            'payment_amount' => number_format($payment['amount'], 2),
            'payment_date' => date('F d, Y', strtotime($payment['date_created'])),
            'invoice_number' => $payment['invoice'],
            'reference_number' => $payment['ref_number'] ?? 'N/A',
            'system_name' => $this->settings['name'] ?? 'Rental Management System',
            'system_email' => $this->settings['email'] ?? '',
            'system_contact' => $this->settings['contact'] ?? ''
        ];
        
        // Replace variables in template
        $subject = $this->replaceTemplateVariables($template['subject'], $variables);
        $body_html = $this->replaceTemplateVariables($template['body_html'], $variables);
        $body_text = $this->replaceTemplateVariables($template['body_text'], $variables);
        
        // Send email
        return $this->sendEmail($payment['email'], $subject, $body_html, $body_text, 'payment_confirmation', $payment['tenant_id']);
    }
    
    /**
     * Log email to database
     */
    private function logEmail($tenant_id, $email_to, $subject, $email_type, $status, $error_message = null) {
        $query = "INSERT INTO email_logs (tenant_id, email_to, email_subject, email_type, status, error_message) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isssss", $tenant_id, $email_to, $subject, $email_type, $status, $error_message);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    /**
     * Update email log status
     */
    private function updateEmailLog($log_id, $status, $error_message = null) {
        if (!$log_id) return;
        
        $sent_date = ($status == 'sent') ? date('Y-m-d H:i:s') : null;
        
        $query = "UPDATE email_logs SET status = ?, error_message = ?, sent_date = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssi", $status, $error_message, $sent_date, $log_id);
        $stmt->execute();
    }
    
    /**
     * Send test email
     */
    public function sendTestEmail($test_email) {
        $subject = "Test Email from Rental Management System";
        $body_html = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #2c3e50;'>✅ Email Configuration Test</h2>
            <p>Congratulations! Your email configuration is working correctly.</p>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>Sent at: " . date('F d, Y H:i:s') . "</li>
                <li>From: " . ($this->settings['email_from_name'] ?? 'Rental Management System') . "</li>
                <li>SMTP Host: " . ($this->settings['smtp_host'] ?? 'Not configured') . "</li>
            </ul>
            <p>You can now send rent due notices and payment reminders to your tenants!</p>
        </body>
        </html>";
        
        $body_text = "Email Configuration Test\n\nCongratulations! Your email configuration is working correctly.\n\nSent at: " . date('F d, Y H:i:s');
        
        return $this->sendEmail($test_email, $subject, $body_html, $body_text, 'test');
    }
}
?>
