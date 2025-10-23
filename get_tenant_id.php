<?php
include 'db_connect.php';
$result = $conn->query('SELECT id FROM tenants WHERE status = 1 LIMIT 1');
$row = $result->fetch_assoc();
echo $row['id'];
?>
