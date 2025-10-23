<?php
require_once 'db_connect.php';
require_once 'email_class.php';

class InvoiceGenerator {
    private $conn;
    private $settings;
    private $emailManager;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
        $this->emailManager = new EmailManager();
    }
    
    private function loadSettings() {
        $query = "SELECT * FROM system_settings LIMIT 1";
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->settings = [
                'name' => isset($settings['name']) ? $settings['name'] : 'BPA Rental Management',
                'email' => isset($settings['email']) ? $settings['email'] : 'info@bpa.com',
                'contact' => isset($settings['contact']) ? $settings['contact'] : '+1-234-567-8900',
                'address' => isset($settings['address']) ? $settings['address'] : 'BPA Building, Main Street, City'
            ];
        } else {
            $this->settings = [
                'name' => 'BPA Rental Management',
                'email' => 'info@bpa.com',
                'contact' => '+1-234-567-8900',
                'address' => 'BPA Building, Main Street, City'
            ];
        }
    }
    
    public function generateInvoiceForTenant($tenant_id, $due_date = null) {
        // Get tenant and house information
        $query = "SELECT 
                    t.*,
                    h.house_no,
                    h.price as monthly_rent,
                    h.description as house_description,
                    CONCAT(t.firstname, ' ', t.lastname) as full_name
                  FROM tenants t
                  INNER JOIN houses h ON h.id = t.house_id
                  WHERE t.id = ? AND t.status = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Tenant not found or inactive");
        }
        
        $tenant = $result->fetch_assoc();
        
        // Calculate due date if not provided
        if (!$due_date) {
            $due_date = $this->calculateNextDueDate($tenant);
        }
        
        // Calculate outstanding balance
        $outstanding = $this->calculateOutstandingBalance($tenant_id);
        
        // Generate invoice HTML
        $html = $this->generateInvoiceHTML($tenant, $due_date, $outstanding);
        
        return [
            'html' => $html,
            'tenant' => $tenant,
            'due_date' => $due_date,
            'outstanding' => $outstanding,
            'invoice_number' => $this->generateInvoiceNumber($tenant_id)
        ];
    }
    
    private function calculateNextDueDate($tenant) {
        // Calculate next due date based on tenant registration date
        $registration_date = new DateTime($tenant['date_in']);
        $current_date = new DateTime();
        
        $due_date = clone $registration_date;
        
        // Find the next due date
        while ($due_date <= $current_date) {
            $due_date->add(new DateInterval('P1M'));
        }
        
        return $due_date;
    }
    
    private function calculateOutstandingBalance($tenant_id) {
        // Get total payments made
        $payments_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE tenant_id = ?";
        $stmt = $this->conn->prepare($payments_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $payments_result = $stmt->get_result();
        $total_paid = $payments_result->fetch_assoc()['total_paid'];
        
        // Get tenant info for calculation
        $tenant_query = "SELECT t.date_in, h.price FROM tenants t INNER JOIN houses h ON h.id = t.house_id WHERE t.id = ?";
        $stmt = $this->conn->prepare($tenant_query);
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $tenant_result = $stmt->get_result();
        $tenant_info = $tenant_result->fetch_assoc();
        
        // Calculate months since registration
        $start_date = new DateTime($tenant_info['date_in']);
        $current_date = new DateTime();
        $interval = $start_date->diff($current_date);
        $months = ($interval->y * 12) + $interval->m + 1; // +1 for current month
        
        $total_due = $months * $tenant_info['price'];
        $outstanding = $total_due - $total_paid;
        
        return max(0, $outstanding); // Don't return negative values
    }
    
    private function generateInvoiceNumber($tenant_id) {
        return "BPA-INV-" . date('Y') . "-" . str_pad($tenant_id, 4, '0', STR_PAD_LEFT) . "-" . date('m');
    }
    
    private function generateInvoiceHTML($tenant, $due_date, $outstanding) {
        $invoice_number = $this->generateInvoiceNumber($tenant['id']);
        $invoice_date = date('F d, Y');
        $due_date_formatted = $due_date->format('F d, Y');
        $days_until_due = $due_date->diff(new DateTime())->days;
        
        // Determine urgency level
        $urgency_class = '';
        $urgency_message = '';
        if ($days_until_due <= 3) {
            $urgency_class = 'urgent';
            $urgency_message = '⚠️ URGENT: Payment due in ' . $days_until_due . ' days!';
        } elseif ($days_until_due <= 7) {
            $urgency_class = 'warning';
            $urgency_message = '⏰ Payment due in ' . $days_until_due . ' days';
        } else {
            $urgency_class = 'normal';
            $urgency_message = '📅 Payment due in ' . $days_until_due . ' days';
        }
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Rent Invoice - {$invoice_number}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                    line-height: 1.4;
                }
                .header {
                    border-bottom: 3px solid #dc3545;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                    display: flex;
                    align-items: center;
                }
                .logo-section {
                    width: 120px;
                    height: 120px;
                    background: linear-gradient(135deg, #dc3545, #c82333);
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 30px;
                }
                .company-info {
                    flex: 1;
                }
                .company-name {
                    font-size: 28px;
                    font-weight: bold;
                    color: #dc3545;
                    margin: 0 0 10px 0;
                }
                .company-details {
                    color: #666;
                    font-size: 14px;
                    line-height: 1.6;
                }
                .invoice-title {
                    text-align: center;
                    background: #f8f9fa;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                    border-left: 5px solid #dc3545;
                }
                .invoice-title h1 {
                    margin: 0;
                    color: #dc3545;
                    font-size: 24px;
                }
                .invoice-number {
                    color: #666;
                    font-size: 16px;
                    margin-top: 5px;
                }
                .urgency-banner {
                    text-align: center;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                    font-weight: bold;
                    font-size: 18px;
                }
                .urgency-banner.urgent {
                    background: #f8d7da;
                    border: 2px solid #dc3545;
                    color: #721c24;
                }
                .urgency-banner.warning {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    color: #856404;
                }
                .urgency-banner.normal {
                    background: #d1ecf1;
                    border: 2px solid #17a2b8;
                    color: #0c5460;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin: 30px 0;
                }
                .info-section {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #dc3545;
                }
                .info-section h3 {
                    margin: 0 0 15px 0;
                    color: #dc3545;
                    font-size: 16px;
                    border-bottom: 1px solid #dee2e6;
                    padding-bottom: 8px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 8px 0;
                    padding: 5px 0;
                }
                .info-label {
                    font-weight: 600;
                    color: #495057;
                }
                .info-value {
                    color: #212529;
                }
                .amount-section {
                    background: #fff5f5;
                    border: 2px solid #dc3545;
                    border-radius: 10px;
                    padding: 25px;
                    margin: 30px 0;
                }
                .amount-section h2 {
                    margin: 0 0 20px 0;
                    color: #dc3545;
                    text-align: center;
                    font-size: 20px;
                }
                .amount-display {
                    text-align: center;
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 15px 0;
                    border: 1px solid #dc3545;
                }
                .amount-label {
                    font-size: 14px;
                    color: #666;
                    margin-bottom: 5px;
                }
                .amount-value {
                    font-size: 32px;
                    font-weight: bold;
                    color: #dc3545;
                }
                .payment-instructions {
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .payment-instructions h3 {
                    color: #007bff;
                    margin-top: 0;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #dee2e6;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 100px;
                    color: rgba(220, 53, 69, 0.1);
                    font-weight: bold;
                    z-index: -1;
                    pointer-events: none;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class='watermark'>BPA INVOICE</div>
            
            <div class='header'>
                <div class='logo-section'>
                    BPA
                </div>
                <div class='company-info'>
                    <h1 class='company-name'>{$this->settings['name']}</h1>
                    <div class='company-details'>
                        <div><strong>📧 Email:</strong> {$this->settings['email']}</div>
                        <div><strong>📞 Phone:</strong> {$this->settings['contact']}</div>
                        <div><strong>📍 Address:</strong> {$this->settings['address']}</div>
                    </div>
                </div>
            </div>
            
            <div class='invoice-title'>
                <h1>RENT INVOICE</h1>
                <div class='invoice-number'>Invoice #: {$invoice_number}</div>
            </div>
            
            <div class='urgency-banner {$urgency_class}'>
                {$urgency_message}
            </div>
            
            <div class='info-grid'>
                <div class='info-section'>
                    <h3>🏠 Property Information</h3>
                    <div class='info-row'>
                        <span class='info-label'>House Number:</span>
                        <span class='info-value'>{$tenant['house_no']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Monthly Rent:</span>
                        <span class='info-value'>$" . number_format($tenant['monthly_rent'], 2) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Property Type:</span>
                        <span class='info-value'>{$tenant['house_description']}</span>
                    </div>
                </div>
                
                <div class='info-section'>
                    <h3>👤 Tenant Information</h3>
                    <div class='info-row'>
                        <span class='info-label'>Name:</span>
                        <span class='info-value'>{$tenant['full_name']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Email:</span>
                        <span class='info-value'>{$tenant['email']}</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Contact:</span>
                        <span class='info-value'>{$tenant['contact']}</span>
                    </div>
                </div>
            </div>
            
            <div class='amount-section'>
                <h2>💰 Payment Details</h2>
                
                <div class='info-grid'>
                    <div>
                        <div class='info-row'>
                            <span class='info-label'>Invoice Date:</span>
                            <span class='info-value'>{$invoice_date}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Due Date:</span>
                            <span class='info-value'>{$due_date_formatted}</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Days Until Due:</span>
                            <span class='info-value'>{$days_until_due} days</span>
                        </div>
                    </div>
                    
                    <div class='amount-display'>
                        <div class='amount-label'>Amount Due</div>
                        <div class='amount-value'>$" . number_format($outstanding > 0 ? $outstanding : $tenant['monthly_rent'], 2) . "</div>
                    </div>
                </div>
            </div>
            
            <div class='payment-instructions'>
                <h3>💳 Payment Instructions</h3>
                <p><strong>Please make your payment by {$due_date_formatted} to avoid late fees.</strong></p>
                <ul>
                    <li>Payment can be made in cash, check, or bank transfer</li>
                    <li>Please include your house number ({$tenant['house_no']}) and invoice number ({$invoice_number}) with your payment</li>
                    <li>For questions about this invoice, contact us at {$this->settings['email']} or {$this->settings['contact']}</li>
                    <li>Late payments may incur additional fees as per your rental agreement</li>
                </ul>
            </div>
            
            <div class='info-section'>
                <h3>📊 Account Summary</h3>
                <div class='info-row'>
                    <span class='info-label'>Monthly Rent:</span>
                    <span class='info-value'>$" . number_format($tenant['monthly_rent'], 2) . "</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Outstanding Balance:</span>
                    <span class='info-value'>$" . number_format($outstanding, 2) . "</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Current Amount Due:</span>
                    <span class='info-value' style='color: #dc3545; font-weight: bold;'>$" . number_format($outstanding > 0 ? $outstanding : $tenant['monthly_rent'], 2) . "</span>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>BPA Rental Management System</strong></p>
                <p>This invoice was generated on {$invoice_date}</p>
                <p>For any questions regarding this invoice, please contact us at {$this->settings['email']} or {$this->settings['contact']}</p>
                <p style='margin-top: 15px; font-size: 10px; color: #999;'>
                    Invoice ID: {$invoice_number} | Tenant ID: {$tenant['id']} | Generated: " . date('Y-m-d H:i:s') . "
                </p>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    public function sendInvoiceEmail($tenant_id, $due_date = null) {
        try {
            $invoice_data = $this->generateInvoiceForTenant($tenant_id, $due_date);
            
            $tenant = $invoice_data['tenant'];
            $due_date_formatted = $invoice_data['due_date']->format('F d, Y');
            $outstanding = $invoice_data['outstanding'];
            $amount_due = $outstanding > 0 ? $outstanding : $tenant['monthly_rent'];
            
            // Email subject
            $subject = "Rent Invoice - {$tenant['house_no']} - Due {$due_date_formatted}";
            
            // Email body (HTML version of invoice)
            $email_body = $invoice_data['html'];
            
            // Send email using existing email system
            return $this->emailManager->sendEmail(
                $tenant['email'],
                $subject,
                $email_body,
                '', // No plain text version for now
                'rent_invoice',
                $tenant_id
            );
            
        } catch (Exception $e) {
            error_log("Invoice email failed for tenant {$tenant_id}: " . $e->getMessage());
            return false;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    try {
        $generator = new InvoiceGenerator();
        
        switch ($_GET['action']) {
            case 'generate_invoice':
                $tenant_id = $_GET['tenant_id'] ?? 0;
                if ($tenant_id <= 0) {
                    throw new Exception("Invalid tenant ID");
                }
                
                $result = $generator->generateInvoiceForTenant($tenant_id);
                
                // Return HTML for browser display/printing
                header('Content-Type: text/html; charset=UTF-8');
                echo $result['html'];
                break;
                
            case 'send_invoice_email':
                $tenant_id = $_GET['tenant_id'] ?? 0;
                if ($tenant_id <= 0) {
                    throw new Exception("Invalid tenant ID");
                }
                
                $success = $generator->sendInvoiceEmail($tenant_id);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
