<?php
session_start();
include 'db_connect.php';

echo "<h1>🔐 Role-Based Access Control Test</h1>";

// Check if user is logged in
if (!isset($_SESSION['login_id'])) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Not Logged In</h3>";
    echo "<p>Please log in to test the role system.</p>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Login</a>";
    echo "</div>";
    exit;
}

// Get current user info
$user_id = $_SESSION['login_id'];
$user_name = $_SESSION['login_name'] ?? 'Unknown';
$user_type = $_SESSION['login_type'] ?? 2;

// Role definitions
$roles = [
    0 => ['name' => 'Super-admin', 'color' => '#dc3545', 'description' => 'Full access to everything including Email Settings'],
    1 => ['name' => 'Admin', 'color' => '#ffc107', 'description' => 'Access to everything except Email Settings'],
    2 => ['name' => 'Staff', 'color' => '#17a2b8', 'description' => 'Basic access to core functions only']
];

$current_role = $roles[$user_type] ?? $roles[2];

echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Current User Information</h3>";
echo "<p><strong>Name:</strong> $user_name</p>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>Role:</strong> <span style='color: {$current_role['color']}; font-weight: bold;'>{$current_role['name']}</span></p>";
echo "<p><strong>Type Code:</strong> $user_type</p>";
echo "<p><strong>Description:</strong> {$current_role['description']}</p>";
echo "</div>";

// Test access permissions
echo "<h2>🧪 Access Permission Tests</h2>";

$access_tests = [
    'Dashboard' => ['required_level' => 2, 'url' => 'index.php?page=home'],
    'House Types' => ['required_level' => 2, 'url' => 'index.php?page=categories'],
    'Houses' => ['required_level' => 2, 'url' => 'index.php?page=houses'],
    'Tenants' => ['required_level' => 2, 'url' => 'index.php?page=tenants'],
    'Payments' => ['required_level' => 2, 'url' => 'index.php?page=invoices'],
    'Reports' => ['required_level' => 2, 'url' => 'index.php?page=reports'],
    'Users Management' => ['required_level' => 1, 'url' => 'index.php?page=users'],
    'Email Notifications' => ['required_level' => 1, 'url' => 'index.php?page=email_notifications'],
    'Email Settings' => ['required_level' => 0, 'url' => 'index.php?page=email_settings']
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f2f2f2;'>";
echo "<th style='padding: 10px;'>Feature</th>";
echo "<th style='padding: 10px;'>Required Role</th>";
echo "<th style='padding: 10px;'>Your Access</th>";
echo "<th style='padding: 10px;'>Status</th>";
echo "<th style='padding: 10px;'>Action</th>";
echo "</tr>";

foreach ($access_tests as $feature => $test) {
    $required_level = $test['required_level'];
    $required_role = $roles[$required_level]['name'];
    $has_access = $user_type <= $required_level;
    
    $status_color = $has_access ? '#28a745' : '#dc3545';
    $status_text = $has_access ? '✅ Allowed' : '❌ Denied';
    $access_text = $has_access ? 'YES' : 'NO';
    
    echo "<tr>";
    echo "<td style='padding: 10px;'><strong>$feature</strong></td>";
    echo "<td style='padding: 10px;'>{$required_role}+</td>";
    echo "<td style='padding: 10px; text-align: center; font-weight: bold; color: $status_color;'>$access_text</td>";
    echo "<td style='padding: 10px; color: $status_color;'>$status_text</td>";
    echo "<td style='padding: 10px;'>";
    if ($has_access) {
        echo "<a href='{$test['url']}' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;'>Test Access</a>";
    } else {
        echo "<span style='color: #6c757d; font-style: italic;'>No Access</span>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Show all users and their roles
echo "<h2>👥 All System Users</h2>";

$users_query = "SELECT id, name, username, type FROM users ORDER BY type ASC, name ASC";
$users_result = $conn->query($users_query);

if ($users_result && $users_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th style='padding: 10px;'>ID</th>";
    echo "<th style='padding: 10px;'>Name</th>";
    echo "<th style='padding: 10px;'>Username</th>";
    echo "<th style='padding: 10px;'>Role</th>";
    echo "<th style='padding: 10px;'>Permissions</th>";
    echo "</tr>";
    
    while ($user = $users_result->fetch_assoc()) {
        $user_role = $roles[$user['type']] ?? $roles[2];
        $is_current = $user['id'] == $user_id;
        $row_style = $is_current ? 'background: #fff3cd;' : '';
        
        echo "<tr style='$row_style'>";
        echo "<td style='padding: 10px; text-align: center;'>{$user['id']}</td>";
        echo "<td style='padding: 10px;'>{$user['name']}" . ($is_current ? ' <strong>(You)</strong>' : '') . "</td>";
        echo "<td style='padding: 10px;'>{$user['username']}</td>";
        echo "<td style='padding: 10px; color: {$user_role['color']}; font-weight: bold;'>{$user_role['name']}</td>";
        echo "<td style='padding: 10px; font-size: 12px;'>{$user_role['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #dc3545;'>❌ No users found in database</p>";
}

// Test login credentials
echo "<h2>🔑 Test Login Credentials</h2>";
echo "<div style='background: #e9ecef; padding: 20px; border: 1px solid #ced4da; border-radius: 5px;'>";
echo "<h4>Available Test Accounts:</h4>";
echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";

$test_accounts = [
    ['username' => 'superadmin', 'password' => 'superadmin123', 'role' => 'Super-admin', 'color' => '#dc3545'],
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'Admin', 'color' => '#ffc107'],
];

foreach ($test_accounts as $account) {
    echo "<div style='background: white; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; min-width: 200px;'>";
    echo "<h5 style='color: {$account['color']}; margin-top: 0;'>{$account['role']}</h5>";
    echo "<p><strong>Username:</strong> {$account['username']}</p>";
    echo "<p><strong>Password:</strong> {$account['password']}</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Navigation links
echo "<h2>🔗 Quick Navigation</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>";
echo "<a href='index.php?page=home' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";

if ($user_type <= 1) {
    echo "<a href='index.php?page=users' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Users</a>";
    echo "<a href='index.php?page=email_notifications' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Email Notifications</a>";
}

if ($user_type == 0) {
    echo "<a href='index.php?page=email_settings' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Email Settings</a>";
}

echo "<a href='ajax.php?action=logout' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚪 Logout</a>";
echo "</div>";

// Role hierarchy explanation
echo "<h2>📊 Role Hierarchy Explanation</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<h4>🔐 Access Control Logic:</h4>";
echo "<ul>";
echo "<li><strong>Super-admin (Type 0):</strong> Can access everything, including Email Settings</li>";
echo "<li><strong>Admin (Type 1):</strong> Can access everything except Email Settings</li>";
echo "<li><strong>Staff (Type 2):</strong> Can access basic functions only</li>";
echo "</ul>";
echo "<p><strong>Implementation:</strong> Access is granted when <code>user_type <= required_level</code></p>";
echo "<p><strong>Example:</strong> Email Settings requires level 0, so only Super-admin (type 0) can access it.</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
</style>
