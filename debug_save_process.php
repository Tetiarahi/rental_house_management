<?php
include 'db_connect.php';
include 'admin_class.php';

echo "<h1>🔍 Debug Save Process Step by Step</h1>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we have any houses to assign
$houses = $conn->query("SELECT id, house_no FROM houses LIMIT 5");
echo "<h2>🏠 Available Houses:</h2>";
if ($houses && $houses->num_rows > 0) {
    echo "<ul>";
    while ($house = $houses->fetch_assoc()) {
        echo "<li>House ID: {$house['id']}, House No: {$house['house_no']}</li>";
    }
    echo "</ul>";

    // Use the first available house
    $houses->data_seek(0);
    $first_house = $houses->fetch_assoc();
    $house_id = $first_house['id'];
} else {
    echo "<p style='color: red;'>❌ No houses found! This might be the problem.</p>";
    $house_id = 1; // Try anyway
}

// Test the save process with detailed logging
echo "<h2>🧪 Testing Save Process</h2>";

// Simulate form submission
$test_data = [
    'firstname' => 'Debug',
    'lastname' => 'Test',
    'middlename' => 'User',
    'email' => 'debug' . time() . '@test.com',
    'contact' => '1234567890',
    'house_id' => $house_id,
    'date_in' => '2025-08-15'
];

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<h3>📝 Form Data Being Sent:</h3>";
foreach ($test_data as $key => $value) {
    echo "<p><strong>$key:</strong> $value</p>";
    $_POST[$key] = $value; // Set POST data
}
echo "</div>";

// Create admin instance and test save
$admin = new Action();

echo "<h3>🔧 Step-by-Step Validation:</h3>";

// Test each validation step manually
$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$house_id = isset($_POST['house_id']) ? intval($_POST['house_id']) : 0;
$date_in = trim($_POST['date_in'] ?? '');

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>1. Required Fields Check:</h4>";
$required_ok = !empty($firstname) && !empty($lastname) && !empty($email) && !empty($contact) && !empty($house_id) && !empty($date_in);
echo "<p>All required fields present: " . ($required_ok ? '✅ YES' : '❌ NO') . "</p>";
if (!$required_ok) {
    echo "<p>Missing: ";
    $missing = [];
    if (empty($firstname)) $missing[] = 'firstname';
    if (empty($lastname)) $missing[] = 'lastname';
    if (empty($email)) $missing[] = 'email';
    if (empty($contact)) $missing[] = 'contact';
    if (empty($house_id)) $missing[] = 'house_id';
    if (empty($date_in)) $missing[] = 'date_in';
    echo implode(', ', $missing) . "</p>";
}
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>2. Email Validation:</h4>";
$email_valid = filter_var($email, FILTER_VALIDATE_EMAIL);
echo "<p>Email '$email' is valid: " . ($email_valid ? '✅ YES' : '❌ NO') . "</p>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>3. Date Format Validation:</h4>";
$date_obj = DateTime::createFromFormat('Y-m-d', $date_in);
$date_valid = $date_obj && $date_obj->format('Y-m-d') === $date_in;
echo "<p>Date '$date_in' format valid: " . ($date_valid ? '✅ YES' : '❌ NO') . "</p>";
if ($date_obj) {
    echo "<p>Parsed as: " . $date_obj->format('Y-m-d H:i:s') . "</p>";
}
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>4. Date Range Validation:</h4>";
if ($date_valid) {
    $reg_date = new DateTime($date_in);
    $current_date = new DateTime(date('Y-m-d'));
    
    $max_future_date = clone $current_date;
    $max_future_date->add(new DateInterval('P1M'));
    
    $min_date = clone $current_date;
    $min_date->sub(new DateInterval('P10Y'));
    
    echo "<p>Registration date: " . $reg_date->format('Y-m-d') . "</p>";
    echo "<p>Current date: " . $current_date->format('Y-m-d') . "</p>";
    echo "<p>Max allowed: " . $max_future_date->format('Y-m-d') . "</p>";
    echo "<p>Min allowed: " . $min_date->format('Y-m-d') . "</p>";
    
    $too_future = $reg_date > $max_future_date;
    $too_old = $reg_date < $min_date;
    
    echo "<p>Too far in future: " . ($too_future ? '❌ YES' : '✅ NO') . "</p>";
    echo "<p>Too old: " . ($too_old ? '❌ YES' : '✅ NO') . "</p>";
    
    $date_range_ok = !$too_future && !$too_old;
    echo "<p>Date range OK: " . ($date_range_ok ? '✅ YES' : '❌ NO') . "</p>";
}
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>5. House ID Validation:</h4>";
$house_check = $conn->prepare("SELECT id FROM houses WHERE id = ?");
$house_check->bind_param("i", $house_id);
$house_check->execute();
$house_check->store_result();
$house_exists = $house_check->num_rows > 0;
echo "<p>House ID $house_id exists: " . ($house_exists ? '✅ YES' : '❌ NO') . "</p>";
$house_check->close();
echo "</div>";

echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
echo "<h4>6. Duplicate Tenant Check:</h4>";
$check_query = "SELECT id FROM tenants WHERE house_id = ? AND status = 1 AND id != ?";
$check_stmt = $conn->prepare($check_query);
$id = 0; // New tenant
$check_stmt->bind_param("ii", $house_id, $id);
$check_stmt->execute();
$check_stmt->store_result();
$has_duplicate = $check_stmt->num_rows > 0;
echo "<p>House already has active tenant: " . ($has_duplicate ? '❌ YES' : '✅ NO') . "</p>";
$check_stmt->close();
echo "</div>";

// Now try the actual save
echo "<h3>💾 Actual Save Result:</h3>";
$save_result = $admin->save_tenant();

$error_codes = [
    0 => 'Missing required fields or general error',
    1 => 'Success ✅',
    2 => 'House already assigned to an active tenant',
    3 => 'Invalid house ID',
    4 => 'Invalid email format',
    8 => 'Invalid date format',
    9 => 'Date more than 1 month in future',
    10 => 'Date more than 10 years ago'
];

echo "<div style='background: " . ($save_result == 1 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border: 1px solid " . ($save_result == 1 ? '#c3e6cb' : '#f5c6cb') . ";'>";
echo "<p><strong>Save Result:</strong> $save_result</p>";
echo "<p><strong>Meaning:</strong> " . ($error_codes[$save_result] ?? "Unknown error code") . "</p>";
echo "</div>";

// Check what was actually saved
echo "<h3>📋 Check Database After Save:</h3>";
$latest = $conn->query("SELECT * FROM tenants WHERE status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($latest) {
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<h4>Latest Tenant in Database:</h4>";
    echo "<p><strong>ID:</strong> {$latest['id']}</p>";
    echo "<p><strong>Name:</strong> {$latest['firstname']} {$latest['lastname']}</p>";
    echo "<p><strong>Email:</strong> {$latest['email']}</p>";
    echo "<p><strong>Date In:</strong> {$latest['date_in']}</p>";
    echo "<p><strong>House ID:</strong> {$latest['house_id']}</p>";
    echo "<p><strong>Status:</strong> {$latest['status']}</p>";
    echo "</div>";
}

// Check for any PHP errors
echo "<h3>🚨 Error Log Check:</h3>";
if (file_exists('error.log')) {
    $errors = file_get_contents('error.log');
    if (!empty($errors)) {
        echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>$errors</pre>";
    } else {
        echo "<p>No errors in error.log</p>";
    }
} else {
    echo "<p>No error.log file found</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
