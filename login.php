<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
ob_start();
if(!isset($_SESSION['system'])){
	$system = $conn->query("SELECT * FROM system_settings limit 1")->fetch_array();
	foreach($system as $k => $v){
		$_SESSION['system'][$k] = $v;
	}
}
ob_end_flush();
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?php echo $_SESSION['system']['name'] ?></title>
 	

<?php include('./header.php'); ?>
<?php 
if(isset($_SESSION['login_id']))
header("location:index.php?page=home");

?>

</head>
<style>
	body{
		width: 100%;
	    height: calc(100%);
	    /*background: #007bff;*/
	}
	main#main{
		width:100%;
		height: calc(100%);
		background:white;
	}
	
	#login-right{
		position: absolute;
		right:0;
		width:40%;
		height: calc(100%);
		background:white;
		display: flex;
		align-items: center;
		background: linear-gradient(135deg, #fffdffff, #230de2ff);
	}
	#login-left{
		position: absolute;
		left: 0;
		width: 60%;
		height: calc(100%);
		
		/* Simple gradient background */
		background: linear-gradient(135deg, #f2f2f5ff, #140d7aff);
		
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;

		color: #fff;
		padding: 40px;
	}
	#login-left h1 {
		font-size: 36px;
		font-weight: bold;
		margin-bottom: 15px;
	}

	#login-left p {
		font-size: 18px;
		opacity: 0.9;
	}
	#login-right .card{
		margin: auto;
		z-index: 1;
		background: linear-gradient(135deg, #020141ff, #0c0755ff);
		border-radius: 15px; /* Rounded edges */
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Shadow */
		padding: 30px;
	}
	.system-name{
		font-size: 28px;
		font-weight: bold;
		text-align: center;
		margin-bottom: 20px;
		background: linear-gradient(45deg, #1b1b1dff, #071d80ff);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		}
	.logo {
    margin: auto;
    font-size: 8rem;
    background: white;
    padding: .5em 0.7em;
    border-radius: 50% 50%;
    color: #000000b3;
    z-index: 10;
}
div#login-right::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: calc(100%);
    height: calc(100%);
    /*background: #000000e0;*/
}

</style>

<body>


  <main id="main" class=" bg-light">
  		<div id="login-left">
			<div>
				<h1>Welcome to <?php echo $_SESSION['system']['name'] ?></h1>
				<p>Broadcasting and Publications Authority rental management system</p>
			</div>
  		</div>

  		<div id="login-right" class="bg-light">
  			<div class="w-100">
			<h4 class="system-name"><b><?php echo $_SESSION['system']['name'] ?></b></h4>
			<br>
			<br>
  			<div class="card col-md-8">
  				<div class="card-body">
  					<form id="login-form" >
  						<div class="form-group">
  							<label for="username" class="control-label">Username</label>
  							<input type="text" id="username" name="username" class="form-control">
  						</div>
  						<div class="form-group">
  							<label for="password" class="control-label">Password</label>
  							<input type="password" id="password" name="password" class="form-control">
  						</div>
  						<center><button class="btn-sm btn-block btn-wave col-md-4 btn-primary">Login</button></center>
  					</form>
  				</div>
  			</div>
  			</div>
  		</div>
   

  </main>

  <a href="#" class="back-to-top"><i class="icofont-simple-up"></i></a>


</body>
<script>
	$('#login-form').submit(function(e){
		e.preventDefault()
		$('#login-form button[type="button"]').attr('disabled',true).html('Logging in...');
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		$.ajax({
			url:'ajax.php?action=login',
			method:'POST',
			data:$(this).serialize(),
			error:err=>{
				console.log(err)
		$('#login-form button[type="button"]').removeAttr('disabled').html('Login');

			},
			success:function(resp){
				if(resp == 1){
					location.href ='index.php?page=home';
				}else{
					$('#login-form').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>')
					$('#login-form button[type="button"]').removeAttr('disabled').html('Login');
				}
			}
		})
	})
</script>	
</html>