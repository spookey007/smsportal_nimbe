<?php
	session_start();
	include_once('../database.php');
	include_once('../functions.php');
	$id  = decode($_REQUEST['id']);
	$sql = "select first_name, last_name from users where id='".$id."'";
	$res = mysqli_query($link,$sql);
	if(mysqli_num_rows($res)){
		$row = mysqli_fetch_assoc($res);
	}else{
		$row = array();
	}
	$_SESSION['rndir'] = 'true';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">
<link rel="shortcut icon" href="images/favi.png">
<title>Nimble Messaging</title>
    <link rel="stylesheet" href="../css/ranksol_hazii.css">

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

<script src="html5shiv.js"></script>
<script src="respond-min.js"></script>
</head>

<body class="fixed-left">

<div id="wrapper" >
<div class="topbar"> 
	<div class="topbar-left">
		<div class="text-center"><span><img src="../images/installer_logo.png"></span></div>
	</div>
</div>

<div class="content-page margin-0-auto">
	<div class="content">
		<div class="container"> 
			<div class="row">
				<div class="col-sm-12">
					<section>
						<div class="container-alt">
						<div class="row">
						<div class="col-sm-12 text-center">
							<div class="home-wrapper">
								<h1 class="home-text text-uppercase"><span class="text-pink">congratulations</span> <span class="text-info"> <?php echo $row['first_name'].' '.$row['last_name']?></span></h1>
								<h4 class="text-muted">Your application has been configured successfully, please click <a href="../index.php">here</a> to login.</h4>
							</div>
						</div>
						</div>
					</div>
					</section>
				</div>
			</div>
		</div>
	</div>
</div>