<?php
// Setup Super-admin role for the rental management system
include 'db_connect.php';

echo "<h1>🔧 Setting Up Super-admin Role</h1>";

try {
    // 1. Update the users table comment to reflect new user types
    echo "<h3>1. Updating Users Table Structure</h3>";
    $alter_query = "ALTER TABLE `users` 
                   MODIFY COLUMN `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '0=Super-admin,1=Admin,2=Staff'";
    
    if ($conn->query($alter_query)) {
        echo "<p style='color: green;'>✅ Users table structure updated successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating table structure: " . $conn->error . "</p>";
    }

    // 2. Insert a default Super-admin user
    echo "<h3>2. Creating Default Super-admin User</h3>";
    
    // Check if super-admin already exists
    $check_query = "SELECT id FROM users WHERE username = 'superadmin'";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        // Update existing super-admin
        $update_query = "UPDATE users SET 
                        name = 'Super Administrator', 
                        type = 0 
                        WHERE username = 'superadmin'";
        
        if ($conn->query($update_query)) {
            echo "<p style='color: blue;'>🔄 Super-admin user updated successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating super-admin: " . $conn->error . "</p>";
        }
    } else {
        // Insert new super-admin
        // Password: superadmin123 (MD5: 5e2b4d823db9d044ecd5e084b6d33ea5)
        $insert_query = "INSERT INTO users (name, username, password, type) VALUES 
                        ('Super Administrator', 'superadmin', '5e2b4d823db9d044ecd5e084b6d33ea5', 0)";
        
        if ($conn->query($insert_query)) {
            echo "<p style='color: green;'>✅ Super-admin user created successfully</p>";
            echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
            echo "<strong>📝 Super-admin Login Credentials:</strong><br>";
            echo "<strong>Username:</strong> superadmin<br>";
            echo "<strong>Password:</strong> superadmin123<br>";
            echo "<em>Please change this password after first login!</em>";
            echo "</p>";
        } else {
            echo "<p style='color: red;'>❌ Error creating super-admin: " . $conn->error . "</p>";
        }
    }

    // 3. Update existing admin user to ensure proper type
    echo "<h3>3. Updating Existing Admin Users</h3>";
    $update_admin_query = "UPDATE users SET type = 1 WHERE username = 'admin' AND type != 0";
    
    if ($conn->query($update_admin_query)) {
        $affected_rows = $conn->affected_rows;
        if ($affected_rows > 0) {
            echo "<p style='color: green;'>✅ Updated $affected_rows existing admin user(s)</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ No admin users needed updating</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error updating admin users: " . $conn->error . "</p>";
    }

    // 4. Display current user types for verification
    echo "<h3>4. Current User List</h3>";
    $users_query = "SELECT 
                    id,
                    name,
                    username,
                    CASE 
                        WHEN type = 0 THEN 'Super-admin'
                        WHEN type = 1 THEN 'Admin'
                        WHEN type = 2 THEN 'Staff'
                        ELSE 'Unknown'
                    END as user_type,
                    type as type_code
                    FROM users 
                    ORDER BY type ASC, name ASC";
    
    $users_result = $conn->query($users_query);
    
    if ($users_result && $users_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f2f2f2;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Name</th>";
        echo "<th style='padding: 8px;'>Username</th>";
        echo "<th style='padding: 8px;'>User Type</th>";
        echo "<th style='padding: 8px;'>Type Code</th>";
        echo "</tr>";
        
        while ($user = $users_result->fetch_assoc()) {
            $row_color = '';
            if ($user['type_code'] == 0) $row_color = 'background: #d4edda;'; // Green for Super-admin
            elseif ($user['type_code'] == 1) $row_color = 'background: #cce5ff;'; // Blue for Admin
            else $row_color = 'background: #f8f9fa;'; // Light gray for Staff
            
            echo "<tr style='$row_color'>";
            echo "<td style='padding: 8px; text-align: center;'>{$user['id']}</td>";
            echo "<td style='padding: 8px;'>{$user['name']}</td>";
            echo "<td style='padding: 8px;'>{$user['username']}</td>";
            echo "<td style='padding: 8px; font-weight: bold;'>{$user['user_type']}</td>";
            echo "<td style='padding: 8px; text-align: center;'>{$user['type_code']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No users found in database</p>";
    }

    echo "<h3>✅ Super-admin Role Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎯 Role Hierarchy:</h4>";
    echo "<ul>";
    echo "<li><strong>Super-admin (Type 0):</strong> Full access to everything including Email Settings</li>";
    echo "<li><strong>Admin (Type 1):</strong> Access to everything except Email Settings</li>";
    echo "<li><strong>Staff (Type 2):</strong> Limited access to basic functions</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔄 Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Update navigation and access control files</li>";
    echo "<li>Update user management interface</li>";
    echo "<li>Test the new role system</li>";
    echo "<li>Change default super-admin password</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🏠 Back to Dashboard</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3, h4 { color: #2c3e50; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
</style>
