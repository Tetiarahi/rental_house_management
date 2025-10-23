<?php
include 'db_connect.php';

echo "<h2>Current Registration Dates</h2>";

$result = $conn->query("SELECT id, firstname, lastname, date_in FROM tenants WHERE status = 1");

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th style='padding: 8px;'>ID</th><th style='padding: 8px;'>Name</th><th style='padding: 8px;'>Date In</th><th style='padding: 8px;'>Formatted Date</th><th style='padding: 8px;'>Status</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        $date_in = $row['date_in'];
        $name = $row['firstname'] . ' ' . $row['lastname'];
        
        // Test the date formatting
        if (empty($date_in) || $date_in == '0000-00-00') {
            $formatted_date = "Invalid Date";
            $status = "❌ Invalid";
        } else {
            $timestamp = strtotime($date_in);
            if ($timestamp === false) {
                $formatted_date = "Parse Error";
                $status = "❌ Parse Error";
            } else {
                $formatted_date = date("M d, Y", $timestamp);
                $status = "✅ Valid";
            }
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$row['id']}</td>";
        echo "<td style='padding: 8px;'>{$name}</td>";
        echo "<td style='padding: 8px;'>{$date_in}</td>";
        echo "<td style='padding: 8px;'>{$formatted_date}</td>";
        echo "<td style='padding: 8px;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No active tenants found.</p>";
}
?>
