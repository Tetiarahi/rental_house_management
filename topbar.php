<style>
	.logo {
    margin: auto;
    font-size: 20px;
    background: white;
    padding: 7px 11px;
    border-radius: 50% 50%;
    color: #000000b3;
    }
    .navbar {
      background: linear-gradient(90deg, #060544ff, #1d0850ff) !important; /* Blue gradient */
    }
}
</style>

<nav class="navbar navbar-light fixed-top bg-primary" style="padding:0;min-height: 3.5rem">
  <div class="container-fluid mt-2 mb-2">
  	<div class="col-lg-12">
  		<div class="col-md-1 float-left" style="display: flex;">
  		
  		</div>
      <div class="col-md-4 float-left text-white">
        <large><b><?php echo isset($_SESSION['system']['name']) ? $_SESSION['system']['name'] : '' ?></b></large>
      </div>
	  	<div class="float-right">
        <?php
        // Display user role badge
        $user_type = $_SESSION['login_type'] ?? 2;
        $role_info = [
            0 => ['name' => 'Super-admin', 'color' => 'badge-danger', 'icon' => 'fa-crown'],
            1 => ['name' => 'Admin', 'color' => 'badge-warning', 'icon' => 'fa-user-shield'],
            2 => ['name' => 'Staff', 'color' => 'badge-info', 'icon' => 'fa-user']
        ];
        $current_role = $role_info[$user_type] ?? $role_info[2];
        ?>
        <span class="badge <?php echo $current_role['color']; ?> mr-2" style="font-size: 12px; padding: 5px 8px;">
            <i class="fa <?php echo $current_role['icon']; ?>"></i> <?php echo $current_role['name']; ?>
        </span>

        <div class=" dropdown mr-4">
            <a href="#" class="text-white dropdown-toggle"  id="account_settings" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $_SESSION['login_name'] ?> </a>
              <div class="dropdown-menu" aria-labelledby="account_settings" style="left: -2.5em;">
                <div class="dropdown-header">
                    <small class="text-muted">
                        <i class="fa <?php echo $current_role['icon']; ?>"></i>
                        <?php echo $current_role['name']; ?> User
                    </small>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" id="manage_my_account"><i class="fa fa-cog"></i> Manage Account</a>
                <a class="dropdown-item" href="ajax.php?action=logout"><i class="fa fa-power-off"></i> Logout</a>
              </div>
        </div>
      </div>
  </div>
  
</nav>

<script>
  $('#manage_my_account').click(function(){
    uni_modal("Manage Account","manage_user.php?id=<?php echo $_SESSION['login_id'] ?>&mtype=own")
  })
</script>