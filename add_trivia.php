<?php
include_once("header.php");
include_once("left_menu.php");
if((isset($_REQUEST['id'])) && (trim($_REQUEST['id']!=''))){
   $sql = sprintf("select * from campaigns where id=%s ",
    mysqli_real_escape_string($link,DBin($_REQUEST['id']))
);
   $res = mysqli_query($link,$sql);
   if(mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
}else
$row = array();
}

$timeArray   = getTimeArray();
$timeOptions = DBout('');
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
                                Add Trivia Campaign
                                <input type="button" class="btn btn-primary" value="Back" id="btn_right"  onclick="window.location='view_campaigns.php'" />
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
                                    <input type="text" name="keyword" parsley-trigger="change" required placeholder="Enter keyword..." class="form-control" value="<?php echo DBout(!empty($row['keyword'])? $row['keyword'] : '')?>">
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
                                                $selected = DBout('selected="selected"');
                                            }else{
                                                $selected = DBout('');
                                            }
                                            ?>

                                            <option <?php echo DBout($selected) ?> value="<?php echo DBout($numbers['phone_number'])?>">
                                             <?php echo DBout($numbers['phone_number'])?></option>
                                             <?php
                                         }
                                     }
                                     ?>
                                 </select>
                             </div>
                             <div class="form-group">
                                <label><input name="attach_mobile_device"
                                        <?php
                                        if(!empty($row['attach_mobile_device'])) {
                                            if ($row['attach_mobile_device'] == '1')
                                                echo DBout('checked="checked"');
                                            else echo DBout('');
                                        }
                                        ?> value="1" type="checkbox" /> Attach mobile device</label>
                            </div>

                            <?php
                            if($_SESSION['user_type']=='1'){?>
                                <div class="form-group">
                                    <label><input name="share_with_subaccounts"
                                            <?php
                                            if(!empty($row['share_with_subaccounts'])) {
                                                if ($row['share_with_subaccounts'] == '1')
                                                    echo DBout('checked="checked"');
                                                else echo DBout('');
                                            }
                                        ?> value="1" type="checkbox" /> Share Campaign With Subaccounts</label>
                                </div>
                            <?php } ?>

                            <div class="col-lg-12 padding_left" >
                                <div class="portlet">
                                    <div class="portlet-heading bg-custom-trivia" id="bg-custom">
                                        <h5 id="h5"> 
                                            SMS/MMS
                                            <a onclick="slideToggleMainSection(this,'sms_texts_section','');" href="javascript:;"><i class="fa fa-minus" title="Open" id="fa_plus_sign"></i></a>
                                        </h5>
                                        <div class="portlet-widgets">
                                            <span class="divider"></span>
                                            <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5"></i></a>
                                        </div>
                                    </div>
                                    <div class="panel-collapse collapse in sms_texts_section" aria-expanded="true">
                                        <div class="portlet-body padding_top ">
                                            <div class="form-group">
                                                <label>Welcome Trivia SMS*</label>
                                                <textarea name="welcome_sms" parsley-trigger="change" required placeholder="Enter welcome sms text..." class="form-control textCounter"><?php echo DBout(!empty($row['welcome_sms']) ? $row['welcome_sms'] : '')?></textarea>
                                                <span class="showCounter">
                                                   <span class="showCount"><?php
                                                      echo !empty($row['welcome_sms']) ? DBout($maxLength-strlen($row['welcome_sms'])) : ''?>
                                                   </span> Characters left
                                               </span>
                                           </div>
                                           <div class="form-group">
                                            <label>Select Media</label>
                                            <input type="file" name="campaign_media" class="file"  />
                                            <input type="hidden" name="hidden_campaign_media" value="<?php echo DBout($row['media'])?>" />
                                            <?php
                                            if(!empty($row['media'])){
                                                echo isMediaExists($row['media']);
                                            }
                                            ?>
                                        </div>

                                        <div class="form-group">
                                            <label>Correct Answer SMS*</label>
                                            <textarea name="correct_sms" parsley-trigger="change" required placeholder="Enter sms text that will send on correct answer..." class="form-control textCounter"><?php echo DBout(!empty($row['correct_sms'])) ? $row['correct_sms'] : ''?></textarea>
                                            <span class="showCounter">
                                               <span class="showCount"><?php echo !empty($row['correct_sms']) ? DBout($maxLength-strlen($row['correct_sms'])) : ''?></span> Characters left
                                           </span>
                                       </div>

                                       <div class="form-group">
                                        <label>Wrong Answer SMS*</label>
                                        <textarea name="wrong_sms" parsley-trigger="change" required placeholder="Enter sms text that will send on wrong answer..." class="form-control textCounter"><?php echo DBout(!empty($row['wrong_sms']) ? $row['wrong_sms'] : '')?></textarea>
                                        <span class="showCounter">
                                           <span class="showCount"><?php echo !empty($row['wrong_sms']) ? DBout($maxLength-strlen($row['wrong_sms'])) : ''?></span> Characters left
                                       </span>
                                   </div>

                                   <div class="form-group">
                                    <label>Complete Trivia SMS*</label>
                                    <textarea name="complete_sms" parsley-trigger="change" required placeholder="Enter sms text that will send on complete trivia..." class="form-control textCounter"><?php echo DBout(!empty($row['complete_sms']) ? $row['complete_sms'] : '')?></textarea>
                                    <span class="showCounter">
                                       <span class="showCount"><?php echo !empty($row['complete_sms']) ? DBout($maxLength-strlen($row['complete_sms'])) : ''?></span> Characters left
                                   </span>
                               </div>

                               <div class="form-group">
                                <label>Already Member SMS*</label>
                                <textarea name="already_member_sms" parsley-trigger="change" required placeholder="Enter sms text for existing member..." class="form-control textCounter"><?php echo !empty($row['already_member_msg']) ? DBout($row['already_member_msg']) : ''?></textarea>
                                <span class="showCounter">
                                   <span class="showCount"><?php echo !empty($row['already_member_msg']) ? DBout($maxLength-strlen($row['already_member_msg'])) : ''?></span> Characters left
                               </span>
                           </div>



                       </div>
                   </div>
               </div>
           </div>

           <div class="height_both"></div>

           <div class="col-lg-12 padding_left">
            <div class="portlet">
                <div class="portlet-heading bg-custom-trivia"  id="bg-custom ">
                    <h5 id="h5">
                        Questions/Answers
                        <a onclick="slideToggleMainSection(this,'question_answer_section','');" href="javascript:;"><i class="fa fa-plus" title="Open" id="h5_right"></i></a>
                    </h5>
                    <div class="portlet-widgets">
                        <span class="divider"></span>
                        <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5" ></i></a>
                    </div>
                </div>
                <?php
                $display_question_section = 'display-2';
                if(!empty($_REQUEST['id'])) {
                    $questions_query = mysqli_query($link, "select * from trivia_questions where campaign_id = '" . $_REQUEST['id'] . "'");
                    $questions_res = mysqli_fetch_assoc($questions_query);
                    if (mysqli_num_rows($questions_query)) {
                        $display_question_section = 'display-1';
                    };
                }

                ?>
                <div class="panel-collapse collapse in question_answer_section <?php echo DBout($display_question_section)?>" id="Questions_Answer"  aria-expanded="true">
                    <div class="portlet-body padding_top">


                        <?php
                        $no_of_questions = 0;
                        if(!empty($_REQUEST['id'])){

                            $sql_q = sprintf("select * from trivia_questions where campaign_id=%s ",
                               mysqli_real_escape_string($link,DBin($_REQUEST['id']))
                           );

                            $res_q = mysqli_query($link,$sql_q);
                            $no_of_questions = DBout(mysqli_num_rows($res_q));
                        }
                        ?>



                        <input id="no_of_questions" value="<?php if($no_of_questions>0){ echo DBout($no_of_questions); }else{ echo DBout("1"); } ?>" type="hidden" />

                        <div id="questions">

                            <?php
                            if($no_of_questions > 0){
                                $q=1;
                                while($row_q = mysqli_fetch_assoc($res_q)){

                                    ?>
                                    <div id="question_<?php echo DBout($q);?>">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Question*</label>
                                                <textarea id="question_<?php echo DBout($q);?>" name="field[<?php echo DBout($q);?>][question]" placeholder="Enter welcome question sms text..." class="form-control textCounter"><?php echo DBout($row_q['question']); ?></textarea>
                                                <span class="showCounter">
                                                   <span class="showCount"></span> Characters left
                                               </span>
                                           </div>
                                       </div>


                                       <?php

                                       $sql_a = sprintf("select * from trivia_answers where question_id=%s ",
                                           mysqli_real_escape_string($link,DBin($row_q['id']))
                                       );


                                       $res_a = mysqli_query($link,$sql_a);
                                       $no_of_answers = DBout(mysqli_num_rows($res_a));

                                       ?>


                                       <div class="">
                                        <div class="col-md-5 labb" > Answer </div>
                                        <div class="col-md-2 labb"> Option </div>
                                        <div class="col-md-3 labb" align="center"> Correct Answer </div>
                                        <div class="col-md-2 labb" >  </div>
                                        <input id="no_of_ans_<?php echo DBout($q);?>" value="<?php if($no_of_answers>0){ echo DBout($no_of_answers); }else{ echo DBout("1"); } ?>" type="hidden" />
                                    </div>
                                    <div id="answers_<?php echo DBout($q);?>">
                                        <?php
                                        if($no_of_answers > 0){
                                            $a=1;
                                            while($row_a = mysqli_fetch_assoc($res_a)){
                                                ?>
                                                <div class="">
                                                    <div class="col-md-5"> <input name="field[<?php echo DBout($q);?>][answers][<?php echo DBout($a);?>][answer]" id="answer_<?php echo DBout($q);?>_<?php echo DBout($a);?>" value="<?php echo DBout($row_a['answer']); ?>" class="form-control" /> </div>
                                                    <div class="col-md-2"> <input name="field[<?php echo DBout($q);?>][answers][<?php echo DBout($a);?>][value]" id="value_<?php echo DBout($q);?>_<?php echo DBout($a);?>"  value="<?php echo DBout($row_a['value']); ?>"class="form-control" /> </div>
                                                    <div class="col-md-3"> <input name="field[<?php echo DBout($q);?>][answers][<?php echo DBout($a);?>][correct]" id="correct_<?php echo DBout($q);?>_<?php echo DBout($a);?>" <?php if($row_a['correct']==1){ echo DBout("checked"); } ?> class="form-control" value="1" type="checkbox" /> </div>
                                                    <div class="col-md-2" align="center">
                                                        <?php
                                                        if($a!="1"){?>
                                                            <button type="button" class="btn btn-danger" onclick="removeAnswer(this)"><i class="fa fa-remove"></i></button>
                                                            <?php
                                                        }
                                                        ?>
                                                        <button type="button" class="btn btn-success" onclick="addAnswer('<?php echo DBout($q);?>')" ><i class="fa fa-plus"></i></button>
                                                    </div>
                                                </div>
                                                <?php
                                                $a++;
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="col-md-12"> <hr /> </div>
                                </div>

                                <?php

                                $q++;
                            }
                        }
                        else{
                            ?>

                            <div id="question_1">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Question*</label>
                                        <textarea id="question_1" name="field[1][question]" placeholder="Enter welcome question sms text..." class="form-control textCounter"></textarea>
                                        <span class="showCounter">
                                           <span class="showCount"></span> Characters left
                                       </span>
                                   </div>
                               </div>
                               <div class="">
                                <div class="col-md-5 labb" align=""> Answer </div>
                                <div class="col-md-2 labb" align=""> Option </div>
                                <div class="col-md-3 labb" align="center"> Correct Answer </div>
                                <div class="col-md-2 labb" align="">  </div>
                                <input id="no_of_ans_1" value="1" type="hidden" />
                            </div>
                            <div id="answers_1">
                                <div class="">
                                    <div class="col-md-5"> <input name="field[1][answers][1][answer]" id="answer_1_1" class="form-control" /> </div>
                                    <div class="col-md-2"> <input name="field[1][answers][1][value]" id="value_1_1" class="form-control" /> </div>
                                    <div class="col-md-3"> <input name="field[1][answers][1][correct]" id="correct_1_1" class="form-control" value="1" type="checkbox" /> </div>
                                    <div class="col-md-2" align="center">
                                        <button type="button" class="btn btn-success" onclick="addAnswer('1')" ><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12"> <hr /> </div>
                        </div>
                        <?php
                    }
                    ?>

                </div>

                <div class="col-md-12">
                    <button type="button" class="btn btn-primary pull-right" onclick="addQuestion()" ><i class="fa fa-plus"></i> Add More Questions</button>
                </div>

            </div>
        </div>
    </div>
</div>




<div class="height_both"></div>
<div class="col-lg-12 padding_left">
    <?php
    if(!empty($row['double_optin_check'])) {
        if ($row['double_optin_check'] == '1') {
            $doubleOptInIcon = DBout('fa-minus');
            $doubleOptinCheck = DBout('checked=checked');
            $doubleOptinMainSection = DBout('diplay-1');
            $doubleOptinInnerSection = DBout('display-1');
        } else {
            $doubleOptInIcon = DBout('fa-plus');
            $doubleOptinCheck = DBout('display-2');
            $doubleOptinMainSection = DBout('display-2');
            $doubleOptinInnerSection = DBout('display-2');
        }
    }else {
        $doubleOptInIcon = DBout('fa-plus');
        $doubleOptinCheck = DBout('display-1');
        $doubleOptinMainSection = DBout('display-2');
        $doubleOptinInnerSection = DBout('display-2');
    }
    ?>
    <div class="portlet">
        <div class="portlet-heading bg-custom-trivia" id="bg-custom">
            <h5 id="h5">
                Make the campaign double opt-in
                <a onclick="slideToggleMainSection(this,'double_optin_section','doubleOptInCheck');" href="javascript:;"><i class="fa <?php echo DBout($doubleOptInIcon);?>" title="Open" id="h5_right"></i></a>
            </h5>
            <div class="portlet-widgets">
                <span class="divider"></span>
                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5"></i></a>
            </div>
        </div>
        <div class="panel-collapse collapse double_optin_section <?php echo DBout($doubleOptinMainSection);?>"  aria-expanded="true">
            <div class="portlet-body padding_top">
                <div class="form-group">
                    <label><input <?php echo DBout(!empty($doubleOptinCheck)? $doubleOptinCheck : '')?> type="checkbox" name="double_optin_check" value="1" onClick="slideToggleInnerSection(this,'doubleOptInSection')" /> Enable Double Opt-in</label>
                </div>
                <div class="form-group doubleOptInSection <?php echo DBout($doubleOptinInnerSection);?>">
                    <label>Double Opt-in SMS*</label>
                    <textarea name="double_optin" placeholder="Enter double opt-in text..." class="form-control textCounter"><?php echo DBout(!empty($row['double_optin']) ? $row['double_optin'] : '')?></textarea>
                    <span class="showCounter">
                       <span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
                   </span>
               </div>
               <div class="form-group doubleOptInSection <?php echo DBout($doubleOptinInnerSection)?>">
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
<div class="height_both"></div>
<div class="col-lg-12 padding_left">
    <?php
    $mainSection = DBout('display-2');
    $getEmailIcon = DBout('fa-plus');
    if(!empty($row['get_email'])) {
        if ($row['get_email'] == '1') {
            $getEmailIcon = DBout('fa-minus');
            $getEmailCheck = DBout('checked=checked');
            $mainSection = DBout('display-1');
            $getEmailInnerSection = DBout('display-1');
        } else {
            $getEmailCheck = DBout('display-2');
            $getEmailInnerSection = DBout('display-2');
             }
    } else {
            $getEmailCheck = DBout('display-2');
            $getEmailInnerSection = DBout('display-2');
    }
    if(!empty($row['get_email'])) {
        if ($row['get_subs_name_check'] == '1') {
            $getEmailIcon = DBout('fa-minus');
            $getNameCheck = DBout('checked=checked');
            $mainSection = DBout('display-1');
            $getNameInnerSection = DBout('display-1');
        }else {
            $getNameCheck = DBout('display-2');
            $getNameInnerSection = DBout('display-2');
        }
    } else {
        $getNameCheck = DBout('display-2');
        $getNameInnerSection = DBout('display-2');
    }
    ?>
    <div class="portlet">
        <div class="portlet-heading bg-custom-trivia" id="bg-custom">
            <h5 id="h5">
                Get subscriber name/email
                <a onclick="slideToggleMainSection(this,'get_email_section','get_email');" href="javascript:;"><i class="fa <?php echo DBout($getEmailIcon)?>" title="Open" id="h5_right"></i></a>
            </h5>
            <div class="portlet-widgets">
                <span class="divider"></span>
                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5"></i></a>
            </div>
        </div>
        <div class="panel-collapse collapse get_email_section <?php echo DBout($mainSection)?>"  aria-expanded="true">
            <div class="portlet-body padding_top">
                <div class="form-group">
                    <label class="checkbox-inline"><input type="checkbox" name="get_subs_email" <?php echo DBout($getEmailCheck)?> value="1" onClick="slideToggleInnerSection(this,'subsEmailSection')" /> Get subscriber email</label>
                </div>
                <div class="form-group subsEmailSection <?php echo DBout($getEmailInnerSection)?>">
                    <label>Message to get subscriber Email</label>
                    <textarea name="reply_email" parsley-trigger="change" placeholder="Enter sms to ask for email..." class="form-control textCounter"><?php echo DBout(!empty($row['reply_email']) ? $row['reply_email'] : '')?></textarea>
                    <span class="showCounter">
                       <span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
                   </span>
               </div>
               <div class="form-group subsEmailSection <?php echo DBout($getEmailInnerSection)?>">
                <label>Email Received Confirmation Message</label>
                <textarea name="email_updated" parsley-trigger="change" placeholder="Confirmation sms text for receiving email..." class="form-control textCounter"><?php echo DBout(!empty($row['email_updated']) ? $row['email_updated'] : '')?></textarea>
                <span class="showCounter">
                   <span class="showCount"><?php echo DBout($maxLength)?></span> Characters left
               </span>
           </div>

           <div class="form-group">
            <label class="checkbox-inline"><input type="checkbox" name="get_subs_name_check" <?php echo DBout($getNameCheck)?> onClick="slideToggleInnerSection(this,'subsNameSection')" value="1" /> Get subscriber name</label>
        </div>
        <div class="subsNameSection <?php echo DBout($getNameInnerSection)?>" >
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
<div class="height_top"></div>
<div class="col-lg-12 padding_left">
    <?php
    if (!empty($row['campaign_expiry_check'])) {
        if ($row['campaign_expiry_check'] == '1') {
            $campExpiryIcon = DBout('fa-minus');
            $campaignExpiryCheck = DBout('checked=checked');
            $campaignExpirySection = DBout('display-1');
            $campaignExpiryInnerSection = DBout('display-1');
        }
    }else {
        $campExpiryIcon = DBout('fa-plus');
        $campaignExpiryCheck = DBout('display-2');
        $campaignExpirySection = DBout('display-2');
        $campaignExpiryInnerSection = DBout('display-2');
    }
    ?>
    <div class="portlet">
        <div class="portlet-heading bg-custom-trivia" id="bg-custom">
            <h5 id="h5">
                Activate campaign for limited time
                <a onclick="slideToggleMainSection(this,'campaign_expity_section','check_campaign_expiry');" href="javascript:;"><i class="fa <?php echo DBout($campExpiryIcon)?>" title="Open" id="h5_right"></i></a>
            </h5>
            <div class="portlet-widgets">
                <span class="divider"></span>
                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5" ></i></a>
            </div>
        </div>
        <div class="panel-collapse collapse campaign_expity_section <?php echo DBout($campaignExpirySection)?>"  aria-expanded="true">
            <div class="portlet-body padding_top">
                <div class="form-group">
                    <label><input type="checkbox" name="campaign_expiry_check" <?php echo DBout(!empty($campaignExpiryCheck) ? $campaignExpiryCheck : '')?> onClick="slideToggleInnerSection(this,'campaignExpirySection')" value="1" /> Enable/Disable</label>
                </div>
                <div class="campaignExpirySection <?php echo DBout($campaignExpiryInnerSection)?>" >
                    <div class="col-md-6 padding_left">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="text" class="form-control addDatePicker" name="start_date" placeholder="Start date." value="<?php echo DBout(!empty($row['start_date']) ? $row['start_date'] : '')?>">
                        </div>
                    </div>
                    <div class="col-md-6 padding_left">
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
<div  class="height_both"></div>
<div class="col-lg-12 padding_left">
    <?php
    if(!empty($row['followup_msg_check'])) {
        if ($row['followup_msg_check'] == '1') {
            $followUpIcon = DBout('fa-minus');
            $followUpCheck = DBout('checked=checked');
            $followUpSection = DBout('display-1');
            $followUpInnerSection = DBout('display-1');
        }
    }else {
        $followUpIcon = DBout('fa-plus');
        $followUpCheck = DBout('display-2');
        $followUpSection = DBout('display-2');
        $followUpInnerSection = DBout('display-2');
    }
    ?>
    <div class="portlet">
        <div class="portlet-heading bg-custom-trivia" id="bg-custom">
            <h5 id="h5">
                Add Delay Messages for this campaign.
                <a onclick="slideToggleMainSection(this,'follow_up_msg_section','');" href="javascript:void(0);"><i class="fa <?php echo DBout($followUpIcon)?>" title="Add More" id="h5_right"></i></a>
            </h5>
            <div class="portlet-widgets">
                <span class="divider"></span>
                <a href="#bg-primary" data-parent="#accordion1" data-toggle="collapse" class="" aria-expanded="true"><i class="ion-minus-round" title="Show/Hide" id="h5"></i></a>
            </div>
        </div>
        <div class="panel-collapse collapse follow_up_msg_section <?php echo DBout($followUpSection)?>" id="bg-primary"  aria-expanded="true">
            <div class="form-group padding_top">
                <label><input type="checkbox" <?php echo DBout(!empty($followUpCheck) ? $followUpCheck :'' )?> name="followup_msg_check" value="1" onClick="slideToggleInnerSection(this,'followUpContainer')" /> Enable/Disable</label>
            </div>
            <div class="portlet-body followUpContainer <?php echo DBout($followUpInnerSection)?>" id="followUpContainer">
                <?php
                $totalFollowUp=0;
                if(!empty($row['id'])) {
                    $sqlFollow = sprintf("select * from follow_up_msgs where group_id=S%", mysqli_real_escape_string($link, DBin($row['id'])));
                    $resFollow = mysqli_query($link, $sqlFollow);
                    $totalFollowUp = DBout(mysqli_num_rows($resFollow));
                }
                if($totalFollowUp==0){
                    ?>
                    <div>
                        <table width="100%" class="delay_table">
                            <tr>
                                <td width="25%">Select Days/Time</td>
                                <td>
                                    <input type="text" class="form-control numericOnly" id="date_time"  placeholder="Days delay..." name="delay_day[]" value="0" onblur="switchTimeDropDown(this)">&nbsp;
                                    <select class="form-control timeDropDown" id="timedropdwon"  name="delay_time[]">
                                        <?php
                                        $timeArray = getTimeArray();
                                        foreach($timeArray as $key => $value){
                                         ?>
                                         <option value="<?php echo DBout($key) ?>"><?php echo DBout($value) ?></option>
                                         <?php

                                     }
                                     ?>
                                 </select>
                                 <select class="form-control hoursDropDown" id="hoursDropDown" name="delay_time_hours[]">
                                    <?php
                                    echo $options;
                                    ?>
                                </select>
                                <span id="span_pointer"  onClick="addMoreFollowUpMsg()"><i class="fa fa-plus" title="Add More" id="plus_green"></i></span>
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
        }
                else{
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
                                <?php

                                ?>
                                <input type="text" class="form-control numericOnly" id="numericOnlys"  placeholder="Days delay..." name="delay_day[]" value="<?php echo DBout($rowFollow['delay_day'])?>" onblur="switchTimeDropDown(this)">&nbsp;
                                <select class="form-control timeDropDown <?php echo DBout($timeList)?>" id="timeDropDown"  name="delay_time[]">
                                    <?php
                                    $timeArray = getTimeArray();
                                    foreach($timeArray as $key => $value){
                                        if($key == $rowFollow['delay_time'])
                                            $sel = DBout('selected="selected"');
                                        else
                                            $sel = DBout('');
                                        ?>

                                        <option <?php echo DBout($sel) ?> value="<?php echo DBout($key)?>"><?php echo DBout($value)?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <select class="form-control hoursDropDown <?php echo DBout($hoursList)?>" id="hoursDropDown"  name="delay_time_hours[]">
                                    <?php
                                    for($i=1; $i<=23; $i++){
                                        if($i > 1)
                                            $hour = DBout('hours');
                                        else
                                            $hour = DBout('hour');

                                        if($rowFollow['delay_time'] == '+'.$i.' '.$hour)
                                            $selh = DBout('selected="selected"');
                                        else
                                            $selh = ('');


                                        ?>



                                        <option <?php echo DBout($selh) ?> value="+ .<?php echo DBout($i)?> <?php echo DBout($hour) ?>">After <?php echo DBout($i) ?> <?php ucfirst($hour) ?></option>


                                        <?php
                                    }
                                    ?>
                                </select>
                                <?php
                                if($index=='0'){
                                    ?>
                                    <span id="span_pointer" onClick="addMoreFollowUpMsg()"><i class="fa fa-plus" title="Add More" id="plus_green"></i></span>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Message</td>
                            <td>
                                <textarea name="delay_message[]" class="form-control textCounter"><?php echo DBout($rowFollow['message'])?></textarea>
                                <span class="showCounter">
                                   <span class="showCount"><?php echo DBout($maxLength-strlen($rowFollow['message']))?></span> Characters left
                               </span>
                           </td>
                       </tr>
                       <tr>
                        <td  class="attach_media">Attach Media</td>
                        <td>
                            <input type="hidden" name="hidden_delay_media[]" value="<?php echo DBout($rowFollow['media'])?>">
                            <input type="file" name="delay_media[]" id="file" ><span class="fa fa-trash" id="fa_trash" title="Remove Message" onclick="removeFollowUp(this)"></span><br>
                            <?php
                            if(trim($rowFollow['media'])!=''){
                                echo DBout(isMediaExists($rowFollow['media']));
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if(($index+1)!=$totalFollowUp){?>
                        <tr><td colspan="2"><hr id="hr_line"></td></tr>
                    <?php }$index++;?>
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
<div class="height_both"></div>
<div class="form-group text-right m-b-0">
    <button class="btn btn-primary waves-effect waves-light" type="submit"> Save </button>
    <button type="reset" class="btn btn-default waves-effect waves-light m-l-5" onclick="window.location = 'javascript:history.go(-1)'"> Cancel </button>
    <input type="hidden" name="cmd" value="add_trivia" />
    <input type="hidden" name="campaign_id" value="<?php echo DBout($row['id'])?>" />
</div>
</form>
</div>
</div>
</div>
</div>


<div id="answer_structure">
    <div class="">
        <div class="col-md-5"> <input name="field[q-no][answers][a-no][answer]" id="answer_q-no_a-no" class="form-control" /> </div>
        <div class="col-md-2"> <input name="field[q-no][answers][a-no][value]" id="value_q-no_a-no" class="form-control" /> </div>
        <div class="col-md-3"> <input name="field[q-no][answers][a-no][correct]" id="correct_q-no_a-no" class="form-control" value="1" type="checkbox" /> </div>
        <div class="col-md-2" align="center">
            <button type="button" class="btn btn-danger" onclick="removeAnswer(this)"><i class="fa fa-remove"></i></button>
            <button type="button" class="btn btn-success" onclick="addAnswer('q-no')" ><i class="fa fa-plus"></i></button>
        </div>
    </div>
</div>

<div id="question_structure">
    <div id="question_q-no">
        <div class="col-md-12">
            <div class="form-group">
                <label>Question*</label>
                <textarea id="question_q-no" name="field[q-no][question]" placeholder="Enter welcome question sms text..." class="form-control textCounter"></textarea>
                <span class="showCounter">
                    <span class="showCount"></span> Characters left
                </span>
            </div>
        </div>
        <div class="">
            <div class="col-md-5 labb" align=""> Answer </div>
            <div class="col-md-2 labb" align=""> Option </div>
            <div class="col-md-3 labb" align="center"> Correct Answer </div>
            <div class="col-md-2 labb" align="">  </div>
            <input id="no_of_ans_q-no" value="1" type="hidden" />
        </div>
        <div id="answers_q-no">
            <div class="">
                <div class="col-md-5"> <input name="field[q-no][answers][a-no][answer]" id="answer_q-no_a-no" class="form-control" /> </div>
                <div class="col-md-2"> <input name="field[q-no][answers][a-no][value]" id="value_q-no_a-no" class="form-control" /> </div>
                <div class="col-md-3"> <input name="field[q-no][answers][a-no][correct]" id="correct_q-no_a-no" class="form-control" value="1" type="checkbox" /> </div>
                <div class="col-md-2" align="center">
                    <button type="button" class="btn btn-success" onclick="addAnswer('q-no')" ><i class="fa fa-plus"></i></button>
                </div>
            </div>
        </div>
        <div class="col-md-12"> <hr /> </div>
    </div>
</div>
</div>
</div>
<?php include_once("footer_info.php");?>
</div>
<?php include_once("footer.php");?>
<script type="text/javascript" src="scripts/js/parsley.min.js"></script>
<script src="scripts/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript" src="scripts/js/parsley.min.js"></script>
<script src="scripts/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript" src="js/add_trivias.js"></script>