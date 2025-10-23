<?php
include 'db_connect.php';
session_start();

// Access control: Only Super-admin (type 0) can access Email Settings
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 0) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Access Denied</h4>';
    echo '<p>You do not have permission to access Email Settings.</p>';
    echo '<hr>';
    echo '<p class="mb-0">Only <strong>Super-admin</strong> users can configure email settings.</p>';
    echo '<p class="mb-0">Current user type: <strong>' .
         (($_SESSION['login_type'] ?? 2) == 1 ? 'Admin' : 'Staff') . '</strong></p>';
    echo '</div>';
    echo '<a href="index.php?page=home" class="btn btn-primary"><i class="fa fa-home"></i> Back to Dashboard</a>';
    echo '</div>';
    exit;
}

// Get current email settings
$qry = $conn->query("SELECT * from system_settings limit 1");
if($qry->num_rows > 0){
    foreach($qry->fetch_array() as $k => $val){
        $meta[$k] = $val;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-envelope"></i> Email Notification Settings
                    </h4>
                </div>
                <div class="card-body">
                    <form action="" id="manage-email-settings">
                        
                        <!-- Email Notifications Toggle -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_notifications_enabled" 
                                       name="email_notifications_enabled" value="1" 
                                       <?php echo (isset($meta['email_notifications_enabled']) && $meta['email_notifications_enabled']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="email_notifications_enabled">
                                    <strong>Enable Email Notifications</strong>
                                </label>
                            </div>
                            <small class="text-muted">Turn on/off all email notifications system-wide</small>
                        </div>

                        <hr>

                        <!-- SMTP Configuration -->
                        <h5><i class="fa fa-server"></i> SMTP Server Configuration</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_host" class="control-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo isset($meta['smtp_host']) ? $meta['smtp_host'] : 'smtp.gmail.com' ?>" required>
                                    <small class="text-muted">e.g., smtp.gmail.com, smtp.outlook.com</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="smtp_port" class="control-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo isset($meta['smtp_port']) ? $meta['smtp_port'] : '587' ?>" required>
                                    <small class="text-muted">Usually 587 (TLS) or 465 (SSL)</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="smtp_encryption" class="control-label">Encryption</label>
                                    <select class="form-control" id="smtp_encryption" name="smtp_encryption" required>
                                        <option value="tls" <?php echo (isset($meta['smtp_encryption']) && $meta['smtp_encryption'] == 'tls') ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?php echo (isset($meta['smtp_encryption']) && $meta['smtp_encryption'] == 'ssl') ? 'selected' : '' ?>>SSL</option>
                                        <option value="none" <?php echo (isset($meta['smtp_encryption']) && $meta['smtp_encryption'] == 'none') ? 'selected' : '' ?>>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_username" class="control-label">SMTP Username/Email</label>
                                    <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                           value="<?php echo isset($meta['smtp_username']) ? $meta['smtp_username'] : '' ?>" required>
                                    <small class="text-muted">Your email address for sending emails</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smtp_password" class="control-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                           value="<?php echo isset($meta['smtp_password']) ? $meta['smtp_password'] : '' ?>" required>
                                    <small class="text-muted">For Gmail, use App Password instead of regular password</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email_from_name" class="control-label">From Name</label>
                            <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                                   value="<?php echo isset($meta['email_from_name']) ? $meta['email_from_name'] : 'Rental Management System' ?>" required>
                            <small class="text-muted">Name that appears as sender in emails</small>
                        </div>

                        <hr>

                        <!-- Notification Timing -->
                        <h5><i class="fa fa-clock"></i> Notification Timing</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rent_due_days_notice" class="control-label">Rent Due Notice (Days Before)</label>
                                    <input type="number" class="form-control" id="rent_due_days_notice" name="rent_due_days_notice" 
                                           value="<?php echo isset($meta['rent_due_days_notice']) ? $meta['rent_due_days_notice'] : '3' ?>" 
                                           min="1" max="30" required>
                                    <small class="text-muted">Send rent due notice X days before due date</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_reminder_days" class="control-label">Payment Reminder (Days After Due)</label>
                                    <input type="number" class="form-control" id="payment_reminder_days" name="payment_reminder_days" 
                                           value="<?php echo isset($meta['payment_reminder_days']) ? $meta['payment_reminder_days'] : '7' ?>" 
                                           min="1" max="30" required>
                                    <small class="text-muted">Send overdue reminder X days after due date</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Test Email Section -->
                        <h5><i class="fa fa-paper-plane"></i> Test Email Configuration</h5>
                        
                        <div class="form-group">
                            <label for="test_email" class="control-label">Test Email Address</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="test_email" placeholder="Enter email to test configuration">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info" id="send-test-email">
                                        <i class="fa fa-paper-plane"></i> Send Test Email
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Send a test email to verify your SMTP configuration</small>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> Save Email Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Templates Management -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-file-text"></i> Email Templates
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $templates = $conn->query("SELECT * FROM email_templates ORDER BY template_type");
                                while($template = $templates->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo $template['template_name'] ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $template['template_type'] == 'rent_due' ? 'primary' : 
                                                ($template['template_type'] == 'payment_reminder' ? 'warning' : 'success') 
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $template['template_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?php echo $template['subject'] ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $template['is_active'] ? 'success' : 'secondary' ?>">
                                            <?php echo $template['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-template" data-id="<?php echo $template['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-success preview-template" data-id="<?php echo $template['id'] ?>">
                                            <i class="fa fa-eye"></i> Preview
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Save email settings
    $('#manage-email-settings').submit(function(e) {
        e.preventDefault();
        start_load();
        
        $.ajax({
            url: 'ajax.php?action=save_email_settings',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success: function(resp) {
                if(resp == 1) {
                    alert_toast('Email settings saved successfully.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert_toast('Error saving email settings.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error. Please try again.', 'error');
                end_load();
            }
        });
    });

    // Send test email
    $('#send-test-email').click(function() {
        var testEmail = $('#test_email').val();
        if(!testEmail) {
            alert_toast('Please enter a test email address.', 'error');
            return;
        }

        start_load();
        
        $.ajax({
            url: 'ajax.php?action=send_test_email',
            data: { test_email: testEmail },
            method: 'POST',
            success: function(resp) {
                if(resp == 1) {
                    alert_toast('Test email sent successfully!', 'success');
                } else {
                    alert_toast('Failed to send test email. Please check your SMTP settings.', 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Connection error. Please try again.', 'error');
                end_load();
            }
        });
    });

    // Edit template
    $('.edit-template').click(function() {
        var templateId = $(this).data('id');
        // TODO: Open template editor modal
        alert_toast('Template editor coming soon!', 'info');
    });

    // Preview template
    $('.preview-template').click(function() {
        var templateId = $(this).data('id');
        // TODO: Open template preview modal
        alert_toast('Template preview coming soon!', 'info');
    });
});
</script>

<style>
.custom-control-label {
    font-weight: 500;
}

.card-header h4 {
    margin: 0;
    color: #2c3e50;
}

.badge {
    font-size: 0.8em;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
