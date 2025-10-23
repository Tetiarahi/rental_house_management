<?php
include 'db_connect.php';
require_once 'email_class.php';

// Check if this is an AJAX request for manual sending
if (isset($_POST['action'])) {
    $emailManager = new EmailManager();
    
    switch ($_POST['action']) {
        case 'send_rent_due_notices':
            $sent_count = 0;
            $failed_count = 0;
            
            // Get tenants who need rent due notices
            $tenants = getTenantsDueForNotice();
            
            foreach ($tenants as $tenant) {
                if ($emailManager->sendRentDueNotice($tenant['id'])) {
                    $sent_count++;
                } else {
                    $failed_count++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'sent' => $sent_count,
                'failed' => $failed_count,
                'total' => count($tenants)
            ]);
            exit;


            
        case 'send_payment_reminders':
            $sent_count = 0;
            $failed_count = 0;
            
            // Get tenants who need payment reminders
            $tenants = getTenantsOverdue();
            
            foreach ($tenants as $tenant) {
                if ($emailManager->sendPaymentReminder($tenant['id'])) {
                    $sent_count++;
                } else {
                    $failed_count++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'sent' => $sent_count,
                'failed' => $failed_count,
                'total' => count($tenants)
            ]);
            exit;
    }
}

/**
 * Get tenants who need rent due notices
 */
function getTenantsDueForNotice() {
    global $conn;
    
    // Get notification settings
    $settings_query = "SELECT rent_due_days_notice FROM system_settings LIMIT 1";
    $settings_result = $conn->query($settings_query);
    $notice_days = 3; // default
    
    if ($settings_result && $settings_result->num_rows > 0) {
        $settings = $settings_result->fetch_assoc();
        $notice_days = $settings['rent_due_days_notice'] ?? 3;
    }
    
    $tenants = [];
    
    // Get all active tenants
    $tenant_query = "SELECT t.*, h.house_no, h.price 
                    FROM tenants t 
                    INNER JOIN houses h ON h.id = t.house_id 
                    WHERE t.status = 1 AND t.email != ''";
    $tenant_result = $conn->query($tenant_query);
    
    if ($tenant_result && $tenant_result->num_rows > 0) {
        while ($tenant = $tenant_result->fetch_assoc()) {
            // Calculate if tenant needs notice
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
            
            // Check if we should send notice
            if ($days_until_due <= $notice_days && $days_until_due > 0) {
                // Check if we haven't already sent a notice recently
                $recent_notice_query = "SELECT id FROM email_logs 
                                       WHERE tenant_id = ? 
                                       AND email_type = 'rent_due' 
                                       AND status = 'sent' 
                                       AND sent_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $stmt = $conn->prepare($recent_notice_query);
                $stmt->bind_param("i", $tenant['id']);
                $stmt->execute();
                $recent_result = $stmt->get_result();
                
                if ($recent_result->num_rows == 0) {
                    $tenant['days_until_due'] = $days_until_due;
                    $tenant['due_date'] = $due_date->format('Y-m-d');
                    $tenants[] = $tenant;
                }
            }
        }
    }
    
    return $tenants;
}

/**
 * Get tenants who are overdue and need reminders
 */
function getTenantsOverdue() {
    global $conn;
    
    // Get notification settings
    $settings_query = "SELECT payment_reminder_days FROM system_settings LIMIT 1";
    $settings_result = $conn->query($settings_query);
    $reminder_days = 7; // default
    
    if ($settings_result && $settings_result->num_rows > 0) {
        $settings = $settings_result->fetch_assoc();
        $reminder_days = $settings['payment_reminder_days'] ?? 7;
    }
    
    $tenants = [];
    
    // Get all active tenants
    $tenant_query = "SELECT t.*, h.house_no, h.price 
                    FROM tenants t 
                    INNER JOIN houses h ON h.id = t.house_id 
                    WHERE t.status = 1 AND t.email != ''";
    $tenant_result = $conn->query($tenant_query);
    
    if ($tenant_result && $tenant_result->num_rows > 0) {
        while ($tenant = $tenant_result->fetch_assoc()) {
            // Calculate if tenant is overdue
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
            
            // Check if tenant is overdue enough for reminder
            if ($days_overdue >= $reminder_days) {
                // Check if we haven't already sent a reminder recently
                $recent_reminder_query = "SELECT id FROM email_logs 
                                         WHERE tenant_id = ? 
                                         AND email_type = 'payment_reminder' 
                                         AND status = 'sent' 
                                         AND sent_date >= DATE_SUB(NOW(), INTERVAL 3 DAY)";
                $stmt = $conn->prepare($recent_reminder_query);
                $stmt->bind_param("i", $tenant['id']);
                $stmt->execute();
                $recent_result = $stmt->get_result();
                
                if ($recent_result->num_rows == 0) {
                    $tenant['days_overdue'] = $days_overdue;
                    $tenant['last_due_date'] = $last_due_date->format('Y-m-d');
                    $tenants[] = $tenant;
                }
            }
        }
    }
    
    return $tenants;
}

