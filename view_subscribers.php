<?php
    include_once("header.php");
    include_once("left_menu.php");
	if(trim($_REQUEST['filter_records'])=='')
		$_REQUEST['filter_records'] = 'all';
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
                                Subscribers
                                <input type="button" class="btn btn-primary move-right" value="Add New" onclick="window.location='add_subscribers.php'" />
                                
                                <input type="button" class="btn btn-danger move-right" value="Delete All" onclick="deleteAllNumbers()" />
                                <input type="button" class="btn btn-danger numberActions move-right" value="Delete Numbers" onclick="deleteNumbers()" style="display: none" />
                                
                                <input type="button" class="btn btn-success numberActions move-right" id="display-none" value="Schedule Message" onclick="scheduleSMS()" />
                            </h4>
                            <p class="category">List of subscribers.</p>
                        </div>
                        <?php

                        $sql_subscribers = mysqli_query($link,sprintf("select * from subscribers where user_id = %s",
                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                            ));
                        ?>
                        <div class="col-md-4"><span class="badge badge-success"><?php echo 'Total : '.mysqli_num_rows($sql_subscribers); ?></span></div><br>
                        <div class="content table-responsive table-full-width">
                            <div class="row">
                                <form class="view_subscriber_class">
                                    <div class="col-md-4" style="text-align: -webkit-right">
										<select name="filter_records" class="form-control" style="width: 150px;" onChange="reloadRecords(this)">
											<option <?php if($_REQUEST['filter_records']=='all')echo 'selected="selected"'?> value="all">All</option>
											<option <?php if($_REQUEST['filter_records']=='0-250')echo 'selected="selected"'?> value="0-250">0-250</option>
											<option <?php if($_REQUEST['filter_records']=='250-500')echo 'selected="selected"'?> value="250-500">250-500</option>
											<option <?php if($_REQUEST['filter_records']=='500-750')echo 'selected="selected"'?> value="500-750">500-750</option>
											<option <?php if($_REQUEST['filter_records']=='750-1000')echo 'selected="selected"'?> value="750-1000">750-1000</option>
										</select>
									</div>
                                    <div class="col-md-5">
                                        <?php
											if((isset($_REQUEST['search'])) && ($_REQUEST['search']!='')){
												$search = $_REQUEST['search'];
											}else{
												$search = '';
											}
                                        ?>
                                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by phone, name, email" value="<?php echo DBout($search)?>" />
                                    </div>
                                    <div class="col-md-2">
                                        <select name="group_id" id="group_id" class="form-control">
                                            <option value="">By Campaign</option>
                                        <?php
                                        $sql2 = sprintf("select * from campaigns where user_id =%s ",
                                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                            );
                                        $res2 = mysqli_query($link,$sql2);
                                        while($row2 = mysqli_fetch_assoc($res2)){
                                            ?>
                                                <option <?php if($_REQUEST['group_id']==$row2['id']){ echo DBout("selected"); } ?> value="<?php echo DBout($row2['id']); ?>"><?php echo DBout($row2['title']);?></option>
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
                            <table id="subscribersTable" class="table table-hover table-striped listTable margin-right-200">
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
                
                $where = "where s.user_id='".DBout($_SESSION['user_id'])."' and s.id=sga.subscriber_id and sga.group_id=c.id ";
                
                if((isset($_REQUEST['search'])) && ($_REQUEST['search']!='')){
                    $search = DBin($_REQUEST['search']);
                    $where .= " and (s.phone_number like '%".$search."%' or s.email like '%".$search."%' or s.first_name like '%".$search."%' or s.last_name like '%".$search."%' or s.custom_info like '%".$search."%')";
                }else{
                    $_REQUEST['search'] = '';
                }

                $group_id = '';
                if(isset($_REQUEST['group_id']) && $_REQUEST['group_id']!=''){
                    $group_id = DBin($_REQUEST['group_id']);
                    $where .= " and sga.group_id = $group_id";
                }
                
				if(trim($_REQUEST['filter_records'])=='all'){
						//$limit = 'limit 0,250';
						//$sql ="select s.*, c.title from subscribers s, subscribers_group_assignment sga, campaigns c $where order by s.id desc limit ".$limit;
						//$sql ="select s.*, c.title from subscribers s, subscribers_group_assignment sga, campaigns c $where order by s.id desc";
						$sql ="select s.*, c.title from subscribers s, subscribers_group_assignment sga, campaigns c $where order by s.id desc";
						$pageNum=1;
						if(isset($_GET['page'])) {
							if (is_numeric($_GET['page']))
								$pageNum = DBin($_GET['page']);
							else
								$pageNum = 1;
						}
						$gr_id='';
						if(isset($_REQUEST['group_id']) && $_REQUEST['group_id']!=''){
							$gr_id = DBin($_REQUEST['group_id']);
						}
						$max_records_per_page = 20;
						$pagelink   = "view_subscribers.php?search=".$_REQUEST['search']."&group_id=".$gr_id."&filter_records=all&";
						$pages      = generatePaging($sql,$pagelink,$pageNum,$max_records_per_page);
						$limit      = $pages['limit'];
						$sql       .= $limit;
						if($pageNum==1)
							$countPaging=1;
						else
							$countPaging=(($pageNum*$max_records_per_page)-$max_records_per_page)+1;

						if($_SESSION['TOTAL_RECORDS'] <= $max_records_per_page){
							$maxLimit = $_SESSION['TOTAL_RECORDS'];
						}else{
							$maxLimit = (((int)$countPaging+(int)$max_records_per_page)-1);
						}
						if($maxLimit >= $_SESSION['TOTAL_RECORDS']){
							$maxLimit = $_SESSION['TOTAL_RECORDS'];
						}
				}else{
					$limit = explode('-',$_REQUEST['filter_records']);
					$limit = 'limit '.$limit[0].','.$limit[1];
					$sql ="select s.*, c.title from subscribers s, subscribers_group_assignment sga, campaigns c $where order by s.id desc ".$limit;
				}
				
                
						
				//echo $sql;
				
                $res = mysqli_query($link,$sql) or die(mysqli_error($link));
                if(mysqli_num_rows($res)){
					if(trim($_REQUEST['filter_records'])=='all'){
                    	$index = $countPaging;
					}else{
						$index = 1;
					}
                    while($row = mysqli_fetch_assoc($res)){
                        $sel = sprintf("select id as unReadMsgs from chat_history where phone_id=%s and is_read='0'",
                                mysqli_real_escape_string($link,filterVar($row['id']))
                            );
                        $exe = mysqli_query($link,$sel);
                        if(mysqli_num_rows($exe)){
                            $unReadMsgs = DBout(mysqli_num_rows($exe));
                        }else{
                            $unReadMsgs = 0;
                        }
                        if($appSettings['subs_lookup']=='1'){
                            $show = '';
                            if(trim($row['carrier_name'])==NULL){
                                $response = subscriberLookUp($adminSettings['twilio_sid'],$adminSettings['twilio_token'],$row['phone_number'],$row['id']);
                                $callerName = DBout($response['caller_name']['caller_name']);
                                $callerType = DBout($response['caller_name']['caller_type']);
                                $countryCode= DBout($response['country_code']);
                                $carrierName= DBout($response['carrier']['name']);
                                $carrierType= DBout($response['carrier']['type']);
                                $mobCountryCode = DBout($response['carrier']['mobile_country_code']);
                                $mobNetworkCode = DBout($response['carrier']['mobile_network_code']);
                            }else{
                                $callerName  = DBout($row['first_name']);
                                $callerType  =DBout( $row['caller_type']);
                                $countryCode = DBout($row['country_code']);
                                $carrierName =DBout( $row['carrier_name']);
                                $carrierType = DBout($row['carrier_type']);
                                $mobCountryCode = DBout($row['mobile_country_code']);
                                $mobNetworkCode = DBout($row['mobile_network_code']);
                            }
                        }else{
                            $show = DBout('display-2');
                            $callerName  = DBout($row['first_name']);
                            $callerType  = DBout($row['caller_type']);
                            $countryCode = DBout($row['country_code']);
                            $carrierName = DBout($row['carrier_name']);
                            $carrierType = DBout($row['carrier_type']);
                            $mobCountryCode = DBout($row['mobile_country_code']);
                            $mobNetworkCode = DBout($row['mobile_network_code']);
                        }
            ?>
                        <tr>
                            <td><?php echo DBout($index++)?></td>
                            <td>
                                <input type="checkbox" id="number_<?php echo DBout($row['id']); ?>" name="numbers[]" value="<?php echo DBout($row['id']); ?>" class="numbers-checkbox" />
                            </td>
                            <td><?php echo DBout(highlightMatch($_REQUEST['search'],$callerName))?><br /><small><?php echo DBout(highlightMatch($_REQUEST['search'],$row['email']));?></small></td>
                            <td><?php echo DBout(highlightMatch($_REQUEST['search'],$row['phone_number']));?></td>
                            <td><?php echo DBout($row['title']);?></td>
                            <td><?php echo DBout($row['city']);?>/<?php echo DBout($row['state']);?></td>
                            
                            <td>
                                <?php 
                                    if($row['status']=='1'){ ?>
                                        <span class="badge badge-success">Active</span>
                                <?php }
                                    else if($row['status']=='2'){ ?>
                                        <span class="badge badge-warning">Blocked</span>
                                <?php   } else if($row['status']=='3') { ?>
                                        <span class="badge badge-danger">Deleted</span>
                                <?php    }?>
                            </td>
                            <td><?php echo DBout(date($appSettings['app_date_format'].' H:i:s',strtotime($row['created_date'])));?></td>
                            <td class="text-center">
                                <?php
                                    if(trim($row['custom_info'])!=''){
                                ?>
                                    <a href="#customInfoBox" title="View additional Information" onclick="getSubsCustomInfo('<?php echo DBout($row['id'])?>')" data-toggle="modal"><i class="fa fa-info"></i></a>
                                <?php
                                    }
                                ?>
                                <a href="chat.php?phoneid=<?php echo DBout(encode($row['id']).'&ph='.urlencode($row['phone_number']));?>" title="Chat">
                                    <?php
                                        if($unReadMsgs>0){ ?>
                                            <span class="chatBadge"><?php echo DBout($unReadMsgs)?></span>
                                    <?php   }
                                    ?>
                                    <i class="fa fa-comments green" aria-hidden="true"></i></a><i class="fa fa-arrow-down pointer pruple <?php echo DBout($show)?>" onclick="showSubscriberDetails(this,'<?php echo DBout($row['id'])?>')"></i>&nbsp;&nbsp;<a href="add_subscribers.php?id=<?php echo DBout($row['id'])?>"><i class="fa fa-edit"></i></a>&nbsp;<i class="fa fa-remove red pointer" onclick="deleteSubscriber('<?php echo DBout($row['id'])?>')"></i>
                            </td>
                        </tr>
            <?php           
                    }   
                }
            ?>  
                
                <tr>
                    <td colspan="11" class="padding-right-0 padding-left-0"><?php echo $pages['pagingString'];?></td>
                </tr>
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
<?php include_once("footer.php");?>
<link rel="stylesheet" type="text/css" href="assets/css/stacktable.css" />
<script type="text/javascript" src="assets/js/stacktable.js"></script>
<script src="scripts/view_subscribers.js"></script>
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
<script>
	function reloadRecords(obj){
		var filter = $(obj).val();
		window.location = 'view_subscribers.php?filter_records='+filter;
	}
	function deleteAllNumbers(){
		if(confirm("Are you sure you want to delete all numbers?")){
			$.post("server.php",{"cmd":"delete_all_numbers"},function(r){
				window.location = 'view_subscribers.php';
			});
		}
	}
</script>