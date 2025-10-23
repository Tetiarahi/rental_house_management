<?php
include 'db_connect.php';

echo "<h1>🔍 Check Users and Fix Login Credentials</h1>";

// 1. Check current users in database
echo "<h2>1. Current Users in Database</h2>";
$users_query = "SELECT id, name, username, password, type FROM users ORDER BY type ASC, name ASC";
$users_result = $conn->query($users_query);

if ($users_result && $users_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Name</th>";
    echo "<th style='padding: 8px;'>Username</th>";
    echo "<th style='padding: 8px;'>Password Hash</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Role</th>";
    echo "</tr>";
    
    $roles = ['Super-admin', 'Admin', 'Staff'];
    
    while ($user = $users_result->fetch_assoc()) {
        $role = $roles[$user['type']] ?? 'Unknown';
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$user['id']}</td>";
        echo "<td style='padding: 8px;'>{$user['name']}</td>";
        echo "<td style='padding: 8px;'><strong>{$user['username']}</strong></td>";
        echo "<td style='padding: 8px; font-family: monospace; font-size: 12px;'>" . substr($user['password'], 0, 20) . "...</td>";
        echo "<td style='padding: 8px; text-align: center;'>{$user['type']}</td>";
        echo "<td style='padding: 8px;'><strong>$role</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No users found!</p>";
}

