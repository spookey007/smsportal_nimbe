<?php
include_once("header.php");
include_once("left_menu.php");
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
                                Blocked Subscribers
                                <input type="button" class="btn btn-primary subscriber-1" value="Add New"  onclick="window.location='add_subscribers.php'" />

                                <input type="button" class="btn btn-danger numberActions subscriber-2" value="Delete Numbers" onclick="deleteNumbers()" />
                                <input type="button" class="btn btn-success numberActions subscriber-3" value="Schedule Message"  onclick="scheduleSMS()" />
                            </h4>
                            <p class="category">List of Blocked subscribers.</p>
                        </div>
                        <div class="content table-responsive table-full-width">
                            <div class="row">
                                <form class="subscriber-form">
                                    <div class="col-md-4"></div>
                                    <div class="col-md-5">
                                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by phone, name, email" value="<?php if(isset($_REQUEST['search'])){
                                           echo $_REQUEST['search']; 
                                        } ?>" />
                                    </div>
                                    <div class="col-md-2">
                                        <select name="group_id" id="group_id" class="form-control">
                                            <option value="">By Campaign</option>
                                            <?php
                                            $sql2 = "select * from campaigns where user_id = '".$_SESSION['user_id']."'";
                                            $res2 = mysqli_query($link,$sql2);
                                            while($row2 = mysqli_fetch_assoc($res2)){
                                                ?>
                                                <option <?php if($_REQUEST['group_id']==$row2['id']){ echo "selected"; } ?> value="<?php echo $row2['id']; ?>"><?php echo $row2['title'];?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-md btn-success"><i class="fa fa-search"></i></button>
                                    </div>
                                </form>
                            </div>
                            <table id="subscribersTable" class="table table-hover table-striped listTable subscriber-4">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th><input onclick="checkAll(this)" class="all_numbers_chk" name="all_numbers_chk" value="1" type="checkbox" /></th>
                                    <th>Name/Email</th>
                                    <th>Phone</th>
                                    <th>Campaign</th>
                                    <th>City/State</th>
                                    <th>Status</th>
                                    <th>Subscribed Date</th>
                                    <th>Manage</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                               
                                if(isset($_REQUEST['search']) && $_REQUEST['search']!=''){
                                    $sql = "select * from subscribers where status=2 and
                                                user_id='".$_SESSION['user_id']."' and (phone_number like '%".$_REQUEST['search']."%' or email like '%".$_REQUEST['search']."%')
                                                order by id desc";
                                }else{
                                    $sql = "select * from subscribers where status = 2 and user_id='".$_SESSION['user_id']."' order by id desc";
                                }
                            
                            

                                $res = mysqli_query($link,$sql) or die(mysqli_error($link));
                                if(mysqli_num_rows($res)){
                                    $index = 1;
                                    while($row = mysqli_fetch_assoc($res)){
                                        $sel = "select id as unReadMsgs from chat_history where phone_id='".$row['id']."' and is_read='0'";
                                        $exe = mysqli_query($link,$sel);
                                        if(mysqli_num_rows($exe)){
                                            $unReadMsgs = mysqli_num_rows($exe);
                                        }else{
                                            $unReadMsgs = 0;
                                        }
                                        if($appSettings['subs_lookup']=='1'){
                                            $show = 'display-1';
                                            if(trim($row['carrier_name'])==NULL){
                                                $response = subscriberLookUp($adminSettings['twilio_sid'],$adminSettings['twilio_token'],$row['phone_number'],$row['id']);
                                                $callerName = $response['caller_name']['caller_name'];
                                                $callerType = $response['caller_name']['caller_type'];
                                                $countryCode= $response['country_code'];
                                                $carrierName= $response['carrier']['name'];
                                                $carrierType= $response['carrier']['type'];
                                                $mobCountryCode = $response['carrier']['mobile_country_code'];
                                                $mobNetworkCode = $response['carrier']['mobile_network_code'];
                                            }else{
                                                $callerName  = $row['first_name'];
                                                $callerType  = $row['caller_type'];
                                                $countryCode = $row['country_code'];
                                                $carrierName = $row['carrier_name'];
                                                $carrierType = $row['carrier_type'];
                                                $mobCountryCode = $row['mobile_country_code'];
                                                $mobNetworkCode = $row['mobile_network_code'];
                                            }
                                        }else{
                                            $show = 'display-2';
                                            $callerName  = $row['first_name'];
                                            $callerType  = $row['caller_type'];
                                            $countryCode = $row['country_code'];
                                            $carrierName = $row['carrier_name'];
                                            $carrierType = $row['carrier_type'];
                                            $mobCountryCode = $row['mobile_country_code'];
                                            $mobNetworkCode = $row['mobile_network_code'];
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $index++?></td>
                                            <td>
                                                <input type="checkbox" id="number_<?php echo $row['id']; ?>" name="numbers[]" value="<?php echo $row['id']; ?>" class="numbers-checkbox" />
                                            </td>
                                            <td><?php echo highlightMatch($_REQUEST['search'],$callerName)?><br /><small><?php echo highlightMatch($_REQUEST['search'],$row['email']);?></small></td>
                                            <td><?php echo highlightMatch($_REQUEST['search'],$row['phone_number']);?></td>
                                     
                                            <td><?php echo $row['city'];?>/<?php echo $row['state'];?></td>

                                            <td>
                                                <?php
                                                if($row['status']=='1')
                                                    echo '<span class="badge badge-success">Active</span>';
                                                else if($row['status']=='2')
                                                    echo '<span class="badge badge-warning">Blocked</span>';
                                                else if($row['status']=='3')
                                                    echo '<span class="badge badge-danger">Deleted</span>';
                                                ?>
                                            </td>
                                            <td><?php echo date($appSettings['app_date_format'].' H:i:s',strtotime($row['created_date']));?></td>
                                            <td class="table-s">
                                                <?php
                                                if(trim($row['custom_info'])!=''){
                                                    ?>
                                                    <a href="#customInfoBox" title="View additional Information" onclick="getSubsCustomInfo('<?php echo $row['id']?>')" data-toggle="modal"><i class="fa fa-info"></i></a>
                                                    <?php
                                                }
                                                ?>
                                                <a href="chat.php?phoneid=<?php echo encode($row['id']).'&ph='.urlencode($row['phone_number']);?>" title="Chat">
                                                    <?php
                                                    if($unReadMsgs>0){
                                                        echo '<span class="chatBadge">'.$unReadMsgs.'</span>';
                                                    }
                                                    ?>
                                                    <i class="fa fa-comments table-c" aria-hidden="true"></i></a><i class="fa fa-arrow-down table-arr <?php echo $show?>"  onclick="showSubscriberDetails(this,'<?php echo $row['id']?>')"></i>&nbsp;&nbsp;<a href="add_subscribers.php?id=<?php echo $row['id']?>"><i class="fa fa-edit"></i></a>&nbsp;<i class="fa fa-remove del-subscriber"  onclick="deleteSubscriber('<?php echo $row['id']?>')"></i>
                                            </td>
                                        </tr>
             
                                        <?php
                                    }
                                }
                                ?>

                             
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include_once("footer_info.php");?>
</div>
<script type="text/javascript" src="js/block_subscriber.js"></script>
<?php include_once("footer.php");?>

<link rel="stylesheet" type="text/css" href="assets/css/stacktable.css" />
<script type="text/javascript" src="assets/js/stacktable.js"></script>
<div id="customInfoBox" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h6 class="custom-modal-title">Additional Information of the Subscriber</h6>
            </div>
            <div class="modal-body loadCustomInfo"></div>
       
        </div>
    </div>
</div>