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
}
?>
