<?php

include_once "functions.php";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="images/favi.png">
<title>Nimble Messaging</title>
<link href="scripts/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="scripts/css/responsive.css" rel="stylesheet" type="text/css" />
<link href="scripts/css/core.css" rel="stylesheet" type="text/css" />
<link href="scripts/css/pages.css" rel="stylesheet" type="text/css" />
<link href="scripts/css/components.css" rel="stylesheet" type="text/css" />
<link href="scripts/css/icons.css" rel="stylesheet" type="text/css" />
<link href="css/font-awesome-min.css" rel="stylesheet">
<link href='css/font-roboto.css' rel='stylesheet' type='text/css'>
<link href="assets/css/pe-icon-7-stroke.css" rel="stylesheet" />
</head>
<body>
<hr>
<div class="container">
    <div class="row">
        <div class="row">  
            <div class="col-md-4 col-md-offset-4">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <?php
                      session_start();
                      if((isset($_SESSION['message'])) && (trim($_SESSION['message'])!='')){
                        echo ($_SESSION['message']);
                        unset($_SESSION['message']);
                      }
                    ?>
                  </div>
                    <div class="panel-body">                      
                        <div class="text-center">
                          <h3><i class="purple" " class="fa fa-lock fa-4x"></i></h3>
                          <h2 class="text-center">Forgot Password?</h2>
                          <p>You can reset your password here.</p>
                            <div class="panel-body">
                              
                              <form class="form" action="server.php?cmd=forgot_pass" method="post">
                                <fieldset>
                                  <div class="form-group">
                                    <div class="input-group">
                                      <span class="input-group-addon"><i class="glyphicon glyphicon-envelope color-blue purple"></i></span>
                                      
                                      <input id="email" name="email" placeholder="email address" class="form-control" type="email" autocomplete="off" oninvalid="setCustomValidity('Please enter a valid email address!')" onchange="try{setCustomValidity('')}catch(e){}" required="">
                                    </div>
                                  </div>
                                  <div class="form-group btn_res_color">
                                    <input class="btn btn-purple btn-block text-uppercase waves-effect waves-light" value="Send My Password" type="submit" />
                                    <hr>
                                    <a href="index.php" class="btn btn-purple btn-block text-uppercase waves-effect waves-light">
                                      <span class="glyphicon glyphicon-arrow-left"></span> Back
                                    </a>
                                  </div>
                                </fieldset>
                              </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>