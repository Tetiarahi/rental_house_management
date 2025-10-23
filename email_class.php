<?php
class EmailManager {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $this->getEmailSettings();
    }
    
    /**
     * Get email settings from database
     */
    public function getEmailSettings() {
        $query = "SELECT * FROM system_settings LIMIT 1";
        $result = $this->db->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return [];
    }
    
    /**
     * Send email using proper SMTP authentication
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
            
            // Use custom SMTP implementation
            $result = $this->sendWithCustomSMTP($to, $subject, $body_html, $body_text);
            
            if ($result['success']) {
                $this->updateEmailLog($log_id, 'sent', null);
                return true;
            } else {
                $this->updateEmailLog($log_id, 'failed', $result['error']);
                return false;
            }
            
        } catch (Exception $e) {
            $this->logEmail($tenant_id, $to, $subject, $email_type, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using custom SMTP implementation
     */
    private function sendWithCustomSMTP($to, $subject, $body_html, $body_text) {
        try {
            $smtp_host = $this->settings['smtp_host'];
            $smtp_port = $this->settings['smtp_port'] ?? 587;
            $smtp_username = $this->settings['smtp_username'];
            $smtp_password = $this->settings['smtp_password'];
            $smtp_encryption = $this->settings['smtp_encryption'] ?? 'tls';
            $from_name = $this->settings['email_from_name'] ?? 'Rental Management System';
            
            // Create socket connection
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            if ($smtp_encryption == 'ssl') {
                $smtp_host = 'ssl://' . $smtp_host;
                $smtp_port = $smtp_port == 587 ? 465 : $smtp_port;
            }
            
            $socket = stream_socket_client("$smtp_host:$smtp_port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            
            if (!$socket) {
                return ['success' => false, 'error' => "Failed to connect to SMTP server: $errstr ($errno)"];
            }
            
            // Set timeout
            stream_set_timeout($socket, 30);
            
            // Read initial response
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '220')) {
                fclose($socket);
                return ['success' => false, 'error' => "SMTP server not ready: $response"];
            }
            
            // EHLO
            fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
            $response = $this->readResponse($socket);
            
            // Start TLS if required
            if ($smtp_encryption == 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $response = $this->readResponse($socket);
                if (!$this->checkResponse($response, '220')) {
                    fclose($socket);
                    return ['success' => false, 'error' => "STARTTLS failed: $response"];
                }
                
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'error' => "Failed to enable TLS encryption"];
                }
                
                // EHLO again after TLS
                fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
                $response = $this->readResponse($socket);
            }
            
            // AUTH LOGIN
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '334')) {
                fclose($socket);
                return ['success' => false, 'error' => "AUTH LOGIN failed: $response"];
            }
            
            // Send username
            fwrite($socket, base64_encode($smtp_username) . "\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '334')) {
                fclose($socket);
                return ['success' => false, 'error' => "Username authentication failed: $response"];
            }
            
            // Send password
            fwrite($socket, base64_encode($smtp_password) . "\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '235')) {
                fclose($socket);
                return ['success' => false, 'error' => "Password authentication failed: $response"];
            }
            
            // MAIL FROM
            fwrite($socket, "MAIL FROM: <$smtp_username>\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '250')) {
                fclose($socket);
                return ['success' => false, 'error' => "MAIL FROM failed: $response"];
            }
            
            // RCPT TO
            fwrite($socket, "RCPT TO: <$to>\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '250')) {
                fclose($socket);
                return ['success' => false, 'error' => "RCPT TO failed: $response"];
            }
            
            // DATA
            fwrite($socket, "DATA\r\n");
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '354')) {
                fclose($socket);
                return ['success' => false, 'error' => "DATA command failed: $response"];
            }
            
            // Email headers and body
            $boundary = md5(time());
            $email_data = "From: $from_name <$smtp_username>\r\n";
            $email_data .= "To: $to\r\n";
            $email_data .= "Subject: $subject\r\n";
            $email_data .= "MIME-Version: 1.0\r\n";
            $email_data .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
            $email_data .= "\r\n";
            
            // Text part
            if (!empty($body_text)) {
                $email_data .= "--$boundary\r\n";
                $email_data .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $email_data .= "Content-Transfer-Encoding: 8bit\r\n";
                $email_data .= "\r\n";
                $email_data .= $body_text . "\r\n";
            }
            
            // HTML part
            if (!empty($body_html)) {
                $email_data .= "--$boundary\r\n";
                $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
                $email_data .= "Content-Transfer-Encoding: 8bit\r\n";
                $email_data .= "\r\n";
                $email_data .= $body_html . "\r\n";
            }
            
            $email_data .= "--$boundary--\r\n";
            $email_data .= "\r\n.\r\n";
            
            fwrite($socket, $email_data);
            $response = $this->readResponse($socket);
            if (!$this->checkResponse($response, '250')) {
                fclose($socket);
                return ['success' => false, 'error' => "Email sending failed: $response"];
            }
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return ['success' => true, 'error' => null];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'SMTP Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Read response from SMTP server
     */
    private function readResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return trim($response);
    }
    
    /**
     * Check if SMTP response is successful
     */
    private function checkResponse($response, $expected_code) {
        return substr($response, 0, 3) == $expected_code;
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
    public function replaceTemplateVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
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
        if ($log_id) {
            $query = "UPDATE email_logs SET status = ?, error_message = ?, sent_date = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssi", $status, $error_message, $log_id);
            $stmt->execute();
        }
    }
    
    /**
     * Send rent due notice to a tenant
     */
    public function sendRentDueNotice($tenant_id) {
        global $conn;

        // Get tenant information
        $tenant_query = "SELECT t.*, h.house_no, h.price
                        FROM tenants t
                        INNER JOIN houses h ON h.id = t.house_id
                        WHERE t.id = ? AND t.status = 1";
        $stmt = $conn->prepare($tenant_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows == 0) {
            $this->logEmail($tenant_id, '', 'Rent Due Notice', 'rent_due', 'failed', 'Tenant not found or inactive');
            return false;
        }

        $tenant = $result->fetch_assoc();

        if (empty($tenant['email'])) {
            $this->logEmail($tenant_id, '', 'Rent Due Notice', 'rent_due', 'failed', 'Tenant email address is empty');
            return false;
        }

        // Calculate due date
        $registration_date = new DateTime($tenant['date_in']);
        $current_date = new DateTime();

        // Calculate next due date
        $due_date = clone $registration_date;
        while ($due_date <= $current_date) {
            $due_date->add(new DateInterval('P1M'));
        }

        $days_until_due = $current_date->diff($due_date)->days;

        // Get email template
        $template_query = "SELECT * FROM email_templates WHERE template_type = 'rent_due' LIMIT 1";
        $template_result = $conn->query($template_query);

        if (!$template_result || $template_result->num_rows == 0) {
            $this->logEmail($tenant_id, $tenant['email'], 'Rent Due Notice', 'rent_due', 'failed', 'Email template not found');
            return false;
        }

        $template = $template_result->fetch_assoc();

        // Replace variables in template
        $variables = [
            '{tenant_name}' => $tenant['firstname'] . ' ' . $tenant['lastname'],
            '{house_no}' => $tenant['house_no'],
            '{rent_amount}' => number_format($tenant['price'], 2),
            '{due_date}' => $due_date->format('F d, Y'),
            '{days_until_due}' => $days_until_due,
            '{system_name}' => $this->settings['email_from_name'] ?? 'Rental Management System',
            '{system_email}' => $this->settings['smtp_username'] ?? '',
            '{system_contact}' => $this->settings['system_contact'] ?? ''
        ];

        $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
        $body_html = str_replace(array_keys($variables), array_values($variables), $template['body_html']);
        $body_text = str_replace(array_keys($variables), array_values($variables), $template['body_text']);

        // Generate PDF invoice attachment
        require_once 'invoice_generator.php';
        $invoiceGenerator = new InvoiceGenerator();
        $invoice_data = $invoiceGenerator->generateInvoiceForTenant($tenant_id, $due_date);

        // Create PDF file from HTML
        $pdf_content = $this->generatePDFFromHTML($invoice_data['html'], $invoice_data['invoice_number']);

        // Add note about attachment to email body
        $body_html .= "<hr><h3>📄 Invoice Attached</h3><p>Please find your rent invoice attached as a PDF file.</p>";
        $body_text .= "\n\n--- INVOICE ATTACHED ---\nPlease find your rent invoice attached as a PDF file.";

        return $this->sendEmailWithAttachment($tenant['email'], $subject, $body_html, $body_text, 'rent_due', $tenant_id, $pdf_content, $invoice_data['invoice_number'] . '.pdf');
    }

    /**
     * Send payment reminder to a tenant
     */
    public function sendPaymentReminder($tenant_id) {
        global $conn;

        // Get tenant information
        $tenant_query = "SELECT t.*, h.house_no, h.price
                        FROM tenants t
                        INNER JOIN houses h ON h.id = t.house_id
                        WHERE t.id = ? AND t.status = 1";
        $stmt = $conn->prepare($tenant_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows == 0) {
            $this->logEmail($tenant_id, '', 'Payment Reminder', 'payment_reminder', 'failed', 'Tenant not found or inactive');
            return false;
        }

        $tenant = $result->fetch_assoc();

        if (empty($tenant['email'])) {
            $this->logEmail($tenant_id, '', 'Payment Reminder', 'payment_reminder', 'failed', 'Tenant email address is empty');
            return false;
        }

        // Calculate overdue information
        $registration_date = new DateTime($tenant['date_in']);
        $current_date = new DateTime();

        // Find the most recent due date that has passed
        $due_date = clone $registration_date;
        while ($due_date <= $current_date) {
            $last_due_date = clone $due_date;
            $due_date->add(new DateInterval('P1M'));
        }

        $days_overdue = $current_date->diff($last_due_date)->days;

        // Get email template
        $template_query = "SELECT * FROM email_templates WHERE template_type = 'payment_reminder' LIMIT 1";
        $template_result = $conn->query($template_query);

        if (!$template_result || $template_result->num_rows == 0) {
            $this->logEmail($tenant_id, $tenant['email'], 'Payment Reminder', 'payment_reminder', 'failed', 'Email template not found');
            return false;
        }

        $template = $template_result->fetch_assoc();

        // Replace variables in template
        $variables = [
            '{tenant_name}' => $tenant['firstname'] . ' ' . $tenant['lastname'],
            '{house_no}' => $tenant['house_no'],
            '{rent_amount}' => number_format($tenant['price'], 2),
            '{due_date}' => $last_due_date->format('F d, Y'),
            '{days_overdue}' => $days_overdue,
            '{system_name}' => $this->settings['email_from_name'] ?? 'Rental Management System',
            '{system_email}' => $this->settings['smtp_username'] ?? '',
            '{system_contact}' => $this->settings['system_contact'] ?? ''
        ];

        $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
        $body_html = str_replace(array_keys($variables), array_values($variables), $template['body_html']);
        $body_text = str_replace(array_keys($variables), array_values($variables), $template['body_text']);

        // Generate PDF invoice attachment for overdue payment
        require_once 'invoice_generator.php';
        $invoiceGenerator = new InvoiceGenerator();
        $invoice_data = $invoiceGenerator->generateInvoiceForTenant($tenant_id);

        // Create PDF file from HTML
        $pdf_content = $this->generatePDFFromHTML($invoice_data['html'], $invoice_data['invoice_number']);

        // Add note about attachment to email body
        $body_html .= "<hr><h3>📄 Outstanding Invoice Attached</h3><p>Please find your outstanding invoice attached as a PDF file.</p>";
        $body_text .= "\n\n--- OUTSTANDING INVOICE ATTACHED ---\nPlease find your outstanding invoice attached as a PDF file.";

        return $this->sendEmailWithAttachment($tenant['email'], $subject, $body_html, $body_text, 'payment_reminder', $tenant_id, $pdf_content, $invoice_data['invoice_number'] . '.pdf');
    }

    /**
     * Send payment confirmation to a tenant
     */
    public function sendPaymentConfirmation($payment_id) {
        global $conn;

        // Get payment and tenant information
        $payment_query = "SELECT p.*, t.firstname, t.lastname, t.email, h.house_no
                         FROM payments p
                         INNER JOIN tenants t ON t.id = p.tenant_id
                         INNER JOIN houses h ON h.id = t.house_id
                         WHERE p.id = ?";
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows == 0) {
            $this->logEmail(null, '', 'Payment Confirmation', 'payment_confirmation', 'failed', 'Payment not found');
            return false;
        }

        $payment = $result->fetch_assoc();

        if (empty($payment['email'])) {
            $this->logEmail($payment['tenant_id'], '', 'Payment Confirmation', 'payment_confirmation', 'failed', 'Tenant email address is empty');
            return false;
        }

        // Get email template
        $template_query = "SELECT * FROM email_templates WHERE template_type = 'payment_confirmation' LIMIT 1";
        $template_result = $conn->query($template_query);

        if (!$template_result || $template_result->num_rows == 0) {
            $this->logEmail($payment['tenant_id'], $payment['email'], 'Payment Confirmation', 'payment_confirmation', 'failed', 'Email template not found');
            return false;
        }

        $template = $template_result->fetch_assoc();

        // Replace variables in template
        $variables = [
            '{tenant_name}' => $payment['firstname'] . ' ' . $payment['lastname'],
            '{house_no}' => $payment['house_no'],
            '{payment_amount}' => number_format($payment['amount'], 2),
            '{payment_date}' => date('F d, Y', strtotime($payment['date_created'])),
            '{payment_reference}' => $payment['reference_number'] ?? 'N/A',
            '{system_name}' => $this->settings['email_from_name'] ?? 'Rental Management System',
            '{system_email}' => $this->settings['smtp_username'] ?? '',
            '{system_contact}' => $this->settings['system_contact'] ?? ''
        ];

        $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
        $body_html = str_replace(array_keys($variables), array_values($variables), $template['body_html']);
        $body_text = str_replace(array_keys($variables), array_values($variables), $template['body_text']);

        return $this->sendEmail($payment['email'], $subject, $body_html, $body_text, 'payment_confirmation', $payment['tenant_id']);
    }

    /**
     * Send test email
     */
    public function sendTestEmail($test_email) {
        $subject = "✅ Test Email from Rental Management System";
        $body_html = "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;'>
                <h2 style='color: #28a745; margin-top: 0;'>🎉 Email Configuration Successful!</h2>
                <p style='font-size: 16px; line-height: 1.6;'>Congratulations! Your SMTP email configuration is working correctly.</p>

                <div style='background: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>📊 Configuration Details:</h3>
                    <ul style='line-height: 1.8;'>
                        <li><strong>SMTP Host:</strong> {$this->settings['smtp_host']}</li>
                        <li><strong>SMTP Port:</strong> {$this->settings['smtp_port']}</li>
                        <li><strong>Encryption:</strong> {$this->settings['smtp_encryption']}</li>
                        <li><strong>Test Date:</strong> " . date('F d, Y H:i:s') . "</li>
                    </ul>
                </div>

                <p style='color: #6c757d; font-size: 14px; margin-bottom: 0;'>
                    Your rental management system is now ready to send automated email notifications!
                </p>
            </div>
        </body>
        </html>";

        $body_text = "Email Configuration Test\n\nCongratulations! Your SMTP email configuration is working correctly.\n\nConfiguration Details:\n- SMTP Host: {$this->settings['smtp_host']}\n- SMTP Port: {$this->settings['smtp_port']}\n- Encryption: {$this->settings['smtp_encryption']}\n- Test Date: " . date('F d, Y H:i:s') . "\n\nYour rental management system is now ready to send automated email notifications!";

        return $this->sendEmail($test_email, $subject, $body_html, $body_text, 'test');
    }

    /**
     * Validate SMTP settings
     */
    private function validateSettings() {
        return !empty($this->settings['smtp_host']) &&
               !empty($this->settings['smtp_port']) &&
               !empty($this->settings['smtp_username']) &&
               !empty($this->settings['smtp_password']);
    }

    /**
     * Generate PDF from HTML content
     */
    private function generatePDFFromHTML($html_content, $filename) {
        // Simple HTML to PDF conversion using browser print functionality
        // This creates a print-ready HTML that can be saved as PDF
        $pdf_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $filename . '</title>
            <style>
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .invoice-container {
                    max-width: 800px;
                    margin: 0 auto;
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                ' . $html_content . '
            </div>
        </body>
        </html>';

        return $pdf_html;
    }

    /**
     * Send email with PDF attachment
     */
    public function sendEmailWithAttachment($to, $subject, $body_html, $body_text, $type, $tenant_id, $pdf_content, $pdf_filename) {
        if (!$this->validateSettings()) {
            $this->logEmail($tenant_id, $to, $subject, $type, 'failed', 'SMTP settings not configured');
            return false;
        }

        try {
            // Create socket connection
            $socket = fsockopen($this->settings['smtp_host'], $this->settings['smtp_port'], $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
            }

            // Read server greeting
            $this->readResponse($socket);

            // Send EHLO
            fwrite($socket, "EHLO " . $this->settings['smtp_host'] . "\r\n");
            $this->readResponse($socket);

            // Start TLS if required
            if ($this->settings['smtp_port'] == 587) {
                fwrite($socket, "STARTTLS\r\n");
                $this->readResponse($socket);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to enable TLS encryption");
                }

                // Send EHLO again after TLS
                fwrite($socket, "EHLO " . $this->settings['smtp_host'] . "\r\n");
                $this->readResponse($socket);
            }

            // Authenticate
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->readResponse($socket);

            fwrite($socket, base64_encode($this->settings['smtp_username']) . "\r\n");
            $this->readResponse($socket);

            fwrite($socket, base64_encode($this->settings['smtp_password']) . "\r\n");
            $this->readResponse($socket);

            // Send email
            fwrite($socket, "MAIL FROM: <" . $this->settings['smtp_username'] . ">\r\n");
            $this->readResponse($socket);

            fwrite($socket, "RCPT TO: <$to>\r\n");
            $this->readResponse($socket);

            fwrite($socket, "DATA\r\n");
            $this->readResponse($socket);

            // Create multipart email with attachment
            $boundary = "----=_Part_" . md5(time());

            $headers = "From: " . $this->settings['email_from_name'] . " <" . $this->settings['smtp_username'] . ">\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            $headers .= "\r\n";

            // Email body
            $message = "--$boundary\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n\r\n";

            // Text version
            $message .= "--alt-$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $body_text . "\r\n\r\n";

            // HTML version
            $message .= "--alt-$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $body_html . "\r\n\r\n";

            $message .= "--alt-$boundary--\r\n\r\n";

            // PDF attachment
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; name=\"$pdf_filename\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$pdf_filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($pdf_content)) . "\r\n";

            $message .= "--$boundary--\r\n";

            // Send the complete message
            fwrite($socket, $headers . $message . "\r\n.\r\n");
            $this->readResponse($socket);

            // Quit
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            $this->logEmail($tenant_id, $to, $subject, $type, 'sent', 'Email with PDF attachment sent successfully');
            return true;

        } catch (Exception $e) {
            $this->logEmail($tenant_id, $to, $subject, $type, 'failed', $e->getMessage());
            return false;
        }
    }
}
