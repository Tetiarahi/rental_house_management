
<style>
	.collapse a{
		text-indent:10px;
	}
	nav#sidebar{
		/*background: url(assets/uploads/<?php echo $_SESSION['system']['cover_img'] ?>) !important*/
		background: linear-gradient(135deg, #0a0420ff, #07032cff);
        color: #fff;
	}
</style>

<nav id="sidebar" class='mx-lt-5' style="background-color:#343a40;" >
		
		<div class="sidebar-list">
				<a href="index.php?page=home" class="nav-item nav-home"><span class='icon-field'><i class="fa fa-tachometer-alt "></i></span> Dashboard</a>
				<a href="index.php?page=categories" class="nav-item nav-categories"><span class='icon-field'><i class="fa fa-th-list "></i></span> House Type</a>
				<a href="index.php?page=houses" class="nav-item nav-houses"><span class='icon-field'><i class="fa fa-home "></i></span> Houses</a>
				<a href="index.php?page=tenants" class="nav-item nav-tenants"><span class='icon-field'><i class="fa fa-user-friends "></i></span> Tenants</a>
				<a href="index.php?page=invoices" class="nav-item nav-invoices"><span class='icon-field'><i class="fa fa-file-invoice "></i></span> Payments</a>
				<a href="index.php?page=reports" class="nav-item nav-reports"><span class='icon-field'><i class="fa fa-list-alt "></i></span> Reports</a>

				<?php
				// Role-based navigation access control
				// Type 0 = Super-admin (full access)
				// Type 1 = Admin (everything except Email Settings)
				// Type 2 = Staff (basic access only)
				$user_type = $_SESSION['login_type'] ?? 2;
				?>

				<?php if($user_type <= 1): // Admin and Super-admin ?>
				<a href="index.php?page=users" class="nav-item nav-users"><span class='icon-field'><i class="fa fa-users "></i></span> Users</a>
				<a href="index.php?page=email_notifications" class="nav-item nav-email_notifications"><span class='icon-field'><i class="fa fa-bell text-warning"></i></span> Email Notifications</a>
				<?php endif; ?>

				<?php if($user_type == 0): // Super-admin only ?>
				<a href="index.php?page=email_settings" class="nav-item nav-email_settings"><span class='icon-field'><i class="fa fa-envelope text-info"></i></span> Email Settings</a>
				<!-- <a href="index.php?page=site_settings" class="nav-item nav-site_settings"><span class='icon-field'><i class="fa fa-cogs text-danger"></i></span> System Settings</a> -->
				<?php endif; ?>
		</div>

</nav>
<script>
	$('.nav_collapse').click(function(){
		console.log($(this).attr('href'))
		$($(this).attr('href')).collapse()
	})
	$('.nav-<?php echo isset($_GET['page']) ? $_GET['page'] : '' ?>').addClass('active')
</script>
