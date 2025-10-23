<?php
include 'db_connect.php';

class Action {
    private $db;

    public function __construct() {
        $this->db = $GLOBALS['conn']; // Initialize with connection from db_connect.php
    }

    public function save_user() {
        // Extract and sanitize input
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $type = isset($_POST['type']) ? intval($_POST['type']) : 2; // Default to Staff

        // Validate input
        if (empty($name) || empty($username)) {
            return 0; // Invalid input
        }

        // Check for duplicate username, excluding current user if editing
        $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
        $check_stmt = $this->db->prepare($check_query);
        $check_stmt->bind_param("si", $username, $id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            return 2; // Username already exists
        }
        $check_stmt->close();

        // Handle password
        if (!empty($password)) {
            $password = md5($password); // Note: MD5 is insecure; consider password_hash()
        }

        if ($id > 0) {
            // Update existing user
            if (!empty($password)) {
                $query = "UPDATE users SET name = ?, username = ?, password = ?, type = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ssssi", $name, $username, $password, $type, $id);
            } else {
                $query = "UPDATE users SET name = ?, username = ?, type = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ssi", $name, $username, $type, $id);
            }
        } else {
            // Insert new user
            if (empty($password)) {
                return 0; // Password required for new user
            }
            $query = "INSERT INTO users (name, username, password, type) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssi", $name, $username, $password, $type);
        }

        // Execute query
        $result = $stmt->execute();
        $stmt->close();

        return $result ? 1 : 0; // Return 1 for success, 0 for failure
    }

    public function delete_user() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result ? 1 : 0;
        }
        return 0;
    }

    public function login() {
        // Implement login logic
        $username = trim($_POST['username']);
        $password = md5($_POST['password']); // Match save_user() hashing
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            session_start();
            $user = $result->fetch_assoc();
            $_SESSION['login_id'] = $user['id'];
            $_SESSION['login_name'] = $user['name'];
            $_SESSION['login_type'] = $user['type'];
            $stmt->close();
            return 1;
        }
        $stmt->close();
        return 0;
    }

    public function login2() {
        // Placeholder: Alternative login (e.g., for tenants)
        return 0;
    }

    public function logout() {
        // No database access needed
        session_start();
        $_SESSION = [];
        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        header("Location: login.php");
        exit();
    }

    public function logout2() {
        // Placeholder: Alternative logout
        return 0;
    }

    public function signup() {
        // Placeholder: Implement signup logic (similar to save_user)
        return 0;
    }

    public function update_account() {
        // Placeholder: Implement account update logic (similar to save_user)
        return 0;
    }

    public function save_settings() {
        // Implement system settings save (based on login.php fragment)
        $data = $_POST;
        $stmt = $this->db->prepare("UPDATE system_settings SET name = ?, email = ?, contact = ?, address = ? WHERE id = 1");
        $stmt->bind_param("ssss", $data['name'], $data['email'], $data['contact'], $data['address']);
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    public function save_email_settings() {
        try {
            $data = $_POST;

            // Prepare email settings update
            $query = "UPDATE system_settings SET
                     smtp_host = ?,
                     smtp_port = ?,
                     smtp_username = ?,
                     smtp_password = ?,
                     smtp_encryption = ?,
                     email_from_name = ?,
                     email_notifications_enabled = ?,
                     rent_due_days_notice = ?,
                     payment_reminder_days = ?
                     WHERE id = 1";

            $stmt = $this->db->prepare($query);

            $email_enabled = isset($data['email_notifications_enabled']) ? 1 : 0;

            $stmt->bind_param("sissssiis",
                $data['smtp_host'],
                $data['smtp_port'],
                $data['smtp_username'],
                $data['smtp_password'],
                $data['smtp_encryption'],
                $data['email_from_name'],
                $email_enabled,
                $data['rent_due_days_notice'],
                $data['payment_reminder_days']
            );

            $result = $stmt->execute();
            $stmt->close();

            return $result ? 1 : 0;

        } catch (Exception $e) {
            error_log("save_email_settings error: " . $e->getMessage());
            return 0;
        }
    }

    public function save_category() {
        // Implement category save (based on houses.php)
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name']);
        if (empty($name)) {
            return 0;
        }
        $check = $this->db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check->bind_param("si", $name, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            return 2; // Category exists
        }
        $check->close();

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
        }
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    public function delete_category() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result ? 1 : 0;
        }
        return 0;
    }

    public function save_house() {
        // Implement house save (based on houses.php)
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $house_no = trim($_POST['house_no']);
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);

        if (empty($house_no) || empty($category_id) || empty($description) || empty($price)) {
            return 0;
        }

        $check = $this->db->prepare("SELECT id FROM houses WHERE house_no = ? AND id != ?");
        $check->bind_param("si", $house_no, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            return 2; // House number exists
        }
        $check->close();

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE houses SET house_no = ?, category_id = ?, description = ?, price = ? WHERE id = ?");
            $stmt->bind_param("sisdi", $house_no, $category_id, $description, $price, $id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO houses (house_no, category_id, description, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisd", $house_no, $category_id, $description, $price);
        }
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    public function delete_house() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $this->db->prepare("DELETE FROM houses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result ? 1 : 0;
        }
        return 0;
    }

    public function save_tenant() {
        try {
            // Extract and sanitize input
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $middlename = trim($_POST['middlename'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $house_id = isset($_POST['house_id']) ? intval($_POST['house_id']) : 0;
            $date_in = trim($_POST['date_in'] ?? '');

        // Validate required fields
        if (empty($firstname) || empty($lastname) || empty($email) || empty($contact) || empty($house_id) || empty($date_in)) {
            return 0; // Missing required fields
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 4; // Invalid email format
        }

        // Validate registration date
        if (!DateTime::createFromFormat('Y-m-d', $date_in)) {
            return 8; // Invalid date format
        }

        // Check if registration date is reasonable (not too far in future, allow past dates)
        $reg_date = new DateTime($date_in);
        $current_date = new DateTime(date('Y-m-d'));

        // Allow past dates, but prevent dates more than 1 month in the future
        $max_future_date = clone $current_date;
        $max_future_date->add(new DateInterval('P1M')); // Add 1 month

        if ($reg_date > $max_future_date) {
            return 9; // Registration date cannot be more than 1 month in the future
        }

        // Prevent extremely old dates (more than 10 years ago)
        $min_date = clone $current_date;
        $min_date->sub(new DateInterval('P10Y')); // Subtract 10 years

        if ($reg_date < $min_date) {
            return 10; // Registration date cannot be more than 10 years ago
        }

        // Check if house_id exists
        $house_check = $this->db->prepare("SELECT id FROM houses WHERE id = ?");
        $house_check->bind_param("i", $house_id);
        $house_check->execute();
        $house_check->store_result();
        if ($house_check->num_rows == 0) {
            $house_check->close();
            return 3; // Invalid house ID
        }
        $house_check->close();

        // Check for duplicate active tenant in the same house
        $check_query = "SELECT id FROM tenants WHERE house_id = ? AND status = 1 AND id != ?";
        $check_stmt = $this->db->prepare($check_query);
        $check_stmt->bind_param("ii", $house_id, $id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            return 2; // House already assigned to an active tenant
        }
        $check_stmt->close();

        // Handle file upload
        $contract_file = null;
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/contracts/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_tmp = $_FILES['contract_file']['tmp_name'];
            $file_name = $_FILES['contract_file']['name'];
            $file_size = $_FILES['contract_file']['size'];
            $file_type = $_FILES['contract_file']['type'];

            // Validate file type
            if ($file_type !== 'application/pdf') {
                return 5; // Invalid file type
            }

            // Validate file size (5MB limit)
            if ($file_size > 20 * 1024 * 1024) {
                return 6; // File too large
            }

            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = 'contract_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $contract_file = $unique_filename;

                // If updating, delete old contract file
                if ($id > 0) {
                    $old_file_stmt = $this->db->prepare("SELECT contract_file FROM tenants WHERE id = ?");
                    $old_file_stmt->bind_param("i", $id);
                    $old_file_stmt->execute();
                    $old_file_result = $old_file_stmt->get_result();
                    if ($old_file_result->num_rows > 0) {
                        $old_file_row = $old_file_result->fetch_assoc();
                        if (!empty($old_file_row['contract_file'])) {
                            $old_file_path = $upload_dir . $old_file_row['contract_file'];
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                            }
                        }
                    }
                    $old_file_stmt->close();
                }
            } else {
                return 7; // File upload failed
            }
        }

        // Default status to 1 (active) if not provided
        $status = 1;

        if ($id > 0) {
            // Update existing tenant
            if ($contract_file !== null) {
                $query = "UPDATE tenants SET firstname = ?, lastname = ?, middlename = ?, email = ?, contact = ?, house_id = ?, date_in = ?, status = ?, contract_file = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("sssssisssi", $firstname, $lastname, $middlename, $email, $contact, $house_id, $date_in, $status, $contract_file, $id);
            } else {
                $query = "UPDATE tenants SET firstname = ?, lastname = ?, middlename = ?, email = ?, contact = ?, house_id = ?, date_in = ?, status = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("sssssissi", $firstname, $lastname, $middlename, $email, $contact, $house_id, $date_in, $status, $id);
            }
        } else {
            // Insert new tenant
            $query = "INSERT INTO tenants (firstname, lastname, middlename, email, contact, house_id, date_in, status, contract_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssisis", $firstname, $lastname, $middlename, $email, $contact, $house_id, $date_in, $status, $contract_file);
        }

        // Execute query
        $result = $stmt->execute();
        $stmt->close();

        return $result ? 1 : 0; // Return 1 for success, 0 for failure

        } catch (Exception $e) {
            // Log the error for debugging
            error_log("save_tenant error: " . $e->getMessage());
            return 0; // Return error
        }
    }

    public function delete_tenant() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $this->db->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result ? 1 : 0;
        }
        return 0;
    }

    private function formatDateSafely($date_string) {
        // Handle empty or invalid dates
        if (empty($date_string) || $date_string == '0000-00-00') {
            return 'Invalid Date';
        }

        // Clean the date string (remove any hidden characters)
        $date_string = trim($date_string);

        // Try DateTime class first (more reliable than strtotime)
        try {
            $date = DateTime::createFromFormat('Y-m-d', $date_string);
            if ($date && $date->format('Y-m-d') === $date_string) {
                return $date->format('M d, Y');
            }
        } catch (Exception $e) {
            // DateTime failed, try strtotime as fallback
        }

        // Fallback to strtotime
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date("M d, Y", $timestamp);
        }

        // If all else fails
        return 'Invalid Date';
    }

    public function get_tdetails() {
        // Extract and sanitize input
        $tenant_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($tenant_id <= 0) {
            return json_encode(['status' => 2, 'error' => 'Invalid tenant ID']);
        }

        // Query tenant and house details
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                CONCAT(t.lastname, ', ', t.firstname, ' ', COALESCE(t.middlename, '')) AS name,
                h.price,
                t.date_in
            FROM tenants t
            INNER JOIN houses h ON h.id = t.house_id
            WHERE t.id = ? AND t.status = 1
        ");
        if (!$stmt) {
            file_put_contents('error.log', "Prepare failed: " . $this->db->error . "\n", FILE_APPEND);
            return json_encode(['status' => 2, 'error' => 'Database query error']);
        }

        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();

            // Calculate months from registration date to current date
            // Handle invalid dates gracefully
            if (empty($data['date_in']) || $data['date_in'] == '0000-00-00' || !DateTime::createFromFormat('Y-m-d', $data['date_in'])) {
                // Invalid registration date - set months to 0 to avoid massive calculations
                $months = 0;
                $payable = 0;
            } else {
                $start_date = new DateTime($data['date_in']);
                $current_date = new DateTime(date('Y-m-d'));

                // Ensure registration date is not in the future
                if ($start_date > $current_date) {
                    $months = 0;
                    $payable = 0;
                } else {
                    $interval = $start_date->diff($current_date);
                    $months = ($interval->y * 12) + $interval->m;

                    // If we're past the day of the month when they registered, add 1 more month
                    if ($current_date->format('d') >= $start_date->format('d')) {
                        $months += 1;
                    }

                    $payable = $data['price'] * $months;
                }
            }

            // Query total paid amount
            $paid_query = $this->db->prepare("SELECT SUM(amount) AS paid FROM payments WHERE tenant_id = ?");
            if (!$paid_query) {
                file_put_contents('error.log', "Paid query prepare failed: " . $this->db->error . "\n", FILE_APPEND);
                $stmt->close();
                return json_encode(['status' => 2, 'error' => 'Payment query error']);
            }

            $paid_query->bind_param("i", $tenant_id);
            $paid_query->execute();
            $paid_result = $paid_query->get_result();
            $paid = $paid_result->num_rows > 0 ? $paid_result->fetch_assoc()['paid'] : 0;
            $paid_query->close();

            $outstanding = $payable - $paid;

            $stmt->close();
            return json_encode([
                'status' => 1,
                'id' => $data['id'],
                'name' => $data['name'],
                'price' => number_format($data['price'], 2),
                'outstanding' => number_format($outstanding, 2),
                'paid' => number_format($paid, 2),
                'rent_started' => $this->formatDateSafely($data['date_in']),
                'months' => $months
            ]);
        }

        $stmt->close();
        return json_encode(['status' => 2, 'error' => 'Tenant not found or inactive']);
    }

    public function save_payment() {
        // Extract and sanitize input
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $invoice = trim($_POST['invoice'] ?? '');
        $ref_number = trim($_POST['ref_number'] ?? ''); // Optional reference number
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $payment_date = trim($_POST['payment_date'] ?? '');

        // Validate inputs (ref_number is optional)
        if ($tenant_id <= 0 || empty($invoice) || $amount <= 0 || empty($payment_date)) {
            return 0; // Missing or invalid required fields
        }

        // Validate date format
        if (!DateTime::createFromFormat('Y-m-d', $payment_date)) {
            return 0; // Invalid date format
        }

        // Check if tenant_id exists and is active
        $tenant_check = $this->db->prepare("SELECT id FROM tenants WHERE id = ? AND status = 1");
        $tenant_check->bind_param("i", $tenant_id);
        $tenant_check->execute();
        $tenant_check->store_result();
        if ($tenant_check->num_rows == 0) {
            $tenant_check->close();
            return 2; // Invalid or inactive tenant
        }
        $tenant_check->close();

        if ($id > 0) {
            // Update existing payment
            $query = "UPDATE payments SET tenant_id = ?, invoice = ?, ref_number = ?, amount = ?, date_created = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("issdsi", $tenant_id, $invoice, $ref_number, $amount, $payment_date, $id);
        } else {
            // Insert new payment
            $query = "INSERT INTO payments (tenant_id, invoice, ref_number, amount, date_created) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("issds", $tenant_id, $invoice, $ref_number, $amount, $payment_date);
        }

        // Execute query
        $result = $stmt->execute();
        $payment_id = $this->db->insert_id; // Get the inserted payment ID
        $stmt->close();

        // Send payment confirmation email if payment was saved successfully
        if ($result && $payment_id > 0) {
            try {
                require_once 'email_class.php';
                $emailManager = new EmailManager();
                $emailManager->sendPaymentConfirmation($payment_id);
            } catch (Exception $e) {
                // Log email error but don't fail the payment save
                error_log("Payment confirmation email failed: " . $e->getMessage());
            }
        }

        return $result ? 1 : 0; // Return 1 for success, 0 for failure
    }

    public function delete_payment() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $this->db->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result ? 1 : 0;
        }
        return 0;
    }

    public function get_monthly_payments() {
        $current_year = date('Y');
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        $monthly_data = [];

        // Initialize all months with 0
        for ($i = 1; $i <= 12; $i++) {
            $monthly_data[$i] = 0;
        }

        // Get actual payment data for current year
        $query = "SELECT MONTH(date_created) as month, SUM(amount) as total
                  FROM payments
                  WHERE YEAR(date_created) = ?
                  GROUP BY MONTH(date_created)
                  ORDER BY MONTH(date_created)";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $current_year);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fill in actual data
        while ($row = $result->fetch_assoc()) {
            $monthly_data[$row['month']] = floatval($row['total']);
        }
        $stmt->close();

        // Convert to arrays for Chart.js
        $amounts = array_values($monthly_data);

        return json_encode([
            'months' => $months,
            'amounts' => $amounts,
            'year' => $current_year
        ]);
    }
}
?>