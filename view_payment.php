<?php include 'db_connect.php' ?>

<?php 
$tenants =$conn->query("SELECT t.*,concat(t.lastname,', ',t.firstname,' ',t.middlename) as name,h.house_no,h.price FROM tenants t inner join houses h on h.id = t.house_id where t.id = {$_GET['id']} ");
foreach($tenants->fetch_array() as $k => $v){
	if(!is_numeric($k)){
		$$k = $v;
	}
}
// Calculate months from registration date to current date
// Handle invalid dates gracefully
if (empty($date_in) || $date_in == '0000-00-00' || !DateTime::createFromFormat('Y-m-d', $date_in)) {
    // Invalid registration date - set months to 0 to avoid massive calculations
    $months = 0;
    $payable = 0;
} else {
    $start_date = new DateTime($date_in);
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

        $payable = $price * $months;
    }
}
$paid = $conn->query("SELECT SUM(amount) as paid FROM payments where tenant_id =".$_GET['id']);
$last_payment = $conn->query("SELECT * FROM payments where tenant_id =".$_GET['id']." order by unix_timestamp(date_created) desc limit 1");
$paid = $paid->num_rows > 0 ? $paid->fetch_array()['paid'] : 0;
$last_payment = $last_payment->num_rows > 0 ? date("M d, Y",strtotime($last_payment->fetch_array()['date_created'])) : 'N/A';
$outstanding = $payable - $paid;

?>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="row">
			<div class="col-md-4">
				<div id="details">
					<large><b>Details</b></large>
					<hr>
					<p>Tenant: <b><?php echo ucwords($name) ?></b></p>
					<p>Monthly Rental Rate: <b><?php echo number_format($price,2) ?></b></p>
					<p>Outstanding Balance: <b><?php echo number_format($outstanding,2) ?></b></p>
					<p>Total Paid: <b><?php echo number_format($paid,2) ?></b></p>
					<p>Rent Started: <b><?php
						// Robust date formatting
						if (empty($date_in) || $date_in == '0000-00-00') {
							echo "Invalid Date";
						} else {
							// Clean the date string
							$date_in = trim($date_in);

							// Try DateTime class first (more reliable)
							try {
								$date = DateTime::createFromFormat('Y-m-d', $date_in);
								if ($date && $date->format('Y-m-d') === $date_in) {
									echo $date->format('M d, Y');
								} else {
									throw new Exception('Invalid format');
								}
							} catch (Exception $e) {
								// Fallback to strtotime
								$timestamp = strtotime($date_in);
								if ($timestamp !== false) {
									echo date("M d, Y", $timestamp);
								} else {
									echo "Invalid Date";
								}
							}
						}
					?></b></p>
					<p>Payable Months: <b><?php echo $months ?></b></p>
				</div>
			</div>
			<div class="col-md-8">
				<large><b>Payment List</b></large>
					<hr>
				<table class="table table-condensed table-striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Invoice</th>
							<th>Reference #</th>
							<th>Amount</th>

						</tr>
					</thead>
					<tbody>
						<?php
						$payments = $conn->query("SELECT * FROM payments where tenant_id = $id ORDER BY date_created DESC");
						if($payments->num_rows > 0):
						while($row=$payments->fetch_assoc()):
						?>
					<tr>
						<td><?php echo date("M d, Y",strtotime($row['date_created'])) ?></td>
						<td><?php echo $row['invoice'] ?></td>
						<td><?php echo !empty($row['ref_number']) ? $row['ref_number'] : '<span class="text-muted">-</span>' ?></td>
						<td class='text-right'><?php echo number_format($row['amount'],2) ?></td>

					</tr>
					<?php endwhile; ?>
					<?php else: ?>
					<tr>
						<td colspan="5" class="text-center text-muted">No payments found</td>
					</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<style>
	#details p {
		margin: unset;
		padding: unset;
		line-height: 1.3em;
	}
	td, th{
		padding: 3px !important;
	}
</style>

<script>

</script>