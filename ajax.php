<?php
ob_start();
$action = $_GET['action'];
include 'admin_class.php';
$crud = new Action();
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'login2'){
	$login = $crud->login2();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'logout2'){
	$logout = $crud->logout2();
	if($logout)
		echo $logout;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save;
}
if($action == 'signup'){
	$save = $crud->signup();
	if($save)
		echo $save;
}
if($action == 'update_account'){
	$save = $crud->update_account();
	if($save)
		echo $save;
}
if($action == "save_settings"){
	$save = $crud->save_settings();
	if($save)
		echo $save;
}
if($action == "save_category"){
	$save = $crud->save_category();
	if($save)
		echo $save;
}

if($action == "delete_category"){
	$delete = $crud->delete_category();
	if($delete)
		echo $delete;
}
if($action == "save_house"){
	$save = $crud->save_house();
	if($save)
		echo $save;
}
if($action == "delete_house"){
	$save = $crud->delete_house();
	if($save)
		echo $save;
}

if($action == "save_tenant"){
	$save = $crud->save_tenant();
	echo $save; // Always echo the response, even if it's 0
}
if($action == "delete_tenant"){
	$save = $crud->delete_tenant();
	if($save)
		echo $save;
}
if($action == "get_tdetails"){
	$get = $crud->get_tdetails();
	if($get)
		echo $get;
}

if($action == "save_payment"){
	$save = $crud->save_payment();
	echo $save; // Always echo the response, even if it's 0
}
if($action == "delete_payment"){
	$save = $crud->delete_payment();
	if($save)
		echo $save;
}
if($action == "get_monthly_payments"){
	echo $crud->get_monthly_payments();
}

if($action == "save_email_settings"){
	echo $crud->save_email_settings();
}

if($action == "send_test_email"){
	require_once 'email_class.php';
	$emailManager = new EmailManager();
	$test_email = $_POST['test_email'] ?? '';

	if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
		echo 0; // Invalid email
	} else {
		$result = $emailManager->sendTestEmail($test_email);
		echo $result ? 1 : 0;
	}
}

if($action == "send_rent_due_notice"){
	require_once 'email_class.php';
	$emailManager = new EmailManager();
	$tenant_id = $_POST['tenant_id'] ?? 0;

	if ($tenant_id > 0) {
		$result = $emailManager->sendRentDueNotice($tenant_id);
		echo $result ? 1 : 0;
	} else {
		echo 0;
	}
}

if($action == "send_payment_reminder"){
	require_once 'email_class.php';
	$emailManager = new EmailManager();
	$tenant_id = $_POST['tenant_id'] ?? 0;

	if ($tenant_id > 0) {
		$result = $emailManager->sendPaymentReminder($tenant_id);
		echo $result ? 1 : 0;
	} else {
		echo 0;
	}
}

if($action == "send_payment_confirmation"){
	require_once 'email_class.php';
	$emailManager = new EmailManager();
	$payment_id = $_POST['payment_id'] ?? 0;

	if ($payment_id > 0) {
		$result = $emailManager->sendPaymentConfirmation($payment_id);
		echo $result ? 1 : 0;
	} else {
		echo 0;
	}
}

ob_end_flush();
?>