// 2. Test password hashes
echo "<h2>2. Password Hash Testing</h2>";
$test_passwords = [
    'admin123' => md5('admin123'),
    'superadmin123' => md5('superadmin123'),
    'admin' => md5('admin'),
    'password' => md5('password'),
    '123456' => md5('123456')
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f2f2f2;'>";
echo "<th style='padding: 8px;'>Password</th>";
echo "<th style='padding: 8px;'>MD5 Hash</th>";
echo "</tr>";

foreach ($test_passwords as $password => $hash) {
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$password</strong></td>";
    echo "<td style='padding: 8px; font-family: monospace; font-size: 12px;'>$hash</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check what the current admin password actually is
echo "<h2>3. Checking Existing Admin Password</h2>";
$admin_query = "SELECT username, password FROM users WHERE username = 'admin' LIMIT 1";
$admin_result = $conn->query($admin_query);

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    $admin_hash = $admin['password'];
    
    echo "<p><strong>Current admin password hash:</strong> <code>$admin_hash</code></p>";
    
    // Try to reverse-engineer the password by testing common ones
    $common_passwords = ['admin', 'admin123', 'password', '123456', 'rental', 'house', 'system'];
    $found_password = null;
    
    foreach ($common_passwords as $test_pass) {
        if (md5($test_pass) === $admin_hash) {
            $found_password = $test_pass;
            break;
        }
    }
    
    if ($found_password) {
        echo "<p style='color: green;'>✅ <strong>Found admin password:</strong> <code>$found_password</code></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Admin password not found in common passwords list</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No admin user found</p>";
}

// 4. Fix/Create users with known passwords
echo "<h2>4. Fix/Create Users with Known Passwords</h2>";

if (isset($_POST['fix_users'])) {
    echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #ced4da; border-radius: 5px; margin: 10px 0;'>";
    
    // Update/Create Super-admin
    $superadmin_password = md5('superadmin123');
    $check_superadmin = $conn->query("SELECT id FROM users WHERE username = 'superadmin'");
    
    if ($check_superadmin && $check_superadmin->num_rows > 0) {
        $update_superadmin = "UPDATE users SET name = 'Super Administrator', password = '$superadmin_password', type = 0 WHERE username = 'superadmin'";
        if ($conn->query($update_superadmin)) {
            echo "<p style='color: green;'>✅ Super-admin updated successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating super-admin: " . $conn->error . "</p>";
        }
    } else {
        $insert_superadmin = "INSERT INTO users (name, username, password, type) VALUES ('Super Administrator', 'superadmin', '$superadmin_password', 0)";
        if ($conn->query($insert_superadmin)) {
            echo "<p style='color: green;'>✅ Super-admin created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating super-admin: " . $conn->error . "</p>";
        }
    }
    
    // Update existing admin password
    $admin_password = md5('admin123');
    $update_admin = "UPDATE users SET password = '$admin_password', type = 1 WHERE username = 'admin'";
    if ($conn->query($update_admin)) {
        echo "<p style='color: green;'>✅ Admin password updated to 'admin123'</p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating admin: " . $conn->error . "</p>";
    }
    
    // Create a staff user for testing
    $staff_password = md5('staff123');
    $check_staff = $conn->query("SELECT id FROM users WHERE username = 'staff'");
    
    if ($check_staff && $check_staff->num_rows == 0) {
        $insert_staff = "INSERT INTO users (name, username, password, type) VALUES ('Staff User', 'staff', '$staff_password', 2)";
        if ($conn->query($insert_staff)) {
            echo "<p style='color: green;'>✅ Staff user created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating staff user: " . $conn->error . "</p>";
        }
    } else {
        $update_staff = "UPDATE users SET password = '$staff_password', type = 2 WHERE username = 'staff'";
        if ($conn->query($update_staff)) {
            echo "<p style='color: green;'>✅ Staff user updated successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating staff user: " . $conn->error . "</p>";
        }
    }
    
    echo "</div>";
    echo "<p><strong>🔄 <a href='check_users_and_fix.php'>Refresh page</a> to see updated users</strong></p>";
}

// 5. Show fix form
if (!isset($_POST['fix_users'])) {
    echo "<form method='post'>";
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔧 Fix User Accounts</h4>";
    echo "<p>This will create/update user accounts with known passwords:</p>";
    echo "<ul>";
    echo "<li><strong>superadmin</strong> / superadmin123 (Super-admin)</li>";
    echo "<li><strong>admin</strong> / admin123 (Admin)</li>";
    echo "<li><strong>staff</strong> / staff123 (Staff)</li>";
    echo "</ul>";
    echo "<button type='submit' name='fix_users' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔧 Fix Users</button>";
    echo "</div>";
    echo "</form>";
}

// 6. Test login form
echo "<h2>5. Test Login</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>";
echo "<h4>🧪 Quick Login Test</h4>";
echo "<form id='test-login-form'>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Username:</strong></label><br>";
echo "<input type='text' id='test-username' placeholder='Enter username' style='padding: 5px; width: 200px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Password:</strong></label><br>";
echo "<input type='password' id='test-password' placeholder='Enter password' style='padding: 5px; width: 200px;'>";
echo "</div>";
echo "<button type='button' onclick='testLogin()' style='background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer;'>🧪 Test Login</button>";
echo "</form>";
echo "<div id='login-result' style='margin-top: 15px;'></div>";
echo "</div>";

echo "<h2>6. Quick Actions</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da; border-radius: 5px;'>";
echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔑 Go to Login Page</a>";
echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";
echo "<a href='test_role_system.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔐 Test Roles</a>";
echo "</div>";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testLogin() {
    const username = document.getElementById('test-username').value;
    const password = document.getElementById('test-password').value;
    const resultDiv = document.getElementById('login-result');
    
    if (!username || !password) {
        resultDiv.innerHTML = '<p style="color: red;">❌ Please enter both username and password</p>';
        return;
    }
    
    resultDiv.innerHTML = '<p style="color: blue;">🔄 Testing login...</p>';
    
    $.ajax({
        url: 'ajax.php?action=login',
        method: 'POST',
        data: { username: username, password: password },
        success: function(resp) {
            if (resp == 1) {
                resultDiv.innerHTML = '<p style="color: green;">✅ Login successful! <a href="index.php">Go to Dashboard</a></p>';
            } else {
                resultDiv.innerHTML = '<p style="color: red;">❌ Login failed - Username or password incorrect</p>';
            }
        },
        error: function() {
            resultDiv.innerHTML = '<p style="color: red;">❌ Connection error</p>';
        }
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>
