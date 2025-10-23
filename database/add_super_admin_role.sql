-- Add Super-admin role to the rental management system
-- This script adds a new user type (0 = Super-admin) and creates a default super-admin user

-- First, let's add a Super-admin user type
-- Current types: 1 = Admin, 2 = Staff
-- New type: 0 = Super-admin (highest privilege level)

-- Update the users table comment to reflect new user types
ALTER TABLE `users` 
MODIFY COLUMN `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '0=Super-admin,1=Admin,2=Staff';

-- Insert a default Super-admin user
-- Username: superadmin, Password: superadmin123 (MD5: 5e2b4d823db9d044ecd5e084b6d33ea5)
INSERT INTO `users` (`name`, `username`, `password`, `type`) VALUES
('Super Administrator', 'superadmin', '5e2b4d823db9d044ecd5e084b6d33ea5', 0)
ON DUPLICATE KEY UPDATE 
`name` = VALUES(`name`),
`type` = VALUES(`type`);

-- Update existing admin user to ensure proper type
UPDATE `users` SET `type` = 1 WHERE `username` = 'admin' AND `type` != 0;

-- Display current user types for verification
SELECT 
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
ORDER BY type ASC, name ASC;
