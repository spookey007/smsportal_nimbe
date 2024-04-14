<?php
	$pageName = $pageName;
	if(trim($adminSettings['sidebar_color'])=='')
		$sidebarColor = DBout('purple');
	else
		$sidebarColor = DBout($adminSettings['sidebar_color']);
        
    $bg = "";
    if($sidebarColor=="#1A4180"){
        $bg = DBout("#1A4180");
    }
?>
<div class="sidebar" data-color="<?php echo DBout($sidebarColor)?>" data-image="">
	<div class="sidebar-wrapper" style="background:<?php echo DBout($bg);?> ">
		<div class="logo">
			<a href="dashboard.php" class="simple-text">
				<?php if(trim($adminSettings['app_logo'])==''){?>
				<img src="images/nimble_messaging.png" />
				<?php }else{?>
				<img src="images/<?php echo $adminSettings['app_logo']; ?>">
				<?php }?>
			</a>
		</div>
		<ul class="nav">
			<li class="<?php if(($pageName=='dashboard.php'))echo DBout('active');?>"> <a href="dashboard.php"> <i class="pe-7s-graph"></i>
				<p>Dashboard</p>
				</a> </li>
            <li class="<?php if( ($pageName=='view_subscribers.php') || ($pageName=='edit_subscribers.php') || ($pageName=='add_subscribers.php')) echo DBout('active');?>"> <a href="view_subscribers.php"> <i class="pe-7s-users"></i>
				<p>Subscribers</p>
				</a> </li>
            <?php if($_SESSION['user_type']!='1'){?>

            <li class="<?php if(($pageName=='default_campaigns.php') || ($pageName=='default_campaigns.php') || ($pageName=='default_campaigns.php'))echo DBout('active');?>"> <a href="default_campaigns.php"> <i class="fa fa-download"></i>
				<p>Default Campaigns</p>
				</a> </li>

            <?php } ?>
            <li class="<?php if(($pageName=='view_campaigns.php') || ($pageName=='add_campaign.php') || ($pageName=='edit_campaign.php'))echo DBout('active');?>"> <a href="view_campaigns.php"> <i class="pe-7s-note2"></i>
				<p>Campaigns</p>
				</a> </li>

            <li class="<?php if(($pageName=='view_autores.php') || ($pageName=='add_autores.php') || ($pageName=='edit_autores.php'))echo DBout('active');?>"> <a href="view_autores.php"> <i class="pe-7s-paper-plane"></i>
				<p>Autoresponders</p>
				</a> </li>
            <li class="<?php if(($pageName=='trivias.php') || ($pageName=='add_trivia.php'))echo DBout('active');?>"> <a href="trivias.php"> <i class="fa fa-signal"></i>
				<p>Trivia</p>
				</a> </li>
            <li class="<?php if(($pageName=='virals.php') || ($pageName=='add_viral.php'))echo DBout('active');?>"> <a href="virals.php"> <i class="fa fa-sitemap"></i>
				<p>Virals</p>
				</a>
            </li>
            <li class="<?php if(($pageName=='contest.php') || ($pageName=='add_contest.php'))echo DBout('active');?>"> <a href="contest.php"> <i class="fa fa-trophy"></i>
				<p>Contest</p>
				</a>
            </li>
            <li class="<?php if(($pageName=='view_apts.php') || ($pageName=='add_apts.php'))echo DBout('active');?>"> <a href="view_apts.php"> <i class="pe-7s-id"></i>
                    <p>Appointments</p>
                </a>
            </li>
			
			
			
			
			<li class="<?php if(($pageName=='view_webform.php') || ($pageName=='add_webform.php') || ($pageName=='edit_webform.php'))echo DBout('active');?>"> <a href="view_webform.php"> <i class="pe-7s-news-paper"></i>
				<p>Webforms</p>
				</a> </li>
			
			<li class="<?php if( ($pageName=='bulk_sms.php') || ($pageName=='edit_bulk_sms.php')) echo DBout('active');?>"> <a href="bulk_sms.php"> <i class="pe-7s-loop"></i>
				<p>Bulk SMS</p>
				</a> </li>
			<li class="<?php if( ($pageName=='view_scheduler.php') || ($pageName=='edit_scheduler.php') || ($pageName=='scheduler.php') || ($pageName=='view_scheduler2.php')) echo DBout('active');?>"> <a href="view_scheduler2.php"> <i class="fa fa-calendar"></i>
				<p>Scheduler</p>
				</a> </li>
			
			<li class="<?php if($pageName=='sms_report.php') echo DBout('active');?>"> <a href="sms_report.php"> <i class="pe-7s-credit"></i>
				<p>SMS Report</p>
				</a> </li>	
			<?php if($_SESSION['user_type']=='1'){?>
			
			<li class="<?php if( ($pageName=='view_package.php') || ($pageName=='add_package.php') || ($pageName=='edit_pkg.php')) echo DBout('active');?>"> <a href="view_package.php"> <i class="pe-7s-cash"></i>
				<p>Pricing Plans</p>
				</a> </li>
			
			<li class="<?php if( ($pageName=='view_user.php') || ($pageName=='add_app_user.php') || ($pageName=='edit_app_user.php')) echo DBout('active');?>"> <a href="view_user.php"> <i class="pe-7s-user"></i>
				<p>Application Users</p>
				</a> </li>
			<?php }?>	
			<li class="<?php if($pageName=='payment_history.php') echo DBout('active');?>"> <a href="payment_history.php"> <i class="pe-7s-wallet"></i>
				<p>Payment History</p>
				</a> </li>
			<li class="<?php if($pageName=='settings.php') echo DBout('active');?>"> <a href="settings.php"> <i class="pe-7s-tools"></i>
				<p>Settings</p>
				</a> </li>
			<?php if($_SESSION['user_type']=='1'){?>
			<li class="<?php if($pageName=='videos.php') echo DBout('active');?>"> <a href="videos.php"> <i class="pe-7s-monitor"></i>
				<p>Tutorials</p>
				</a> </li>
            <?php }else{ 
                if($_SERVER['HTTP_HOST']=="app.smbmessenger.com"){
                ?>
                <li class="<?php if($pageName=='help.php') echo DBout('active');?>"> <a href="help.php"> <i class="pe-7s-monitor"></i>
				<p>Help</p>
				</a> </li>
            <?php }
            }
            ?>
			
		</ul>
	</div>
</div>
