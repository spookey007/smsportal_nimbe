<?php
	include_once("header.php");
	include_once("left_menu.php");
        if((isset($_REQUEST['id'])) && (trim($_REQUEST['id']!=''))){
		$sql = sprintf("select * from campaigns where id=%s",
                mysqli_real_escape_string($link,DBin($_REQUEST['id']))
            );
		$res = mysqli_query($link,$sql);
		if(mysqli_num_rows($res)){
			$row = mysqli_fetch_assoc($res);
		}else
			$row = array();
        $pageTitle = DBout('Edit Campaign');
    }else{
        $pageTitle = DBout('Add Campaign');
    }
    
	$timeArray   = getTimeArray();
	$timeOptions = '';
	foreach($timeArray as $key => $value){
		$timeOptions .= '<option value="'.DBout($key).'">'.DBout($value).'</option>';
	}
	$options = '';
	for($i=1; $i<=23; $i++){
		if($i > 9)
			$hour = DBout('hours');
		else
			$hour = DBout('hour');
		$options .= '<option value="+'.DBout($i).' '.DBout($hour).'">After '.DBout($i).' '.ucfirst(DBout($hour)).'</option>';
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
								<?php echo DBout($pageTitle) ?>
								<input type="button" class="btn btn-primary move-right" value="Back" onclick="window.location='view_campaigns.php'" />
							</h4>
							<p class="category">Create your awesome campaigns here.</p>
						</div>
						<div class="content table-responsive">
                            <form action="server.php" data-parsley-validate novalidate enctype="multipart/form-data" method="post">
                                <div class="form-group">
                                    <label>Title*</label>
                                    <input type="text" name="title" parsley-trigger="change" required placeholder="Enter title..." class="form-control" value="<?php echo DBout(!empty($row['title']) ? $row['title'] : '')?>">
                                </div>
                                <div class="form-group">
                                    <label>Keyword*</label>
                                    <input type="text" name="keyword" parsley-trigger="change" required placeholder="Enter keyword..." class="form-control" value="<?php echo DBout(!empty($row['keyword']) ? $row['keyword'] : '')?>">
                                </div>
                                <div class="form-group">
                                    <label>Phone Number*</label>
                                    <select name="phone_number" class="form-control">
                                        <option value="">- Select One -</option>
                                        <?php
                                        if($appSettings['sms_gateway']=='twilio'){
                                            $sel = sprintf("select * from users_phone_numbers where user_id=%s and ( type='1' or type='4' )",
                                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                            );
                                        }else if($appSettings['sms_gateway']=='plivo'){
                                            $sel = sprintf("select * from users_phone_numbers where user_id=%s and type='2'",
                                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                            );
                                        }
                                        else if($appSettings['sms_gateway']=='nexmo'){
                                            $sel = sprintf("select * from users_phone_numbers where user_id=%s and type='3'",
                                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                            );
                                        }else{
                                            $sel = sprintf("select * from users_phone_numbers where user_id=%s",
                                                mysqli_real_escape_string($link,filterVar($_SESSION['user_id']))
                                            );
                                        }
                                        $rec = mysqli_query($link,$sel);
                                        if(mysqli_num_rows($rec)){
                                            while($numbers = mysqli_fetch_assoc($rec)){
                                                if($row['phone_number']==$numbers['phone_number']){
                                                    $selected = 'selected="selected"';
                                                }else{
                                                    $selected = '';
                                                } ?>

                                                <option <?php echo DBout($selected) ?> value="<?php echo DBout($numbers['phone_number'],ENT_COMPAT)?>"><?php echo DBout($numbers['phone_number'])?></option>
                                            <?php	}
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><input name="attach_mobile_device" <?php
                                        if(!empty($row['attach_mobile_device'])) {
                                            if ($row['attach_mobile_device'] == '1')
                                                echo DBout('checked="checked"');
                                            else
                                                echo DBout('');
                                        }
                                        ?> value="1" type="checkbox" /> Attach mobile device</label>
                                </div>

                                <?php if((!isset($_SESSION['user_id']))&& ($_SESSION['user_type']=='1')){?>
                                    <div class="form-group">
                                        <label><input name="share_with_subaccounts" <?php
                                            if(!empty($row['share_with_subaccounts']=='1')) {
                                                if ($row['share_with_subaccounts'] == '1')
                                                    echo DBout('checked="checked"');
                                                else
                                                    echo DBout('');
                                            }
                                            ?> value="1" type="checkbox" /> Share Campaign With Subaccounts</label>
                                    </div>
                                <?php } ?>
                                <div class="col-lg-12 padding-0">
                                    <div class="portlet">
                                        <div class="portlet-heading bg-custom portlet_style">
                                            <h5 class="white">
                                                SMS/MMS
                                                <a onclick="slideToggleMainSection(this,'sms_texts_section','');" href="javascript:;"><i class="fa fa-plus white move-right" title="Open"></i></a>
                                            </h5>
                                            <div class="portlet-widgets">
                                                <span class="divider"></span>
                                                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round white" title="Show/Hide"></i></a>
                                            </div>
                                        </div>
                                        <div class="panel-collapse sms_texts_section display-block" aria-expanded="true">
                                            <div class="portlet-body padding-10">

                                                <div class="form-group display-none">
                                                    <label>Select Media</label>
                                                    <input type="file" name="campaign_media" class="display-inline" />
                                                    <input type="hidden" name="hidden_campaign_media" value="<?php echo DBout($row['media'])?>" />
                                                    <?php
                                                    if(!empty($row['media'])){
                                                        echo DBout(isMediaExists($row['media']));
                                                    }
                                                    ?>
                                                </div>

                                                <div class="form-group">
                                                    <label>Winning No*</label>
                                                    <input name="winning_number" parsley-trigger="change" required placeholder="Winning number" class="form-control" value="<?php echo DBout(!empty($row['winning_number']) ? $row['winning_number'] : '')?>"/>
                                                </div>

                                                <div class="form-group">
                                                    <label>Winner SMS*</label>
                                                    <textarea name="winner_msg" parsley-trigger="change" required placeholder="Enter winner message" class="form-control textCounter"><?php echo DBout(!empty($row['winner_msg'] ) ? $row['winner_msg'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout(!empty($row['winner_msg']) ? $maxLength-strlen($row['winner_msg']) : '' )?></span> Characters left
												</span>
                                                </div>

                                                <div class="form-group">
                                                    <label>Looser SMS*</label>
                                                    <textarea name="looser_msg" parsley-trigger="change" required placeholder="Enter looser message" class="form-control textCounter"><?php echo DBout(!empty($row['looser_msg']) ? $row['looser_msg'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout(!empty($row['looser_msg']) ? $maxLength-strlen($row['looser_msg']) : '')?></span> Characters left
												</span>
                                                </div>

                                                <div class="form-group">
                                                    <label>Already Member SMS*</label>
                                                    <textarea name="already_member_sms" parsley-trigger="change" required placeholder="Enter sms text for existing member..." class="form-control textCounter"><?php echo DBout(!empty($row['already_member_msg']) ? $row['already_member_msg'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout(!empty($row['already_member_msg']) ? $maxLength-strlen($row['already_member_msg']) : '')?></span> Characters left
												</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="edit_camapign_style"></div>
                                <div class="col-lg-12 padding-0">
                                    <?php
                                    if(!empty($row['double_optin_check'])) {
                                        if ($row['double_optin_check'] == '1') {
                                            $doubleOptInIcon = DBout('fa-minus');
                                            $doubleOptinCheck = DBout('checked=checked');
                                            $doubleOptinMainSection = 'display-1';
                                            $doubleOptinInnerSection = 'display-1';
                                        } else {
                                            $doubleOptInIcon = DBout('fa-plus');
                                            $doubleOptinCheck = '';
                                            $doubleOptinMainSection = DBout('display-2');
                                            $doubleOptinInnerSection = DBout('display-2');
                                        }
                                    } else {
                                        $doubleOptInIcon = DBout('fa-plus');
                                        $doubleOptinCheck = '';
                                        $doubleOptinMainSection = DBout('display-2');
                                        $doubleOptinInnerSection = DBout('display-2');
                                    }
                                    ?>
                                    <div class="portlet">
                                        <div class="portlet-heading bg-custom portlet_style">
                                            <h5 class="white">
                                                Make the campaign double opt-in
                                                <a onclick="slideToggleMainSection(this,'double_optin_section','doubleOptInCheck');" href="javascript:;"><i class="fa <?php echo DBout(!empty($doubleOptInIcon) ? $doubleOptInIcon : '')?> white move-right" title="Open"></i></a>
                                            </h5>
                                            <div class="portlet-widgets">
                                                <span class="divider"></span>
                                                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" class="white"></i></a>
                                            </div>
                                        </div>
                                        <div class="panel-collapse collapse  double_optin_section <?php echo DBout($doubleOptinMainSection)?>"  aria-expanded="true">
                                            <div class="portlet-body padding-10">
                                                <div class="form-group">
                                                    <label><input <?php echo DBout(!empty($doubleOptinCheck) ? $doubleOptinCheck : '')?> type="checkbox" name="double_optin_check" value="1" onClick="slideToggleInnerSection(this,'doubleOptInSection')" /> Enable Double Opt-in</label>
                                                </div>
                                                <div class="form-group doubleOptInSection <?php echo DBout($doubleOptinInnerSection)?>" >
                                                    <label>Double Opt-in SMS*</label>
                                                    <textarea name="double_optin" placeholder="Enter double opt-in text..." class="form-control textCounter"><?php echo DBout(!empty($row['double_optin']) ? $row['double_optin'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
												</span>
                                                </div>
                                                <div class="form-group doubleOptInSection <?php echo DBout($doubleOptinInnerSection)?>" >
                                                    <label>Double Opt-in Confirm Message</label>
                                                    <textarea name="double_optin_confirm_message" placeholder="Enter double opt-in text..." class="form-control textCounter"><?php echo DBout(!empty($row['double_optin_confirm_message']) ? $row['double_optin_confirm_message'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
												</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="edit_camapign_style"></div>
                                <div class="col-lg-12 padding-0">
                                    <?php
                                    $mainSection = 'display-1';
                                    $getEmailIcon = DBout('fa-plus');
                                    if(!empty($row['get_email'])){
                                        if ($row['get_email'] == '1') {
                                            $getEmailIcon = DBout('fa-minus');
                                            $getEmailCheck = DBout('checked=checked');
                                            $mainSection = 'diplay-1';
                                            $getEmailInnerSection = 'display-1';
                                        } else {
                                            $getEmailCheck = 'display-2';
                                            $getEmailInnerSection = DBout('display-2');
                                        }
                                    } else {
                                            $getEmailCheck = 'display-2';
                                            $getEmailInnerSection = DBout('display-2');
                                    }
                                    if(!empty($row['get_subs_name_check'])) {
                                        if ($row['get_subs_name_check'] == '1') {
                                            $getEmailIcon = DBout('fa-minus');
                                            $getNameCheck = DBout('checked=checked');
                                            $mainSection = 'display-1';
                                            $getNameInnerSection = 'display-1';
                                        } else {
                                            $getNameCheck = 'display-2';
                                            $getNameInnerSection = DBout('display-2');
                                        }
                                    }else{
                                        $getNameCheck = 'display-2';
                                        $getNameInnerSection = DBout('display-2');
                                    }
                                    ?>
                                    <div class="portlet">
                                        <div class="portlet-heading bg-custom portlet_style">
                                            <h5 class="white">
                                                Get subscriber name/email
                                                <a onclick="slideToggleMainSection(this,'get_email_section','get_email');" href="javascript:;"><i class="fa <?php echo DBout(!empty($getEmailIcon) ? $getEmailIcon : '')?> white move-right" title="Open"></i></a>
                                            </h5>
                                            <div class="portlet-widgets">
                                                <span class="divider"></span>
                                                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" class="white"></i></a>
                                            </div>
                                        </div>
                                        <div class="panel-collapse collapse get_email_section <?php echo DBout($mainSection)?>" aria-expanded="true">
                                            <div class="portlet-body padding-10">
                                                <div class="form-group">
                                                    <label class="checkbox-inline"><input type="checkbox" name="get_subs_email" <?php echo DBout(!empty($getEmailCheck) ? $getEmailCheck : '')?> value="1" onClick="slideToggleInnerSection(this,'subsEmailSection')" /> Get subscriber email</label>
                                                </div>
                                                <div class="form-group subsEmailSection <?php echo DBout($getEmailInnerSection)?>" >
                                                    <label>Message to get subscriber Email</label>
                                                    <textarea name="reply_email" parsley-trigger="change" placeholder="Enter sms to ask for email..." class="form-control textCounter"><?php echo DBout(!empty($row['reply_email']) ? $row['reply_email'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
												</span>
                                                </div>
                                                <div class="form-group subsEmailSection <?php echo DBout($getEmailInnerSection)?>" >
                                                    <label>Email Received Confirmation Message</label>
                                                    <textarea name="email_updated" parsley-trigger="change" placeholder="Confirmation sms text for receiving email..." class="form-control textCounter"><?php echo DBout(!empty($row['email_updated']) ? $row['email_updated'] : '')?></textarea>
                                                    <span class="showCounter">
													<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
												</span>
                                                </div>

                                                <div class="form-group">
                                                    <label class="checkbox-inline"><input type="checkbox" name="get_subs_name_check" <?php echo DBout($getNameCheck)?> onClick="slideToggleInnerSection(this,'subsNameSection')" value="1" /> Get subscriber name</label>
                                                </div>
                                                <div class="subsNameSection <?php echo DBout($getNameInnerSection)?>">
                                                    <div class="form-group">
                                                        <label>Message to get subscriber name</label>
                                                        <textarea name="msg_to_get_subscriber_name" parsley-trigger="change" placeholder="Message to get subscriber name..." class="form-control textCounter"><?php echo DBout(!empty($row['msg_to_get_subscriber_name']) ? $row['msg_to_get_subscriber_name'] : '')?></textarea>
                                                        <span class="showCounter">
														<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
													</span>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Name Received Confirmation Message</label>
                                                        <textarea name="name_received_confirmation_msg" parsley-trigger="change" placeholder="Name received confirmation message..." class="form-control textCounter"><?php echo DBout(!empty($row['name_received_confirmation_msg']) ? $row['name_received_confirmation_msg'] : '')?></textarea>
                                                        <span class="showCounter">
														<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
													</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="edit_camapign_style"></div>
                                <div class="col-lg-12 padding-0">
                                    <?php
                                    if(!empty($row['campaign_expiry_check'])) {
                                        if ($row['campaign_expiry_check'] == '1') {
                                            $campExpiryIcon = DBout('fa-minus');
                                            $campaignExpiryCheck = DBout('checked=checked');
                                            $campaignExpirySection = 'display-1';
                                            $campaignExpiryInnerSection = 'display-1';
                                        } else {
                                            $campExpiryIcon = DBout('fa-plus');
                                            $campaignExpiryCheck = 'display-2';
                                            $campaignExpirySection = DBout('display-2');
                                            $campaignExpiryInnerSection = DBout('display-2');
                                        }
                                    } else {
                                        $campExpiryIcon = DBout('fa-plus');
                                        $campaignExpiryCheck = '';
                                        $campaignExpirySection = DBout('display-2');
                                        $campaignExpiryInnerSection = DBout('display-2');
                                    }
                                    ?>
                                    <div class="portlet">
                                        <div class="portlet-heading bg-custom portlet_style">
                                            <h5 class="white">
                                                Activate campaign for limited time
                                                <a onclick="slideToggleMainSection(this,'campaign_expity_section','check_campaign_expiry');" href="javascript:;"><i class="fa <?php echo DBout($campExpiryIcon)?> white move-right" title="Open"></i></a>
                                            </h5>
                                            <div class="portlet-widgets">
                                                <span class="divider"></span>
                                                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" class="white"></i></a>
                                            </div>
                                        </div>
                                        <div class="panel-collapse collapse campaign_expity_section <?php echo DBout($campaignExpirySection)?>" aria-expanded="true">
                                            <div class="portlet-body padding-10">
                                                <div class="form-group">
                                                    <label><input type="checkbox" name="campaign_expiry_check" <?php echo DBout(!empty($campaignExpiryCheck) ? $campaignExpiryCheck : '')?> onClick="slideToggleInnerSection(this,'campaignExpirySection')" value="1" /> Enable/Disable</label>
                                                </div>
                                                <div class="campaignExpirySection <?php echo DBout($campaignExpiryInnerSection)?>" >
                                                    <div class="col-md-6 padding-left-0">
                                                        <div class="form-group">
                                                            <label>Start Date</label>
                                                            <input type="text" class="form-control addDatePicker" name="start_date" placeholder="Start date." value="<?php echo DBout(!empty($row['start_date']) ? $row['start_date'] : '')?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 padding-right-0">
                                                        <div class="form-group">
                                                            <label>End Date</label>
                                                            <input type="text" class="form-control addDatePicker" name="end_date" placeholder="End date." value="<?php echo DBout(!empty($row['end_date']) ? $row['end_date'] : '')?>">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Expire Message</label>
                                                        <textarea name="expire_message" parsley-trigger="change" placeholder="Expire Message" class="form-control textCounter"><?php echo DBout(!empty($row['expire_message']) ? $row['expire_message'] : '')?></textarea>
                                                        <span class="showCounter">
														<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
													</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="edit_camapign_style"></div>
                                <div class="col-lg-12 padding-0">
                                    <?php
                                    if(!empty($row['followup_msg_check'])) {
                                        if ($row['followup_msg_check'] == '1') {
                                            $followUpIcon = DBout('fa-minus');
                                            $followUpCheck = DBout('checked=checked');
                                            $followUpSection = 'display-1';
                                            $followUpInnerSection = 'display-1';
                                        } else {
                                            $followUpIcon = DBout('fa-plus');
                                            $followUpCheck = 'display-2';
                                            $followUpSection = DBout('display-2');
                                            $followUpInnerSection = DBout('display-2');
                                        }
                                    } else {
                                        $followUpIcon = DBout('fa-plus');
                                        $followUpCheck = 'display-2';
                                        $followUpSection = DBout('display-2');
                                        $followUpInnerSection = DBout('display-2');
                                    }
                                    ?>
                                    <div class="portlet">
                                        <div class="portlet-heading bg-custom portlet_style">
                                            <h5 class="white">
                                                Add Delay Messages for this campaign.
                                                <a onclick="slideToggleMainSection(this,'follow_up_msg_section','');" href="javascript:void(0) "><i class="fa <?php echo DBout($followUpIcon)?> white move-right" title="Add More"></i></a>
                                            </h5>
                                            <div class="portlet-widgets">
                                                <span class="divider"></span>
                                                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round white" title="Show/Hide"></i></a>
                                            </div>
                                        </div>
                                        <div class="panel-collapse collapse follow_up_msg_section <?php echo DBout($followUpSection)?>" id="bg-primary"  aria-expanded="true">                                   
                                            <div class="form-group padding-10">
                                                <label><input type="checkbox" <?php echo DBout(!empty($followUpCheck) ? $followUpCheck : '')?> name="followup_msg_check" value="1" onClick="slideToggleInnerSection(this,'followUpContainer')" /> Enable/Disable</label>
                                            </div>
                                            <div class="portlet-body followUpContainer padding-10 <?php echo DBout($followUpInnerSection)?>" id="followUpContainer">
                                                <?php
                                                $totalFollowUp=0;
                                                if(!empty($row['id'])) {
                                                    $sqlFollow = sprintf("select * from follow_up_msgs where group_id=%s order by id asc",
                                                        mysqli_real_escape_string($link, filterVar($row['id']))
                                                    );
                                                    $resFollow = mysqli_query($link, $sqlFollow);
                                                    $totalFollowUp = mysqli_num_rows($resFollow);
                                                }
                                                if($totalFollowUp==0){
                                                    ?>
                                                    <div>
                                                        <table width="100%" class="delay_table">
                                                            <tr>
                                                                <td width="25%">Select Days/Time</td>
                                                                <td>
                                                                    <input type="text" class="form-control numericOnly delay-days" placeholder="Days delay..." name="delay_day[]" value="0" onblur="switchTimeDropDown(this)">&nbsp;
                                                                    <select class="form-control timeDropDown width-48-per display-none" name="delay_time[]">
                                                                        <?php
                                                                        $timeArray = getTimeArray();
                                                                        foreach($timeArray as $key => $value){
                                                                            ?>

                                                                            <option value="<?php echo DBout($key) ?>"><?php echo DBout($value)?></option>

                                                                        <?php	}
                                                                        ?>
                                                                    </select>
                                                                    <select class="form-control hoursDropDown width-48-per display-inline" name="delay_time_hours[]">
                                                                        <?php
                                                                        echo $options;
                                                                        ?>
                                                                    </select>
                                                                    <span class="pointer margin-left-30" onClick="addMoreFollowUpMsg()"><i class="fa fa-plus plus-green-style" title="Add More"></i></span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Message</td>
                                                                <td>
                                                                    <textarea name="delay_message[]" class="form-control textCounter"></textarea>
                                                                    <span class="showCounter">
							<span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
						</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Attach Media</td>
                                                                <td>
                                                                    <input type="file" name="delay_media[]">
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <?php
                                                }else{
                                                    $index = 0;
                                                    while($rowFollow = mysqli_fetch_assoc($resFollow)){
                                                        if($rowFollow['delay_day']=='0'){
                                                            $timeList = DBout('display-2');
                                                            $hoursList = DBout('display-3');
                                                        }else{
                                                            $timeList = DBout('display-3');
                                                            $hoursList = DBout('display-2');
                                                        }
                                                        ?>
                                                        <div>
                                                            <table width="100%" class="delay_table">
                                                                <tr>
                                                                    <td width="25%">Select Days/Time</td>
                                                                    <td>
                                                                        <input type="text" class="form-control numericOnly delay-days <?php echo DBout($timeList)?> " placeholder="Days delay..." name="delay_day[]" value="<?php echo DBout(!empty($rowFollow['delay_day']) ? $rowFollow['delay_day'] : '')?>" onblur="switchTimeDropDown(this)">&nbsp;
                                                                        <select class="form-control timeDropDown width-48-per" name="delay_time[]">
                                                                            <?php
                                                                            $timeArray = getTimeArray();
                                                                            foreach($timeArray as $key => $value){
                                                                                if($key == $rowFollow['delay_time'])
                                                                                    $sel = 'selected="selected"';
                                                                                else
                                                                                    $sel = '';
                                                                                ?>
                                                                                <option <?php echo DBout($sel)?> value="<?php echo DBout($key)?>"><?php echo DBout($value) ?></option>
                                                                            <?php	}
                                                                            ?>
                                                                        </select>
                                                                        <select class="form-control hoursDropDown width-48-per <?php echo DBout($hoursList)?>" name="delay_time_hours[]">
                                                                            <?php
                                                                            for($i=1; $i<=23; $i++){
                                                                                if($i > 1)
                                                                                    $hour = DBout('hours');
                                                                                else
                                                                                    $hour = DBout('hour');

                                                                                if($rowFollow['delay_time'] == '+'.$i.' '.$hour)
                                                                                    $selh = DBout('selected="selected"');
                                                                                else
                                                                                    $selh = '';
                                                                                ?>
                                                                                <option<?php echo DBout($selh)?> value="<?php echo DBout('+'.($i)).' '.DBout($hour)?>">After <?php echo DBout($i).' '.DBout(ucfirst($hour))?></option>
                                                                            <?php	}
                                                                            ?>
                                                                        </select>
                                                                        <?php
                                                                        if($index=='0'){
                                                                            ?>
                                                                            <span class="margin-left-30 pointer" onClick="addMoreFollowUpMsg()"><i class="fa fa-plus plus-green-style" title="Add More"></i></span>
                                                                            <?php
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Message</td>
                                                                    <td>
                                                                        <textarea name="delay_message[]" class="form-control textCounter"><?php echo DBout(!empty($rowFollow['message']) ? $rowFollow['message'] : '')?></textarea>
                                                                        <span class="showCounter">
							<span class="showCount"><?php echo DBout($maxLength-strlen(DBout($rowFollow['message'])))?></span> Characters left
						</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Attach Media</td>
                                                                    <td>
                                                                        <input type="hidden" name="hidden_delay_media[]" value="<?php echo DBout($rowFollow['media'])?>">
                                                                        <input type="file" name="delay_media[]" class="display-inline"><span class="fa fa-trash trash-style" title="Remove Message" onclick="removeFollowUp(this)"></span><br>
                                                                        <?php
                                                                        if(!empty($rowFollow['media'])){
                                                                            echo isMediaExists($rowFollow['media']);
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <?php if(($index+1)!=$totalFollowUp){?>
                                                                    <tr><td colspan="2"><hr class="hr_style"></td></tr>
                                                                <?php } DBout($index++);?>
                                                            </table>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="edit_camapign_style"></div>
                                <div class="form-group text-right m-b-0">
                                    <button class="btn btn-primary waves-effect waves-light" type="submit"> Save </button>
                                    <button type="reset" class="btn btn-default waves-effect waves-light m-l-5" onclick="window.location = 'javascript:history.go(-1)'"> Cancel </button>
                                    <input type="hidden" name="cmd" value="add_contest" />
                                    <input type="hidden" name="campaign_id" value="<?php echo DBout($rowFollow['id'])?>" />
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
<script type="text/javascript" src="scripts/js/parsley.min.js"></script> 
<script src="scripts/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript" src="scripts/add_campaign.js"></script>