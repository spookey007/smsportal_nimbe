<?php include_once "../functions.php"; ?>
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
<div id="wrapper padding-top-5-per">
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
					<div class="card-boxs">
						<div class="row">
							<div class="col-lg-12">
								<h4 class="m-t-0 header-title"><b>Please provide database information below</b></h4>
	<p class="text-muted font-13">
		Provide your already created database information below.
	</p>
	<?php
		if(trim($_REQUEST['message'])!=''){
			echo $_REQUEST['message'];
			$_REQUEST['message']='';
		}
	?>
	<div class="p-20">
		<form method="post" enctype="multipart/form-data" action="../server.php">
			<div class="form-group">
				<label>Host Name</label>
				<input type="text" name="hostname" class="form-control" required>
			</div>
			<div class="form-group">
				<label>Database Name</label>
				<input type="text" name="dbname" class="form-control" required>
			</div>
			<div class="form-group">
				<label>User Name</label>
				<input type="text" name="username" class="form-control" required>
			</div>
			<div class="form-group">
				<label>Password</label>
				<input type="text" name="password" class="form-control">
			</div>
			<div class="form-group text-right m-b-0">
				<img src="../images/busy.gif" id="loading" class="display-none">
				<span id="showResMsg"></span>
				<input type="hidden" name="cmd" value="save_installer_db_info" />
				<button class="btn btn-success waves-effect waves-light" type="submit"> Save & Next </button>
				<button type="button" class="btn btn-info waves-effect waves-light m-l-5" id="checkDBConnection"> Check Connection </button>
			</div>
		</form>
	</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<footer class="footer">
		Powered by <a href="http://ranksol.com" target="_blank">ranksol.com</a> copyright@2017
	</footer>
</div>
<script src="../assets/js/jquery-1.10.2.js"></script>
    <script src="installer.js"></script>