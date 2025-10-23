<?php 
include 'db_connect.php';
session_start();
if (isset($_GET['id'])) {
    $user = $conn->query("SELECT * FROM users WHERE id = " . $_GET['id']);
    foreach ($user->fetch_array() as $k => $v) {
        $meta[$k] = $v;
    }
}
?>
<div class="container-fluid">
    <div id="msg"></div>
    
    <form action="" id="manage-user">    
        <input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id'] : '' ?>">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($meta['name']) ? $meta['name'] : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username'] : '' ?>" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
            <?php if (isset($meta['id'])): ?>
            <small><i>Leave this blank if you don’t want to change the password.</i></small>
            <?php endif; ?>
        </div>
        <?php if (isset($meta['type']) && $meta['type'] == 3): ?>
            <input type="hidden" name="type" value="3">
        <?php else: ?>
        <?php if (!isset($_GET['mtype'])): ?>
        <div class="form-group">
            <label for="type">User Type</label>
            <select name="type" id="type" class="custom-select">
                <option value="2" <?php echo isset($meta['type']) && $meta['type'] == 2 ? 'selected' : '' ?>>Staff</option>
                <option value="1" <?php echo isset($meta['type']) && $meta['type'] == 1 ? 'selected' : '' ?>>Admin</option>
                <?php
                // Only Super-admin can create other Super-admin users
                if (isset($_SESSION['login_type']) && $_SESSION['login_type'] == 0):
                ?>
                <option value="0" <?php echo isset($meta['type']) && $meta['type'] == 0 ? 'selected' : '' ?>>Super-admin</option>
                <?php endif; ?>
            </select>
            <?php if (isset($_SESSION['login_type']) && $_SESSION['login_type'] == 0): ?>
            <small class="text-muted">
                <i class="fa fa-info-circle"></i>
                <strong>Super-admin:</strong> Full system access including Email Settings<br>
                <strong>Admin:</strong> All access except Email Settings<br>
                <strong>Staff:</strong> Basic access only
            </small>
            <?php else: ?>
            <small class="text-muted">
                <i class="fa fa-info-circle"></i>
                <strong>Admin:</strong> All access except Email Settings<br>
                <strong>Staff:</strong> Basic access only
            </small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </form>
</div>
<script>
    $('#manage-user').submit(function(e) {
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_user',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Data successfully saved", 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else if (resp == 2) {
                    $('#msg').html('<div class="alert alert-danger">Username already exists.</div>');
                    end_load();
                } else {
                    $('#msg').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                    end_load();
                }
            },
            error: function() {
                $('#msg').html('<div class="alert alert-danger">Failed to connect to server.</div>');
                end_load();
            }
        });
    });
</script>