// If not AJAX, show the management interface
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-bell"></i> Email Notifications Management
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Manual Send Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fa fa-calendar"></i> Rent Due Notices</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $due_tenants = getTenantsDueForNotice();
                                    ?>
                                    <p><strong><?php echo count($due_tenants); ?></strong> tenants need rent due notices</p>
                                    
                                    <?php if (count($due_tenants) > 0): ?>
                                        <ul class="list-group list-group-flush mb-3">
                                            <?php foreach ($due_tenants as $tenant): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong><?php echo $tenant['firstname'] . ' ' . $tenant['lastname']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $tenant['house_no']; ?> - Due in <?php echo $tenant['days_until_due']; ?> days</small>
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary send-individual-notice" 
                                                        data-tenant-id="<?php echo $tenant['id']; ?>">
                                                    <i class="fa fa-envelope"></i> Send
                                                </button>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <button class="btn btn-primary btn-block" id="send-all-notices">
                                            <i class="fa fa-paper-plane"></i> Send All Rent Due Notices
                                        </button>
                                    <?php else: ?>
                                        <p class="text-muted">No tenants need rent due notices at this time.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Payment Reminders</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $overdue_tenants = getTenantsOverdue();
                                    ?>
                                    <p><strong><?php echo count($overdue_tenants); ?></strong> tenants need payment reminders</p>
                                    
                                    <?php if (count($overdue_tenants) > 0): ?>
                                        <ul class="list-group list-group-flush mb-3">
                                            <?php foreach ($overdue_tenants as $tenant): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong><?php echo $tenant['firstname'] . ' ' . $tenant['lastname']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $tenant['house_no']; ?> - <?php echo $tenant['days_overdue']; ?> days overdue</small>
                                                </span>
                                                <button class="btn btn-sm btn-outline-warning send-individual-reminder" 
                                                        data-tenant-id="<?php echo $tenant['id']; ?>">
                                                    <i class="fa fa-envelope"></i> Send
                                                </button>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <button class="btn btn-warning btn-block" id="send-all-reminders">
                                            <i class="fa fa-paper-plane"></i> Send All Payment Reminders
                                        </button>
                                    <?php else: ?>
                                        <p class="text-muted">No tenants need payment reminders at this time.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Email Logs Section -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa fa-history"></i> Recent Email Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Tenant</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $logs_query = "SELECT el.*, CONCAT(t.firstname, ' ', t.lastname) as tenant_name 
                                                      FROM email_logs el 
                                                      LEFT JOIN tenants t ON t.id = el.tenant_id 
                                                      ORDER BY el.created_date DESC 
                                                      LIMIT 20";
                                        $logs_result = $conn->query($logs_query);
                                        
                                        if ($logs_result && $logs_result->num_rows > 0):
                                            while ($log = $logs_result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($log['created_date'])); ?></td>
                                            <td><?php echo $log['tenant_name'] ?? 'N/A'; ?></td>
                                            <td><?php echo $log['email_to']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $log['email_type'] == 'rent_due' ? 'primary' : 
                                                        ($log['email_type'] == 'payment_reminder' ? 'warning' : 'success') 
                                                ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $log['email_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['email_subject']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $log['status'] == 'sent' ? 'success' : 
                                                        ($log['status'] == 'failed' ? 'danger' : 'secondary') 
                                                ?>">
                                                    <?php echo ucfirst($log['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No email activity yet</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Send all rent due notices
    $('#send-all-notices').click(function() {
        if (!confirm('Send rent due notices to all eligible tenants?')) return;
        
        start_load();
        
        $.ajax({
            url: 'email_notifications.php',
            method: 'POST',
            data: { action: 'send_rent_due_notices' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_toast(`Sent ${response.sent} notices successfully. ${response.failed} failed.`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert_toast('Failed to send notices.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error.', 'error');
                end_load();
            }
        });
    });

    // Send all payment reminders
    $('#send-all-reminders').click(function() {
        if (!confirm('Send payment reminders to all overdue tenants?')) return;
        
        start_load();
        
        $.ajax({
            url: 'email_notifications.php',
            method: 'POST',
            data: { action: 'send_payment_reminders' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_toast(`Sent ${response.sent} reminders successfully. ${response.failed} failed.`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert_toast('Failed to send reminders.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error.', 'error');
                end_load();
            }
        });
    });

    // Send individual notice
    $('.send-individual-notice').click(function() {
        var tenantId = $(this).data('tenant-id');
        
        start_load();
        
        $.ajax({
            url: 'ajax.php?action=send_rent_due_notice',
            method: 'POST',
            data: { tenant_id: tenantId },
            success: function(resp) {
                if (resp == 1) {
                    alert_toast('Rent due notice sent successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert_toast('Failed to send notice.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error.', 'error');
                end_load();
            }
        });
    });

    // Send individual reminder
    $('.send-individual-reminder').click(function() {
        var tenantId = $(this).data('tenant-id');
        
        start_load();
        
        $.ajax({
            url: 'ajax.php?action=send_payment_reminder',
            method: 'POST',
            data: { tenant_id: tenantId },
            success: function(resp) {
                if (resp == 1) {
                    alert_toast('Payment reminder sent successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert_toast('Failed to send reminder.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error.', 'error');
                end_load();
            }
        });
    });

});
</script>
