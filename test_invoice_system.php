<?php
include 'db_connect.php';
session_start();

echo "<h1>📧 BPA Automated Invoice System</h1>";

// Check if user is logged in
if (!isset($_SESSION['login_id'])) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Not Logged In</h3>";
    echo "<p>Please log in to test the invoice system.</p>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Login</a>";
    echo "</div>";
    exit;
}

echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ BPA Automated Invoice System</h3>";
echo "<p>Invoices are now automatically attached to rent due notices and payment reminder emails.</p>";
echo "<p><strong>How It Works:</strong></p>";
echo "<ul>";
echo "<li>📧 <strong>Rent Due Notices:</strong> Include invoice attachment automatically</li>";
echo "<li>⚠️ <strong>Payment Reminders:</strong> Include outstanding invoice attachment</li>";
echo "<li>🏢 <strong>BPA Branding:</strong> Professional company logo and styling</li>";
echo "<li>💰 <strong>Smart Calculations:</strong> Outstanding balance and due dates</li>";
echo "<li>📄 <strong>Professional Layout:</strong> Clean, organized invoice design</li>";
echo "<li>🔄 <strong>Automatic Integration:</strong> No manual invoice sending needed</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔄 Automatic Invoice Integration</h3>";
echo "<p><strong>Invoices are automatically included when:</strong></p>";
echo "<ul>";
echo "<li>📧 Sending rent due notices from Email Notifications page</li>";
echo "<li>⚠️ Sending payment reminders from Email Notifications page</li>";
echo "<li>📅 Each email includes a professional BPA-branded invoice</li>";
echo "<li>💰 Invoice shows current outstanding balance and due dates</li>";
echo "</ul>";
echo "</div>";

// Get active tenants for testing
echo "<h2>👥 Active Tenants Available for Invoice Generation</h2>";

$tenants_query = "SELECT 
                    t.id,
                    t.firstname,
                    t.lastname,
                    t.email,
                    t.contact,
                    t.date_in,
                    h.house_no,
                    h.price as monthly_rent,
                    h.description as house_description
                  FROM tenants t
                  INNER JOIN houses h ON h.id = t.house_id
                  WHERE t.status = 1
                  ORDER BY h.house_no";

$tenants_result = $conn->query($tenants_query);

