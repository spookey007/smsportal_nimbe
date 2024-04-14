<style>
	@media (max-width: 991px){
		.navbar-nav .open .dropdown-menu > li > a {
			padding: 10px 15px 10px 15px;
			border: 1px solid #CCC !important
		}
	}
</style>
<?php
include_once'database.php';
$get_user_gateway = mysqli_query($link,"select sms_gateway from application_settings where user_id=".$_SESSION['user_id']);
$pageName = getCurrentPageName();
	if($pageName!='edit_app_user.php'){
		if($_SESSION['user_type']=='1'){
			if(trim($appSettings['sms_gateway'])==''){
			?>
<div class="alert alert-danger"><span><b> Warning - </b> Application settings are not configured, please configure sms gateway settings <a href="settings.php" class="white"><b>here</b></a>.</span></div>
<?php			}
		}
		else if($_SESSION['user_type']=='2'){
			if(mysqli_num_rows($get_user_gateway) == 0){ ?>
			<div class="alert alert-danger"><span><b> Warning - </b>Application settings are not configured,</div>
	<?php		}
		}
	}
?>
<nav class="navbar navbar-default navbar-fixed">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navigation-example-2"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
			<a class="navbar-brand" href="javascript:void(0)"><?php echo DBout($business_name);?></a> </div>
		<div class="collapse navbar-collapse">
			<ul class="nav navbar-nav navbar-right">
				<?php 
                if($pkgStatus['go']==false){
                ?>
				<li>
                    
                    <a href="javascript:void(0)">
					   <p class="pkg_status"><?php echo DBout($pkgStatus['message'])?></p>
					</a>
                </li>
				<?php }?>
				<li id="googletranslateelement">
					<div id="google_translate_element"></div>
				</li>
				<?php
				//if($_SESSION['user_type']=='1'){
				$server_name =  $_SERVER['HTTP_HOST'];
				$server_name = str_replace("www.","",$server_name);
				if($server_name!="herbert.securedserverspace.com"){
				?>
                <!--<li> <a href="https://codecanyon.net/item/nimble-messaging-business-mobile-sms-marketing-application-for-android/20956083" target="_blank"><i class="fa fa-android" aria-hidden="true"></i>&nbsp;Get Mobile App</a> </li>-->
				<li> <a href="server.php?cmd=download_apk" target="_blank"><i class="fa fa-android" aria-hidden="true"></i>&nbsp;Get Mobile App</a> </li>
                <?php 
                    }
                //}
                 ?>
				<li> <a href="help.php" target="_blank"><i class="fa fa-life-ring" aria-hidden="true"></i>&nbsp;Help</a> </li>
				<?php
				if($_SESSION['user_type']=='1'){
					if($displayUpdate=='none'){
				?>
				<li>
				<?php 
					if(trim($appVersion)!='')
						$appVersion = 'v'.$appVersion;
					?>
                    <a href="javascript:void(0)"><p><?php echo DBout($appVersion)?></p></a>

				</li>
				<?php }else{?>
				<li> <a href="update_app.php" class="btn btn-danger">Update to <?php echo DBout($latestVersion)?></a> </li>
				<?php }

				}
				?>
				<li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown">
					<p><span class="pe-7s-bell font-20"></span>&nbsp;<b class="caret"></b> </p>
					</a>
					<ul class="dropdown-menu">
						<?php 
						if($_SESSION['user_type']=='1'){
							$sqlcrd = sprintf("select used_sms_credits from users where id='%s'",
                                            mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
                                );
							$rescrd = mysqli_query($link,$sqlcrd);
							$rowAdmin = mysqli_fetch_assoc($rescrd);
					?>
						<li> <a href="javascript:void(0);" class="padding-left-15">
							<div class="media">
								<div class="pull-left p-r-10"> <b>Unlimited Plan</b></div>
								<div class="media-body">
									<h5 class="media-heading"> <span class="badge nav_admin">Admin</span> </h5>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="padding-left-10 padding-right-10">
							<div class="media padding-left-5 padding-right-5">
								<div class="media-body">
									<h5 class="media-heading"> SMS Credits: <span class="move-right"><?php echo DBout($rowAdmin['used_sms_credits'].'/Ultd')?></span> </h5>
									<p class="m-0 margin-left-0"> <small>Remaining sms credits <span class="text-primary font-600 move-right">Ultd</span>.</small></p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="padding-left-10 padding-right-10">
							<div class="media padding-left-5 padding-right-5">
								<div class="media-body">
									<h5 class="media-heading"> Phone Numbers: <span class="margin-left-10"><?php echo DBout(checkUserNumberslimit($_SESSION['user_id']).'/Ultd')?></span> </h5>
									<p class="m-0 margin-left-0"> <small>Remaining numbers are <span class="text-primary font-600">Ultd</span>.</small> </p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="padding-left-10 padding-right-10">
							<div class="media padding-left-5 padding-right-5">
								<div class="media-body">
									<h5 class="media-heading">Unlimited Plan</h5>
									<p class="m-0 margin-left-0"> <small> You can buy any number.</small> </p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="padding-left-10 padding-right-10">
							<div class="media padding-right-5 padding-left-5">
								<div class="media-body">
									<h5 class="media-heading">Status Active</h5>
									<p class="m-0 margin-left-0"> <small>Your plan status is currently <span class="text-primary font-600">Active</span>.</small> </p>
								</div>
							</div>
							</a> </li>
						<?php 
						}else{
							$userPackage = getAssingnedPackageInfo($_SESSION['user_id']);
							$pkgTitle 	 = getPackageInfo($userPackage['pkg_id']);	
					?>
						<li>
							<a href="javascript:void(0)" class="padding-left-10 padding-right-10"> <?php echo DBout($pkgTitle['title'])?><br />
							<span class="showUserPkgDtls padding-5 margin-bottom-5"><?php echo 'Expires: '.date('M-d-y H:i a',strtotime($userPackage['end_date']));?></span>
							</a>
						</li>
						<li>
							<a href="javascript:void(0);" class="padding-right-10 padding-left-10 cursor-default">
							<div class="media">
								<div class="media-body">
									<h5 class="media-heading"> SMS Credits: &nbsp;&nbsp;&nbsp;&nbsp;<?php echo DBout($userPackage['used_sms_credits']).'/'.DBout($userPackage['sms_credits'])?> </h5>
									<p class="m-0 margin-left-0"> <small>Remaining sms credits are <span class="text-primary font-600"><?php echo DBout($userPackage['sms_credits']-$userPackage['used_sms_credits'])?></span></small> </p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="cursor-default padding-left-10 padding-right-10">
							<div class="media"> 
								<div class="media-body">
									<h5 class="media-heading"> Phone Numbers: <span class="margin-left-20"><?php echo DBout(checkUserNumberslimit($_SESSION['user_id']).'/'.$userPackage['phone_number_limit'])?></span> </h5>
									<p class="m-0 margin-left-0"> <small>Remaining numbers are <span class="text-primary font-600"><?php echo DBout($userPackage['phone_number_limit']-checkUserNumberslimit($_SESSION['user_id']))?></span></small> </p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="cursor-default padding-left-10 padding-right-10">
							<div class="media"> 
								<div class="media-body">
									<?php
										$pkgCountry = $userPackage['pkg_country'];
									?>
									<h5 class="media-heading"><?php echo DBout($pkgCountry)?> Plan</h5>
									<p class="m-0 margin-left-0"> <small>Can only buy <span class="text-primary font-600"><?php echo DBout($pkgCountry)?></span> numbers.</small> </p>
								</div>
							</div>
							</a> </li>
						<li> <a href="javascript:void(0);" class="cursor-default padding-right-10 padding-left-10">
							<div class="media"> 
								<div class="media-body">
									<?php
										if($userPackage['status']=='1')
											$status = 'Active';
										else
											$status = 'Suspended';
									?>
									<h5 class="media-heading">Status <?php echo DBout($status)?></h5>
									<p class="m-0 margin-left-0"> <small>Plan status is currently <span class="text-primary font-600"><?php echo DBout($status)?></span>.</small> </p>
								</div>
							</div>
							</a> </li>
						<li><a href="pricing_plans.php?uid=<?php echo DBout(encode($_SESSION['user_id']))?>" target="_blank">
							<div class="media"> 
								<div class="media-body"> Upgrade Package </div>
							</div>
							</a> </li>
						<?php }?>
					</ul>
				</li>
				<li class="dropdown"> <a href="#" class="dropdown-toggle" data-toggle="dropdown">
					<p> <?php echo DBout($_SESSION['first_name']).' '.DBout($_SESSION['last_name']);?> <b class="caret"></b> </p>
					</a>
					<ul class="dropdown-menu">
						<li><a href="profile.php"><i class="ti-user m-r-5"></i> Profile</a></li>
						<li><a href="settings.php"><i class="ti-settings m-r-5"></i> Settings</a></li>
						<li class="separator"></li>
						<li><a href="server.php?cmd=logout">Log out</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</nav>