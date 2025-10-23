<?php 
include 'db_connect.php'; 
if(isset($_GET['id'])){
$qry = $conn->query("SELECT * FROM tenants where id= ".$_GET['id']);
foreach($qry->fetch_array() as $k => $val){
	$$k=$val;
}
}
?>
<div class="container-fluid">
	<form action="" id="manage-tenant">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div class="row form-group">
			<div class="col-md-4">
				<label for="" class="control-label">Last Name</label>
				<input type="text" class="form-control" name="lastname"  value="<?php echo isset($lastname) ? $lastname :'' ?>" required>
			</div>
			<div class="col-md-4">
				<label for="" class="control-label">First Name</label>
				<input type="text" class="form-control" name="firstname"  value="<?php echo isset($firstname) ? $firstname :'' ?>" required>
			</div>
			<div class="col-md-4">
				<label for="" class="control-label">Middle Name</label>
				<input type="text" class="form-control" name="middlename"  value="<?php echo isset($middlename) ? $middlename :'' ?>">
			</div>
		</div>
		<div class="form-group row">
			<div class="col-md-4">
				<label for="" class="control-label">Email</label>
				<input type="email" class="form-control" name="email"  value="<?php echo isset($email) ? $email :'' ?>" required>
			</div>
			<div class="col-md-4">
				<label for="" class="control-label">Contact #</label>
				<input type="text" class="form-control" name="contact"  value="<?php echo isset($contact) ? $contact :'' ?>" required>
			</div>
			
		</div>
		<div class="form-group row">
			<div class="col-md-4">
				<label for="" class="control-label">House</label>
				<select name="house_id" id="" class="custom-select select2">
					<option value=""></option>
					<?php
					$house = $conn->query("SELECT * FROM houses where id not in (SELECT house_id from tenants where status = 1) ".(isset($house_id)? " or id = $house_id": "" )." ");
					while($row= $house->fetch_assoc()):
					?>
					<option value="<?php echo $row['id'] ?>" <?php echo isset($house_id) && $house_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['house_no'] ?></option>
					<?php endwhile; ?>
				</select>
			</div>
			<div class="col-md-4">
				<label for="" class="control-label">Registration Date</label>
				<input type="date" class="form-control" name="date_in" value="<?php echo (isset($date_in) && $date_in != '0000-00-00' && $date_in != '') ? $date_in : date('Y-m-d'); ?>"
					   min="<?php echo date('Y-m-d', strtotime('-10 years')); ?>"
					   max="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
				<small class="text-muted">Date when tenant moved in (up to 1 month in future allowed)</small>
			</div>
		</div>
		<div class="form-group row">
			<div class="col-md-6">
				<label for="" class="control-label">Contract PDF</label>
				<input type="file" class="form-control" name="contract_file" accept=".pdf" onchange="displayContractName(this)">
				<small class="text-muted">Upload tenant contract in PDF format (Max 20MB)</small>
				<?php if(isset($contract_file) && !empty($contract_file)): ?>
					<div class="mt-2">
						<small class="text-success">Current file: <?php echo basename($contract_file) ?></small>
						<a href="assets/uploads/contracts/<?php echo $contract_file ?>" target="_blank" class="btn btn-sm btn-info ml-2">
							<i class="fa fa-eye"></i> View
						</a>
					</div>
				<?php endif; ?>
			</div>
			<div class="col-md-6" id="contract-preview" style="display: none;">
				<label class="control-label">Selected File</label>
				<div class="alert alert-info" id="contract-filename"></div>
			</div>
		</div>
	</form>
</div>
<script>
	function displayContractName(input) {
		if (input.files && input.files[0]) {
			var file = input.files[0];
			var fileName = file.name;
			var fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB

			// Check file type
			if (file.type !== 'application/pdf') {
				alert('Please select a PDF file only.');
				input.value = '';
				$('#contract-preview').hide();
				return;
			}

			// Check file size (5MB limit)
			if (file.size > 20 * 1024 * 1024) {
				alert('File size must be less than 20MB.');
				input.value = '';
				$('#contract-preview').hide();
				return;
			}

			$('#contract-filename').html('<strong>' + fileName + '</strong><br><small>Size: ' + fileSize + ' MB</small>');
			$('#contract-preview').show();
		} else {
			$('#contract-preview').hide();
		}
	}

	$('#manage-tenant').submit(function(e){
		e.preventDefault()

		// Validate registration date is not in the future
		var regDate = new Date($('input[name="date_in"]').val());
		var today = new Date();
		today.setHours(0, 0, 0, 0); // Reset time to start of day

		if (regDate > today) {
			alert_toast("Registration date cannot be in the future.",'error')
			return false;
		}

		start_load()
		$('#msg').html('')
		$.ajax({
			url:'ajax.php?action=save_tenant',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				console.log("Server response:", resp); // Debug log
				if(resp==1){
					alert_toast("Data successfully saved.",'success')
						setTimeout(function(){
							location.reload()
						},1000)
				} else if(resp==2){
					alert_toast("House is already occupied by another tenant.",'error')
					end_load()
				} else if(resp==3){
					alert_toast("Invalid house selected.",'error')
					end_load()
				} else if(resp==4){
					alert_toast("Invalid email format.",'error')
					end_load()
				} else if(resp==5){
					alert_toast("Please upload a PDF file only.",'error')
					end_load()
				} else if(resp==6){
					alert_toast("File size must be less than 20MB.",'error')
					end_load()
				} else if(resp==7){
					alert_toast("File upload failed. Please try again.",'error')
					end_load()
				} else if(resp==8){
					alert_toast("Invalid registration date format.",'error')
					end_load()
				} else if(resp==9){
					alert_toast("Registration date cannot be in the future.",'error')
					end_load()
				} else {
					alert_toast("An error occurred. Response: " + resp,'error')
					end_load()
				}
			},
			error:function(){
				alert_toast("Connection error. Please try again.",'error')
				end_load()
			}
		})
	})

	// Fallback: Ensure date input always has a valid value
	$(document).ready(function() {
		var dateInput = $('input[name="date_in"]');
		if (dateInput.length > 0) {
			var currentValue = dateInput.val();
			console.log('Date input current value:', currentValue);

			// If value is empty, invalid, or shows "Invalid Date"
			if (!currentValue || currentValue === '' || currentValue === 'Invalid Date') {
				var today = new Date().toISOString().split('T')[0];
				dateInput.val(today);
				console.log('Set date input to current date:', today);
			}
		}
	});
</script>