if ($tenants_result && $tenants_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th style='padding: 10px;'>Tenant ID</th>";
    echo "<th style='padding: 10px;'>Name</th>";
    echo "<th style='padding: 10px;'>House</th>";
    echo "<th style='padding: 10px;'>Monthly Rent</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Move-in Date</th>";
    echo "<th style='padding: 10px;'>Actions</th>";
    echo "</tr>";
    
    while ($tenant = $tenants_result->fetch_assoc()) {
        $full_name = $tenant['firstname'] . ' ' . $tenant['lastname'];
        $move_in_date = date('M d, Y', strtotime($tenant['date_in']));
        
        echo "<tr>";
        echo "<td style='padding: 10px; text-align: center;'><strong>{$tenant['id']}</strong></td>";
        echo "<td style='padding: 10px;'>{$full_name}</td>";
        echo "<td style='padding: 10px; text-align: center;'>{$tenant['house_no']}</td>";
        echo "<td style='padding: 10px; text-align: right;'>$" . number_format($tenant['monthly_rent'], 2) . "</td>";
        echo "<td style='padding: 10px;'>{$tenant['email']}</td>";
        echo "<td style='padding: 10px;'>{$move_in_date}</td>";
        echo "<td style='padding: 10px; text-align: center;'>";
        echo "<button onclick='previewInvoice({$tenant['id']})' style='background: #17a2b8; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin: 2px;'>";
        echo "<i class='fa fa-eye'></i> Preview";
        echo "</button>";
        echo "<button onclick='sendInvoiceEmail({$tenant['id']})' style='background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin: 2px;'>";
        echo "<i class='fa fa-envelope'></i> Send Email";
        echo "</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px;'>";
    echo "<h4>⚠️ No Active Tenants Found</h4>";
    echo "<p>No active tenants are available in the database. Please add some tenants first to test the invoice system.</p>";
    echo "<a href='index.php?page=tenants' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Tenants</a>";
    echo "</div>";
}

// Show BPA invoice features
echo "<h2>🏢 BPA Invoice Features</h2>";

echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<h4>📋 Invoice Content & Design</h4>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h5>🎨 Visual Design</h5>";
echo "<ul>";
echo "<li><strong>BPA Logo:</strong> Red gradient design with 'BPA' text</li>";
echo "<li><strong>Color Scheme:</strong> Professional red (#dc3545) theme</li>";
echo "<li><strong>Watermark:</strong> 'BPA INVOICE' background for authenticity</li>";
echo "<li><strong>Layout:</strong> Grid-based responsive design</li>";
echo "<li><strong>Typography:</strong> Clean Arial font with proper hierarchy</li>";
echo "</ul>";

echo "<h5>📊 Information Sections</h5>";
echo "<ul>";
echo "<li><strong>Company Info:</strong> BPA name, email, phone, address</li>";
echo "<li><strong>Property Details:</strong> House number, monthly rent, type</li>";
echo "<li><strong>Tenant Details:</strong> Name, email, contact information</li>";
echo "<li><strong>Payment Info:</strong> Amount due, due date, invoice number</li>";
echo "<li><strong>Account Summary:</strong> Outstanding balance, payment status</li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h5>⚠️ Urgency Indicators</h5>";
echo "<ul>";
echo "<li><strong>Urgent (≤3 days):</strong> Red banner with warning</li>";
echo "<li><strong>Warning (≤7 days):</strong> Yellow banner with notice</li>";
echo "<li><strong>Normal (>7 days):</strong> Blue banner with reminder</li>";
echo "</ul>";

echo "<h5>💳 Payment Instructions</h5>";
echo "<ul>";
echo "<li>Clear payment methods and instructions</li>";
echo "<li>Reference numbers for payment tracking</li>";
echo "<li>Contact information for questions</li>";
echo "<li>Late fee warnings and policies</li>";
echo "</ul>";

echo "<h5>🔢 Smart Calculations</h5>";
echo "<ul>";
echo "<li><strong>Due Dates:</strong> Based on tenant move-in date</li>";
echo "<li><strong>Outstanding Balance:</strong> Automatic calculation</li>";
echo "<li><strong>Days Until Due:</strong> Real-time countdown</li>";
echo "<li><strong>Invoice Numbers:</strong> BPA-INV-YYYY-TTTT-MM format</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Bulk sending information
echo "<h2>📧 Bulk Invoice Sending</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>";
echo "<h4>🚀 Mass Email Features</h4>";
echo "<p>The system supports sending invoices to all active tenants at once through the Email Notifications page.</p>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h5>📋 Bulk Sending Process</h5>";
echo "<ol>";
echo "<li>Go to Email Notifications page</li>";
echo "<li>Find the 'Rent Invoices' section</li>";
echo "<li>Click 'Send All Rent Invoices' button</li>";
echo "<li>Confirm the bulk sending action</li>";
echo "<li>System sends to all active tenants</li>";
echo "<li>View success/failure report</li>";
echo "</ol>";
echo "</div>";

echo "<div>";
echo "<h5>✅ Bulk Sending Benefits</h5>";
echo "<ul>";
echo "<li>Send to all tenants with one click</li>";
echo "<li>Consistent BPA branding across all invoices</li>";
echo "<li>Automatic due date calculation for each tenant</li>";
echo "<li>Individual outstanding balance calculations</li>";
echo "<li>Email delivery confirmation tracking</li>";
echo "<li>Error handling and reporting</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Integration information
echo "<h2>🔗 System Integration</h2>";
echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px;'>";
echo "<h4>🔧 How Invoice System Integrates</h4>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h5>📍 Access Points</h5>";
echo "<ul>";
echo "<li><strong>Tenants Page:</strong> 'Invoice' button for each tenant</li>";
echo "<li><strong>Email Notifications:</strong> Bulk and individual sending</li>";
echo "<li><strong>Direct URLs:</strong> API endpoints for integration</li>";
echo "</ul>";

echo "<h5>📧 Email Integration</h5>";
echo "<ul>";
echo "<li>Uses existing EmailManager class</li>";
echo "<li>SMTP configuration from system settings</li>";
echo "<li>Email logging and tracking</li>";
echo "<li>Error handling and retry logic</li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h5>💾 Database Integration</h5>";
echo "<ul>";
echo "<li>Pulls tenant and house data automatically</li>";
echo "<li>Calculates outstanding balances in real-time</li>";
echo "<li>Uses system settings for company information</li>";
echo "<li>Logs all email sending attempts</li>";
echo "</ul>";

echo "<h5>🎨 Customization Options</h5>";
echo "<ul>";
echo "<li>Company details from system settings</li>";
echo "<li>Configurable email templates</li>";
echo "<li>Adjustable due date calculations</li>";
echo "<li>Custom payment instructions</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Navigation and next steps
echo "<h2>🔗 Navigation & Next Steps</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da; border-radius: 5px;'>";
echo "<h4>📖 How to Use the Invoice System</h4>";
echo "<ol>";
echo "<li><strong>Individual Invoices:</strong> Go to Tenants page and click 'Invoice' button for any tenant</li>";
echo "<li><strong>Bulk Invoices:</strong> Go to Email Notifications page and use the 'Rent Invoices' section</li>";
echo "<li><strong>Preview First:</strong> Always preview invoices before sending to check accuracy</li>";
echo "<li><strong>Monitor Results:</strong> Check email logs to confirm successful delivery</li>";
echo "</ol>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php?page=tenants' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Tenants Page</a>";
echo "<a href='index.php?page=email_notifications' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Email Notifications</a>";
echo "<a href='index.php?page=email_settings' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Email Settings</a>";
echo "<a href='index.php?page=home' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";
echo "</div>";
echo "</div>";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function previewInvoice(tenantId) {
    var invoice_url = 'invoice_generator.php?action=generate_invoice&tenant_id=' + tenantId;
    
    // Open invoice in new window for preview
    var invoice_window = window.open(invoice_url, '_blank', 'width=800,height=900,scrollbars=yes,resizable=yes');
    
    if (!invoice_window) {
        alert('Please allow popups to view the invoice preview');
    }
}

function sendInvoiceEmail(tenantId) {
    if (confirm('Send rent invoice email to this tenant?')) {
        // Show loading state
        var button = event.target;
        var originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
        
        $.ajax({
            url: 'invoice_generator.php?action=send_invoice_email&tenant_id=' + tenantId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Invoice email sent successfully!');
                    button.style.background = '#28a745';
                    button.innerHTML = '<i class="fa fa-check"></i> Sent';
                } else {
                    alert('Failed to send invoice email');
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            },
            error: function() {
                alert('Error sending invoice email');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
ul, ol { margin: 10px 0; padding-left: 20px; }
</style>
