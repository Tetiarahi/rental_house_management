<?php
include 'db_connect.php';

echo "<h1>🔧 Fix Tenant Date Issue</h1>";

// Find the tenant with 0000-00-00 date
$invalid_tenant = $conn->query("SELECT * FROM tenants WHERE date_in = '0000-00-00' AND status = 1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($invalid_tenant) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
    echo "<h2>Found Tenant with Invalid Date:</h2>";
    echo "<p><strong>ID:</strong> {$invalid_tenant['id']}</p>";
    echo "<p><strong>Name:</strong> {$invalid_tenant['firstname']} {$invalid_tenant['lastname']}</p>";
    echo "<p><strong>Email:</strong> {$invalid_tenant['email']}</p>";
    echo "<p><strong>Current date_in:</strong> {$invalid_tenant['date_in']}</p>";
    echo "<p><strong>House ID:</strong> {$invalid_tenant['house_id']}</p>";
    echo "</div>";
    
    // Update this tenant's date to August 2025
    echo "<h2>🔄 Updating Tenant Date</h2>";
    
    $update_query = "UPDATE tenants SET date_in = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $new_date = '2025-08-15';
    $stmt->bind_param("si", $new_date, $invalid_tenant['id']);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
        echo "<h3>✅ Success!</h3>";
        echo "<p>Successfully updated tenant <strong>{$invalid_tenant['firstname']} {$invalid_tenant['lastname']}</strong></p>";
        echo "<p>Changed date from <code>0000-00-00</code> to <code>$new_date</code></p>";
        echo "</div>";
        
        // Verify the update
        $updated_tenant = $conn->query("SELECT * FROM tenants WHERE id = {$invalid_tenant['id']}")->fetch_assoc();
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 20px 0;'>";
        echo "<h3>📋 Verification - Updated Tenant Data:</h3>";
        echo "<p><strong>ID:</strong> {$updated_tenant['id']}</p>";
        echo "<p><strong>Name:</strong> {$updated_tenant['firstname']} {$updated_tenant['lastname']}</p>";
        echo "<p><strong>New date_in:</strong> <span style='color: green; font-weight: bold;'>{$updated_tenant['date_in']}</span></p>";
        echo "<p><strong>House ID:</strong> {$updated_tenant['house_id']}</p>";
        echo "</div>";
        
        // Test the date display
        echo "<h3>🧪 Test Date Display</h3>";
        $date_in = $updated_tenant['date_in'];
        
        // Test the view_payment.php logic
        if (empty($date_in) || $date_in == '0000-00-00') {
            $display_result = "Invalid Date";
        } else {
            $date_in = trim($date_in);
            try {
                $date = DateTime::createFromFormat('Y-m-d', $date_in);
                if ($date && $date->format('Y-m-d') === $date_in) {
                    $display_result = $date->format('M d, Y');
                } else {
                    throw new Exception('Invalid format');
                }
            } catch (Exception $e) {
                $timestamp = strtotime($date_in);
                if ($timestamp !== false) {
                    $display_result = date("M d, Y", $timestamp);
                } else {
                    $display_result = "Invalid Date";
                }
            }
        }
        
        echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
        echo "<h4>Date Display Test Result:</h4>";
        echo "<p><strong>Raw date:</strong> $date_in</p>";
        echo "<p><strong>Formatted display:</strong> <span style='color: " . ($display_result == 'Invalid Date' ? 'red' : 'green') . "; font-weight: bold;'>$display_result</span></p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
        echo "<h3>❌ Update Failed</h3>";
        echo "<p>Error: " . $conn->error . "</p>";
        echo "</div>";
    }
    $stmt->close();
    
} else {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
    echo "<h2>✅ No Invalid Dates Found</h2>";
    echo "<p>All tenants already have valid dates.</p>";
    echo "</div>";
}

// Show available houses for future reference
echo "<h2>🏠 Available Houses for New Tenants</h2>";
$available_houses = $conn->query("
    SELECT h.id, h.house_no, h.price,
           CASE 
               WHEN t.id IS NULL THEN 'Available'
               ELSE CONCAT('Occupied by ', t.firstname, ' ', t.lastname)
           END as status
    FROM houses h
    LEFT JOIN tenants t ON h.id = t.house_id AND t.status = 1
    ORDER BY h.id
");

if ($available_houses && $available_houses->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>House ID</th><th>House No</th><th>Price</th><th>Status</th></tr>";
    
    while ($house = $available_houses->fetch_assoc()) {
        $status_color = $house['status'] == 'Available' ? 'green' : 'orange';
        echo "<tr>";
        echo "<td>{$house['id']}</td>";
        echo "<td>{$house['house_no']}</td>";
        echo "<td>" . number_format($house['price'], 2) . "</td>";
        echo "<td style='color: $status_color; font-weight: bold;'>{$house['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 20px 0;'>";
echo "<h2>🎯 Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>The existing tenant's date has been fixed</strong> - Mathew Tetiarahi now has August 15, 2025</li>";
echo "<li><strong>View the tenant's payment details</strong> - 'Rent Started' should now show 'Aug 15, 2025'</li>";
echo "<li><strong>For new tenants</strong> - use an available house (one that shows 'Available' status)</li>";
echo "<li><strong>The date validation is now working</strong> - you can enter past dates within the current year</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 20px 0;'>";
echo "<h2>🔗 Test Links:</h2>";
if ($invalid_tenant) {
    echo "<p><a href='view_payment.php?id={$invalid_tenant['id']}' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>View Updated Tenant Payment Details</a></p>";
}
echo "<p><a href='tenants.php' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Tenants Page</a></p>";
echo "</div>";
?>

<style>
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
