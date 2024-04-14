<?php
    include_once("header.php");
    include_once("left_menu.php");
    $id = '';
    $first_name = '';
    $phone_number = '';
    $email = '';
    $city = '';
    $state = '';

    if(isset($_REQUEST['id'])!=''){
        $sql = sprintf("select * from subscribers where id=%s ",
                mysqli_real_escape_string($link,DBin($_REQUEST['id']))
            );
        $res = mysqli_query($link,$sql);
        if(mysqli_num_rows($res)){
            $row = mysqli_fetch_assoc($res);

            $first_name = $row['first_name'];
            $phone_number = $row['phone_number'];
            $email = $row['email'];
            $city = $row['city'];
            $state = $row['state'];
            $sel = sprintf("select id,group_id from subscribers_group_assignment where subscriber_id=%s ",
                    mysqli_real_escape_string($link,filterVar($row['id']))
                );
            $exe = mysqli_query($link,$sel);
            $rec = mysqli_fetch_assoc($exe);
            $groupID = DBout($rec['group_id']);
            $assignID= DBout($rec['id']);
            $cmd = DBout('update_subscriber');
            $buttonText = DBout('Update');
        }else
            $row = array();
    }else{
        $row = array(); 
        $cmd = DBout('add_subscriber');
        $buttonText = DBout('Save');
    }
?>
<div class="main-panel">
    <?php include_once('navbar.php');?>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="header">
                            <h4 class="title">
                                Add Subscriber
                                <input type="button" class="btn btn-primary move-right" value="Back" onclick="window.location='view_subscribers.php'" />
                            </h4>
                            <p class="category">Add subscribers to your campaigns here.</p>
                        </div>
                        <div class="content table-responsive">
                            <div class="col-sm-12 col-md-12 col-lg-12 col-xs-12 padding-right-0">
                                <a href="server.php?cmd=download_sample_csv" class="btn btn-primary move-right">Sample CSV</a>&nbsp;
                                <a href="#importSubs" data-toggle="modal" class="btn btn-primary move-right margin-right-5">Upload CSV</a>
                                <a href="#exportSubs" data-toggle="modal" class="btn btn-primary move-right margin-right-5">Export Subscribers</a>
                            </div>
                            <form action="server.php" data-parsley-validate enctype="multipart/form-data" method="post">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="first_name" placeholder="Enter name..." class="form-control" value="<?php 
                                echo $first_name;   
                                ?>" required>

                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number" placeholder="Enter phone number..." class="form-control phoneOnly" value="<?php echo $phone_number?>" required maxlength="13">
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="Enter Email Address..." class="form-control" value="<?php echo $email ?>">
                            </div>
                            <div class="form-group">
                                <label>Group (Campaign)</label>
                                <select name="group_id" class="form-control" parsley-trigger="change" required>
                                <?php
                                    $sqlg = sprintf("select id, title from campaigns where user_id=%s ",
                                            mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                        );
                                    $resg = mysqli_query($link,$sqlg);
                                    if(mysqli_num_rows($resg)){
                                        while($rowg = mysqli_fetch_assoc($resg)){
                                            if($rowg['id']==$groupID)
                                                $sele = DBout('selected="selected"');
                                            else
                                                $sele = DBout(''); ?>

                                            <option <?php echo DBout($sele)?> value="<?php echo DBout($rowg['id'])?>"><?php echo DBout($rowg['title']) ?></option>
                                <?php       }
                                    }
                                ?>  
                                </select>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" placeholder="Enter city..." class="form-control" value="<?php echo $city?>">
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" placeholder="Enter state ..." class="form-control" value="<?php echo $state?>">
                            </div>
                            <div class="form-group text-right m-b-0">
                                <button class="btn btn-primary waves-effect waves-light" type="submit"> <?php echo DBout($buttonText)?> </button>
                                <button type="reset" class="btn btn-default waves-effect waves-light m-l-5" onclick="window.location = 'javascript:history.go(-1)'"> Cancel </button>
                                <input type="hidden" name="cmd" value="<?php echo DBout($cmd)?>" />
                                <input type="hidden" name="subscriber_id" value="<?php echo DBout($row['id'])?>" />
                                <input type="hidden" name="assignment_id" value="<?php echo DBout($assignID)?>" />
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include_once("footer_info.php");?>
</div>
<?php include_once("footer.php");?>
<div id="exportSubs" class="modal fade" role="dialog">
  <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h6 class="custom-modal-title">Export Subscribers</h6>
      </div>
      <div class="modal-body">
        <form method="post" enctype="multipart/form-data" action="server.php">
            <div class="form-group">
                <label class="move-right">Select Campaign</label>
                <select name="export_campaign_id" class="form-control">
                    <option value="all">ALL Subscribers</option>
                <?php
                $sql1 = sprintf("select id, title from campaigns where user_id=%s",
                        mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                    );
                    $lists = mysqli_query($link,$sql1);
                    if(mysqli_num_rows($lists)){
                        while($list = mysqli_fetch_assoc($lists)){
                            ?>
                    <option value="<?php echo DBout($list['id'])?>"><?php echo DBout($list['title'])?></option>
                    <?php   }
                    }
                    else{
                        ?>
                    <option value="">No campaign found.</option>';
                <?php   }
                ?>
                </select>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="cmd" value="export_subs" />
                <input type="submit" value="Export CSV" class="btn btn-primary" />
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="importSubs" class="modal fade" role="dialog">
  <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h6 class="custom-modal-title">Import Subscribers</h6>
      </div>
      <div class="modal-body">
        <form method="post" enctype="multipart/form-data" action="server.php">
            <div class="form-group">
                <label class="move-left">Select Campaign</label>
                <select name="imported_campaign_id" class="form-control">
                <?php
                $sql2 = sprintf("select id, title from campaigns where user_id=%s ",
                            mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                    );
                    $lists = mysqli_query($link,$sql2);
                    if(mysqli_num_rows($lists)){
                        while($list = mysqli_fetch_assoc($lists)){
                            ?>
                    <option value="<?php echo DBout($list['id'])?>"><?php echo DBout($list['title'])?></option>
                    <?php   }
                    }
                    else{?>
                    <option value="">No campaign found.</option>
                    <?php
                    }
                ?>
                </select>
            </div>
            <div class="form-group">
                <label class="move-left">Select CSV file </label>
                <input type="file" name="imported_csv" class="display-inline" required/><br>
                <span class="red">Note: Add phone numbers without country code.<br>Please check csv format in sample file before upload.</span>
                
            </div>
            <div class="modal-footer">
                <input type="hidden" name="cmd" value="import_subs" />
                <input type="submit" value="Import" class="btn btn-primary" />
            </div>
        </form>
      </div>
    </div>
  </div>
</div>