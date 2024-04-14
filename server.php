<?php
	session_start();
	include_once("database.php");
	include_once("functions.php");
	$cmd = DBin($_REQUEST['cmd']);

	if(isset($_SESSION['message']) && trim($_SESSION['user_id'])!=''){
		$appSettings = getAppSettings($_SESSION['user_id']);
	}else{
		$appSettings=0;
	}
	if(isset($_SESSION['message']) && trim($_SESSION['user_id'])!=''){
		if (count($appSettings) == 0) {
			$_SESSION['no_settings'] = 'Error! Please add settings before using application under settings tab in side bar';
			header("location: dashboard.php");
		} else {
			unset($_SESSION['no_settings']);
			setTimeZone($_SESSION['user_id']);
		}
	}
	if(isset($_REQUEST['beacon_url_type']) && $_REQUEST['beacon_url_type']!=1){
		$_REQUEST['beacon_url_type']=0;
	}
	if(isset($_REQUEST['coupon']) && $_REQUEST['coupon']!=1){
		$_REQUEST['coupon']=0;
	}
	if(isset($_REQUEST['share_with_subaccounts']) && $_REQUEST['share_with_subaccounts']==1){
		$_REQUEST['share_with_subaccounts']=1;
	}else{
		$_REQUEST['share_with_subaccounts']=0;
	}
	if(!isset($_REQUEST['winning_number']) || $_REQUEST['winning_number']==""){
		$_REQUEST['winning_number']=0;
	}
	$adminSettings = getAppSettings($_SESSION['user_id'],true);

	switch($cmd){
		case "load_admin_numbers":{
			$smsGateway = $adminSettings['sms_gateway'];
			if($smsGateway == 'signalwire'){
				$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/AvailablePhoneNumbers/US/Local.json?AreaCode=".$_REQUEST['areacode'];
				$ch = curl_init();curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
				$data = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($data,true); 
				//echo '<pre>';
				//print_r($data);
				$sr = 1;
				for($i=0; $i < count($data['available_phone_numbers']); $i++){
					if($sr <= 15){
						if($sr == 15)
							$sel = 'checked=checked';
				?>
					<label style="border: 1px solid #d9d9;padding: 5px;border-radius: 7px;margin: 5px;"><input type="radio" name="buy_signalwire_num" <?php echo $sel?> class="buy_signalwire_num" value="<?php echo $data['available_phone_numbers'][$i]['phone_number']?>"> <?php echo $data['available_phone_numbers'][$i]['phone_number']?></label>
				<?php
					}
					$sr++;
				} 
			}else if($smsGateway == 'twilio'){
				
			}else if($smsGateway == 'plivo'){
				
			}else if($smsGateway == 'nexmo'){
				
			}
		}
		break;
			
		case "confirm_signup_email":{
			$wuid = decode($_REQUEST['wuid']);
			$name = $_REQUEST['name'];
			$sql = "select * from web_user_info where id='".$wuid."' and is_verified='0'";
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$row = mysqli_fetch_assoc($res);
				$appSettings = getAppSettings("",true);
				$appUrl		 = getServerUrl();
				$password	 = $row['password'];
				$encryptedPassword = password_hash($password,PASSWORD_DEFAULT);
				$ins = sprintf("insert into users
                                        (
                                                first_name,
                                                last_name,
                                                email,
                                                password,
                                                type,
                                                parent_user_id,
                                                business_name,
                                                tcap_ctia,
                                                msg_and_data_rate,
                                                city,
                                                state,
                                                response,
                                                response_code,
                                                subscription_id,
                                                paypal_subscriber_id,
												phone_number
                                        )
                                        values
                                        (
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '2',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
                                                '%s',
												'%s'
                                        )",
										mysqli_real_escape_string($link,DBin($row['first_name'])),
										mysqli_real_escape_string($link,DBin($row['last_name'])),
										mysqli_real_escape_string($link,$row['email']),
										mysqli_real_escape_string($link,$encryptedPassword),
										mysqli_real_escape_string($link,DBin($row['parent_user_id'])),
										mysqli_real_escape_string($link,DBin($row['business_name'])),
										mysqli_real_escape_string($link,DBin($row['tcap_ctia'])),
										mysqli_real_escape_string($link,DBin($row['msg_and_data_rate'])),
										mysqli_real_escape_string($link,DBin($row['city'])),
										mysqli_real_escape_string($link,DBin($row['state'])),
										mysqli_real_escape_string($link,DBin($row['month'])),
										mysqli_real_escape_string($link,DBin($row['response'])),
										mysqli_real_escape_string($link,DBin($row['response_code'])),
										mysqli_real_escape_string($link,DBin($row['subscription_id'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['subscr_id'])),
							   			mysqli_real_escape_string($link,DBin($_REQUEST['mobile_number']))
                    );
				$exe = mysqli_query($link,$ins)or die(mysqli_error($link));
				if($exe){
					$userID	= mysqli_insert_id($link);
					$sql1 = sprintf("delete from web_user_info where id=%s ",
                            mysqli_real_escape_string($link,$row['id'])
                        );
					mysqli_query($link,$sql1);
					
					$subAccountSid 	 = '';
					$subAccountToken = '';
					$sql2 = sprintf("insert into application_settings
                                                (
													twilio_sid,
													twilio_token,
													user_id,
													user_type
                                                )
                                                values
					                            (
													'%s',
													'%s',
													'%s',
													'2'
					                            )",
												mysqli_real_escape_string($link,DBin($subAccountSid)),
												mysqli_real_escape_string($link,DBin($subAccountToken)),
												mysqli_real_escape_string($link,DBin($userID))
                                                );
					mysqli_query($link,$sql2);
					
					$sqlp = "select * from package_plans where is_free='1' limit 1";
					$resp = mysqli_query($link,$sqlp);
					$pkgInfo = mysqli_fetch_assoc($resp);
					
					$_REQUEST['item_name'] = DBout($pkgInfo['title']." SMS Plan");
					
					$today	= date('Y-m-d H').':00:00';
					$endDate= date('Y-m-d H:i',strtotime('+1 month'.$today));
					$insPkg =
                        sprintf("insert into user_package_assignment
								(
									user_id,
									pkg_id,
									start_date,
									end_date,
									sms_credits,
									phone_number_limit,
									iso_country,
									pkg_country
							    )
							    values
								(
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
								mysqli_real_escape_string($link,DBin($userID)),
									mysqli_real_escape_string($link,DBin($pkgInfo['id'])),
									mysqli_real_escape_string($link,DBin($today)),
									mysqli_real_escape_string($link,DBin($endDate)),
									mysqli_real_escape_string($link,DBin($pkgInfo['sms_credits'])),
									mysqli_real_escape_string($link,DBin($pkgInfo['phone_number_limit'])),
									mysqli_real_escape_string($link,DBin($pkgInfo['iso_country'])),
									mysqli_real_escape_string($link,DBin($pkgInfo['country']))
                                );
					mysqli_query($link,$insPkg);
					
					
					header("location: https://rubee.ai/verified/".$userID."/".$row['first_name']);
					
					/*
					$subject = $appSettings['email_subject'];
					$to		 = $row['email'];
					$from	 = 'admin@'.$_SERVER['SERVER_NAME'];
					$msg	 = $appSettings['new_app_user_email'];
					$msg	 = str_replace('%first_name%',$row['first_name'],$msg);
					$msg	 = str_replace('%last_name%',$row['last_name'],$msg);
					$msg	 = str_replace('%login_email%',$row['email'],$msg);
					$msg	 = str_replace('%login_pass%',$password,$msg);
					$msg	 = str_replace('%login_url%',$appUrl,$msg);
					$FullName= 'Admin';
					sendEmail($subject,$to,$from,$msg,$FullName);

					$subject = $appSettings['email_subject_for_admin_notification'];
					$to		 = $appSettings['admin_email'];
					$from	 = 'admin@'.$_SERVER['SERVER_NAME'];
					$msg	 = str_replace('%email%',$row['email'],$appSettings['new_app_user_email_for_admin']);
					$FullName= 'Admin';
					sendEmail($subject,$to,$from,$msg,$FullName);
					*/
				}
			}
			else{
				echo 'Link expired.';
			}
		}
		break;
			
		case "send_confirmation_email":{
			$firstName = $_REQUEST['fistName'];
			$lastName  = $_REQUEST['lastName'];
			$email	   = $_REQUEST['email'];
			$password  = $_REQUEST['password'];
			
			$from = "support@".$_SERVER['SERVER_NAME'];
			$to   = $email;
			/*
			$body = $firstName.", Welcome to rubee! Before you can get started, we need to verify your account.<br>";
			$body.= "Please enter your mobile number for account verification. We won't share it with or show it to anyone else.";
			*/
			
			$sel = "select id from web_user_info where email='".$email."'";
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = "insert into web_user_info
							(
								first_name,
								last_name,
								email,
								password
							)
						values
							(
								'".$firstName."',
								'".$lastName."',
								'".$email."',
								'".$password."'
							)";
				$res = mysqli_query($link,$sql);
				if($res){
					$webUserID = mysqli_insert_id($link);
				}
				$body = $firstName.", Welcome to rubee! please click on the link below to verify your email address.<br>";
				$body.= 'https://rubee.ai/sms/server.php?cmd=confirm_signup_email&wuid='.encode($webUserID).'&name='.$firstName;
				$FullName = 'Rubee';
				$subject  = 'Welcome to Rubee!';
				sendEmail($subject,$to,$from,$body,$FullName);
				echo '1';
			}else{
				echo '0';
			}
		}
		break;
			
		case "download_apk":{
			downloadFile('rubee.apk');
		}
		break;
			
		case "create_signup":{
			$countryCode = '+'.$_REQUEST['countryCode'];
			$mobileNumber = $countryCode.$_REQUEST['mobile_number'];
			$freeNumber = $_REQUEST['buy_signalwire_num'];
			$userID = $_REQUEST['user_id'];
			
			$phoneNumber = $freeNumber;
			$data = array(
				"FriendlyName" => $phoneNumber,
				"PhoneNumber" => $phoneNumber,
				"SmsUrl" => getServerUrl().'/sms_controlling.php'
			);

			$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/IncomingPhoneNumbers.json";
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
			$data = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($data,true);
			$sid = $data['sid'];
			$sql = sprintf("insert into users_phone_numbers
								(
									friendly_name,
									phone_number,
									iso_country,
									country,
									phone_sid,
									type,
									user_id
								)
							values
								(
									'%s',
									'%s',
									'US',
									'United States',
									'%s',
									'5',
									'%s'
								)",
									mysqli_real_escape_string($link,$phoneNumber),
									mysqli_real_escape_string($link,$phoneNumber),
									mysqli_real_escape_string($link,$sid),
									mysqli_real_escape_string($link,$userID)
				);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			
			$userPkgInfo = getAssingnedPackageInfo($userID);
			$userInfo = getUserInfo($userID);
			$_SESSION['sms_credits'] = $userPkgInfo['sms_credits'];
			$_SESSION['used_sms_credits'] = $userPkgInfo['used_sms_credits'];
			$_SESSION['pkg_end_date'] = $userPkgInfo['end_date'];
			$_SESSION['pkg_status'] = $userPkgInfo['status'];
			$_SESSION['sms_gateway'] = $adminSettings['sms_gateway'];
			$_SESSION['first_name'] = $userInfo['first_name'];
			$_SESSION['last_name']  = $userInfo['last_name'];
			$_SESSION['user_id']    = $userID;
			$_SESSION['user_type']  = '2';
			$_SESSION['business_name']  = 'SMS Tool';
		}
		break;
		
		case "send_verification_code":{
			$mobileNumber = urldecode($_REQUEST['mobileNumber']);
			$countryCode  = '+'.$_REQUEST['countryCode'];
			$mobileNumber = $countryCode.$mobileNumber;
			
			$fromNumber   = getTwilioNumber();
			$code = generateVerificationCode();
			$remainingCredits = '3';
			sendMessage($fromNumber,$mobileNumber,$code,array(),"","");
			echo $code;
		}
		break;
			
		case "delete_mobile_device":{
			$deviceID = DBin($_REQUEST['deviceID']);
			$sql = sprintf("delete from mobile_devices where id='%s' and user_id='%s'",
						mysqli_real_escape_string($link,$deviceID),
						mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
					);

			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! device deleted successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! failed to delete';
			}
		}
		break;

		case "remove_signalwire_number":{
			$numberSid = DBin($_REQUEST['numberSid']);
			$url = "https://".$appSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$appSettings['signalwire_project_key']."/IncomingPhoneNumbers/".$numberSid.".json";
			$data = array(
				"SmsUrl" => 'example.com'
			);
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_USERPWD,$appSettings['signalwire_project_key'].":".$appSettings['signalwire_token']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
			$data = curl_exec($ch);
			curl_close($ch);
			$_SESSION['message_status'] = 0;
			$_SESSION['message'] = 'Success! Number is successfully un-assigned';
		}
			break;

		case "check_api_key":{
				$api_key = DBin($_REQUEST['api_key']);
				$sql = sprintf("select * from application_settings where api_key='%s'",
						mysqli_real_escape_string($link,$api_key)
					);
				$res = mysqli_query($link,$sql);
				$query = mysqli_fetch_assoc($res);
				if(count($query) > 0){
					echo DBout('{"success":"api key matach"}');
				}    
				else{
				   return header('Content-Type: application/json; charset=utf-8', true, 401);
				}
		}
		break;
		case "update_signalwire_number":{
			$numberSid = DBin($_REQUEST['numberSid']);
			$number = DBin($_REQUEST['number']);

			$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/IncomingPhoneNumbers/".$numberSid.".json";
			$data = array(
				"SmsUrl" => getServerUrl().'/sms_controlling.php'
			);
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
			$data = curl_exec($ch);
			curl_close($ch);
			print_r($data);
			$sel = sprintf("select id from users_phone_numbers where phone_number='%s' and user_id='%s'",
							mysqli_real_escape_string($link,DBin($number)),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				/*
				echo $sql = sprintf("insert into users_phone_numbers
									(
										friendly_name,
										phone_number,
										iso_country,
										country,
										phone_sid,
										type,
										user_id
									)
								values
									(
										'%s',
										'%s',
										'US',
										'United States',
										'%s',
										'5',
										'%s'
									)",
										mysqli_real_escape_string($link,DBin($number)),
										mysqli_real_escape_string($link,DBin($number)),
										mysqli_real_escape_string($link,DBin($numberSid)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
					);
				*/
				$sql = "insert into users_phone_numbers
									(
										friendly_name,
										phone_number,
										iso_country,
										country,
										phone_sid,
										type,
										user_id
									)
								values
									(
										'".$number."',
										'".$number."',
										'US',
										'United States',
										'".$numberSid."',
										'5',
										'".$_SESSION['user_id']."'
									)";
				$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			}
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Success! Number is successfully assigned';
		}
		break;

		case "get_existing_signalwire_numbers":{
			if($_SESSION['user_type']=='1'){
				$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/IncomingPhoneNumbers.json";
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
				$data = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($data,true);
			   ?>
					<table class="table table-striped table-hover">
						<theady>
						<th>Sr#</th>
						<th>Friendly Name</th>
						<th>Phone Number</th>
						<th>Currently Install</th>
						<th>Capabilities</th>
						<th>Manage</th>
					</theady>
						<tbody>
				<?php
							if(count($data["incoming_phone_numbers"]) > 0){
						$index = 1;
						for($i=0; $i < count($data["incoming_phone_numbers"]); $i++){
							?>
							<tr>
							<td><?php echo DBout($index++) ?></td>
							<td><?php echo DBout($data["incoming_phone_numbers"][$i]['friendly_name'])?></td>
							<td><?php echo DBout($data["incoming_phone_numbers"][$i]['phone_number'])?></td>
							<td><?php echo DBout($data["incoming_phone_numbers"][$i]['sms_url'])?></td>
							<td>
								<?php
							if($data["incoming_phone_numbers"][$i]['capabilities']['voice']=='1'){ ?>
									Voice <img src="images/tick.gif">
								   <?php     }else{ ?>
											Voice <img src="images/cross.gif">
								   <?php     }
										if($data["incoming_phone_numbers"][$i]['capabilities']['sms']=='1'){ ?>
											SMS <img src="images/tick.gif">
								   <?php     }else{ ?>
											SMS <img src="images/cross.gif">
								   <?php     } ?>
									   <?php if($data["incoming_phone_numbers"][$i]['capabilities']['mms']=='1'){ ?>
										   MMS <img src="images/tick.gif">
								   <?php     }else{ ?>
										   MMS <img src="images/cross.gif">
								   <?php    } ?>
							</td>
							<td align="center">
								   <?php
								if($_SESSION['user_type']=='1'){ ?>
									<img src="images/add-number.png" title="Add Number" class="add_number_style" onclick="addSignalWireNumberToInstall('<?php echo DBout($data["incoming_phone_numbers"][$i]['sid'])?>','<?php echo DBout($data["incoming_phone_numbers"][$i]['phone_number'])?>')">
							   <?php    } ?>
								<img src="images/cross.png" width="20"  class="pointer" title="Release Number" onclick="removeSignalWireNumberFromInstall('<?php echo DBout($data["incoming_phone_numbers"][$i]['sid'])?>','<?php echo DBout($data["incoming_phone_numbers"][$i]['phone_number'])?>')">
							</td>
							</tr>

					<?php    }
					}else{ 
				?>
								<tr><td colspan="5">No number found.</td></tr>
				<?php    
					}
				?>
						</tbody>
					</table>
		  <?php  
			}
			else{
		?>
				<table class="table table-striped table-hover">
					<theady>
						<tr>
							<th>Sr#</th>
							<th>Friendly Name</th>
							<th>Phone Number</th>
						</tr>
					</theady>
					<tbody>
					<?php
						$sql = "select * from users_phone_numbers where user_id='".$_SESSION['user_id']."'";
						$res = mysqli_query($link,$sql);
						if(mysqli_num_rows($res)){
							while($row = mysqli_fetch_assoc($res)){
					?>
								<tr>
									<td><?php echo $index++; ?></td>
									<td><?php echo $row['phone_number'];?></td>
									<td><?php echo $row['phone_number'];?></td>
								</tr>
					<?php
							}
						}else{
					?>
								<tr>
									<td colspan="3">No number found.</td>
								</tr>
					<?php
						}
					?>	
					</tbody>
				</table>
		<?php
			}
		}
		break;

		case "buy_signalwire_number":{
			$phoneNumber = $_REQUEST['phoneNumber'];
			$data = array(
				"FriendlyName" => $phoneNumber,
				"PhoneNumber" => $phoneNumber,
				"SmsUrl" => getServerUrl().'/sms_controlling.php'
			);

			$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/IncomingPhoneNumbers.json";
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
			$data = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($data,true);
			$sid = $data['sid'];
			$sql = sprintf("insert into users_phone_numbers
								(
									friendly_name,
									phone_number,
									iso_country,
									country,
									phone_sid,
									type,
									user_id
								)
							values
								(
									'%s',
									'%s',
									'US',
									'United States',
									'%s',
									'5',
									'%s'
								)",
									mysqli_real_escape_string($link,$phoneNumber),
									mysqli_real_escape_string($link,$phoneNumber),
									mysqli_real_escape_string($link,$sid),
									mysqli_real_escape_string($link,$_SESSION['user_id'])
				);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! number purchased successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! an error occured during process';
			}
		}
		break;

		case "get_signalewire_numbers_areacode":{
			$url = "https://".$adminSettings['signalwire_space_url']."/api/laml/2010-04-01/Accounts/".$adminSettings['signalwire_project_key']."/AvailablePhoneNumbers/US/Local.json?AreaCode=".DBin($_REQUEST['areacode']);
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_USERPWD,$adminSettings['signalwire_project_key'].":".$adminSettings['signalwire_token']);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
			$data = curl_exec($ch);
			curl_close($ch);
			$data = json_decode($data,true); ?>
				<table width="100%" align="center" class="table table-striped table-bordered table-hover">
				<tr>
				<td></td>
				<td>Sr#</td>
				<td>Friendly Name</td>
				<td>Phone Number</td>
				<td>Country</td>
				<td>Capabilities</td>
				</tr>
			<?php
			$sr = 1;
			for($i=0; $i < count($data['available_phone_numbers']); $i++){  ?>
					<tr>
					<td><input type="radio" name="buy_signalwire_num" class="buy_signalwire_num" value="<?php echo DBout($data['available_phone_numbers'][$i]['phone_number'])?>"></td>
					<td><?php echo DBout($sr++)?></td>
					<td><?php echo DBout($data['available_phone_numbers'][$i]['friendly_name'])?></td>
					<td><?php echo DBout($data['available_phone_numbers'][$i]['phone_number'])?></td>
					<td align="center">USA</td>
					<td>
				<?php
				if($data['available_phone_numbers'][$i]['capabilities']['voice']=='1'){ ?>
									   Voice <img src="images/tick.gif">
						<?php    } else{ ?>
									   Voice <img src="images/cross.png">
						<?php   }
						if($data['available_phone_numbers'][$i]['capabilities']['SMS']=='1'){ ?>
									   SMS <img src="images/tick.gif">
						<?php  } else{ ?>
									   SMS <img src="images/cross.png">
						<?php  }
						if($data['available_phone_numbers'][$i]['capabilities']['MMS']=='1'){ ?>
									   MMS <img src="images/tick.gif">
						<?php   } else{ ?>
										MMS <img src="images/cross.png">
						<?php  } ?>
						</td>
						</tr>
						<?php   } ?>
			<tr><td colspan="7"><input type="button" value="Buy Number" class="btn btn-primary" onclick="buySignalWireNumber();"></td></tr>
			</table>
	  <?php  }
			break;
		case "update_footer_customization":{
			$sql = sprintf("update application_settings set footer_customization='%s' where user_id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['footer_customization'])),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! footer info is updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_cronjob_settings":{
			$sql = sprintf("update application_settings set cron_stop_time_from='%s', cron_stop_time_to='%s' where user_id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['cron_stop_time_from'])),
							mysqli_real_escape_string($link,DBin($_REQUEST['cron_stop_time_to'])),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! cron settings has been updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_bitly_api_keys":{
			$bitlyKey = DBin($_REQUEST['bitly_key']);
			$bitlyToken = DBin($_REQUEST['bitly_token']);
			$sql = sprintf("update application_settings set bitly_key='%s', bitly_token='%s' where user_id='%s'",
							mysqli_real_escape_string($link,DBin($bitlyKey)),
							mysqli_real_escape_string($link,DBin($bitlyToken)),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! bitly credential has been updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating';

			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "delete_gdpr_profile":{
			$subsID = DBin($_REQUEST['subsid']);
			$res = mysqli_query($link,sprintf("delete from subscribers where id='%s'",mysqli_real_escape_string($link,DBin($subsID))));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! profile has been deleted.';
			}else{

			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_gdpr_profile":{
			$subsID = DBin($_REQUEST['subs_id']);
			$sql = sprintf("update subscribers set first_name='%s', 
										last_name='%s',
										phone_number='%s',
										email='%s' 
										where id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['gdpr_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['gdpr_last_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['gdpr_phone'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['gdpr_email'])),
										mysqli_real_escape_string($link,DBin($subsID))
						  );
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! profile has been updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "load_apt_followUp":{
			$aptID  = DBin($_REQUEST['aptID']);
			$alerts = sprintf("select * from appointment_followup_msgs where apt_id='%s' order by id asc",
							mysqli_real_escape_string($link,DBin($aptID))
				);
			$altRes = mysqli_query($link,$alerts);
			if(mysqli_num_rows($altRes)){
				$index = 1;
				while($altRow = mysqli_fetch_assoc($altRes)){
					?>
					<p><label>Message Time: </label><?php echo DBout("&nbsp;".str_replace('+','',$altRow['message_time']))?> after appointment.</p>
					<p><label>Message: </label><?php echo DBout("&nbsp;".$altRow['apt_message'])?></p>
					<?php
					if(trim($altRow['media'])!=''){
						if(file_exists('uploads/'.$altRow['media'])){ ?>
							<p><label>Media: </label> <img src="uploads/<?php echo DBout($altRow['media'])?>" width="100" height="100" /></p>
				<?php        }
					}
				}
			}
		}
			break;

		case "load_apt_alerts":{
			$aptID  = DBin($_REQUEST['aptID']);
			$alerts = sprintf("select * from appointment_alerts where apt_id='%s' order by id asc",
						mysqli_real_escape_string($link,DBin($aptID))
				);
			$altRes = mysqli_query($link,$alerts);
			if(mysqli_num_rows($altRes)){
				$index = 1;
				while($altRow = mysqli_fetch_assoc($altRes)){
					?>
					<p><label>Message Time: </label><?php echo DBout("&nbsp;".str_replace('-','',$altRow['message_time']))?> before appointment.</p>
					<p><label>Message: </label><?php echo DBout("&nbsp;".$altRow['apt_message'])?></p>
					<?php
					if(trim($altRow['media'])!=''){
						if(file_exists('uploads/'.$altRow['media'])){ ?>
							<p><label>Media: </label> <img src="uploads/<?php echo DBout($altRow['media']) ?>" width="100" height="100" /></p>';
				 <?php       }
					}
				}
			}
		}
			break;

		case "duplicate_campaign":{
			$campID = DBin($_REQUEST['campID']);
			$title = DBin($_REQUEST['title']);
			$keyword =DBin($_REQUEST['keyword']);
			$sel = sprintf("select id from campaigns where lower(keyword)='%s' and user_id='%s'",
							mysqli_real_escape_string($link,DBin($keyword)),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sel);
			if(mysqli_num_rows($res)==0){
				$sel = sprintf("select * from campaigns where id='%s'",
								mysqli_real_escape_string($link,DBin($campID))
					);
				$exe = mysqli_query($link,$sel);
				if(mysqli_num_rows($exe)){
					$row = mysqli_fetch_assoc($exe);
					$sql = sprintf("insert into campaigns
										(
											title,
											keyword,
											type,
											welcome_sms,
											already_member_msg,
											code_message,
											notification_msg,
											winning_number,
											winner_msg,
											looser_msg,
											correct_sms,
											wrong_sms,
											complete_sms,
											contest_cycle_num,
											double_optin,
											media,
											get_email,
											reply_email,
											email_updated,
											user_id,
											post_message,
											start_date,
											end_date,
											expire_message,
											attach_mobile_device,
											direct_subscription,
											double_optin_check,
											get_subs_name_check,
											msg_to_get_subscriber_name,
											name_received_confirmation_msg,
											campaign_expiry_check,
											followup_msg_check,
											double_optin_confirm_message,
											share_with_subaccounts
										)
									values
										(
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s'
										)",
											mysqli_real_escape_string($link,DBin($title)),
											mysqli_real_escape_string($link,DBin($keyword)),
											mysqli_real_escape_string($link,DBin($row['type'])),
											mysqli_real_escape_string($link,DBin($row['welcome_sms'])),
											mysqli_real_escape_string($link,DBin($row['already_member_msg'])),
											mysqli_real_escape_string($link,DBin($row['code_message'])),
											mysqli_real_escape_string($link,DBin($row['notification_msg'])),
											mysqli_real_escape_string($link,DBin($row['winning_number'])),
											mysqli_real_escape_string($link,DBin($row['winner_msg'])),
											mysqli_real_escape_string($link,DBin($row['looser_msg'])),
											mysqli_real_escape_string($link,DBin($row['correct_sms'])),
											mysqli_real_escape_string($link,DBin($row['wrong_sms'])),
											mysqli_real_escape_string($link,DBin($row['complete_sms'])),
											mysqli_real_escape_string($link,DBin($row['contest_cycle_num'])),
											mysqli_real_escape_string($link,DBin($row['double_optin'])),
											mysqli_real_escape_string($link,DBin($row['media'])),
											mysqli_real_escape_string($link,DBin($row['get_email'])),
											mysqli_real_escape_string($link,DBin($row['reply_email'])),
											mysqli_real_escape_string($link,DBin($row['email_updated'])),
											mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
											mysqli_real_escape_string($link,DBin($row['post_message'])),
											mysqli_real_escape_string($link,DBin($row['start_date'])),
											mysqli_real_escape_string($link,DBin($row['end_date'])),
											mysqli_real_escape_string($link,DBin($row['expire_message'])),
											mysqli_real_escape_string($link,DBin($row['attach_mobile_device'])),
											mysqli_real_escape_string($link,DBin($row['direct_subscription'])),
											mysqli_real_escape_string($link,DBin($row['double_optin_check'])),
											mysqli_real_escape_string($link,DBin($row['get_subs_name_check'])),
											mysqli_real_escape_string($link,DBin($row['msg_to_get_subscriber_name'])),
											mysqli_real_escape_string($link,DBin($row['name_received_confirmation_msg'])),
											mysqli_real_escape_string($link,DBin($row['campaign_expiry_check'])),
											mysqli_real_escape_string($link,DBin($row['followup_msg_check'])),
											mysqli_real_escape_string($link,DBin($row['double_optin_confirm_message'])),
											mysqli_real_escape_string($link,DBin($row['share_with_subaccounts']))

						);
				   		$res =  mysqli_query($link,$sql);
							$sel = sprintf("select * from follow_up_msgs where group_id = '%s'",
								mysqli_real_escape_string($link,DBin($campID))
							);
						   $follow_sql = mysqli_query($link, $sel);
						   $camapign_id = mysqli_insert_id($link);
						   while ($follow_data = mysqli_fetch_assoc($follow_sql)) {
							   $sqlFollow = sprintf("insert into follow_up_msgs
											(group_id,delay_day,delay_time,message,media,user_id)values
											(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
											)",
								   mysqli_real_escape_string($link, $camapign_id),
								   mysqli_real_escape_string($link, DBin($follow_data['delay_day'])),
								   mysqli_real_escape_string($link, DBin($follow_data['delay_time'])),
								   mysqli_real_escape_string($link, DBin($follow_data['message'])),
								   mysqli_real_escape_string($link, DBin($follow_data['media'])),
								   mysqli_real_escape_string($link, DBin($follow_data['user_id']))
							   );
							   $resFollow = mysqli_query($link, $sqlFollow) or die(mysqli_error($link));


					   }
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Success! Campaign Duplicated Successfully';
					   echo DBout('{"error":"no","message":"Successfully created."}');
				}else{
					echo DBout('{"error":"yes","message":"Campaign is already deleted."}');
				}
			}else{
				echo DBout('{"error":"yes","message":"Keyword is already exists."}');
			}
		}
			break;
		case "update_purchase_code":{
			$appSettings = getAppSettings($_SESSION['user_id']);
			if(trim($appSettings['time_zone'])!=''){
				date_default_timezone_set($appSettings['time_zone']);
			}
			$today = date('Y-m-d H:i:s');
			$purchaseCode = DBin($_REQUEST['purchaseCode']);
			$status = DBin($_REQUEST['status']);
			$userID = DBin($_REQUEST['user_id']);
			$sql = sprintf("update application_settings set
								product_purchase_code='%s',
								product_purchase_code_status='%s',
								settings_date='%s'
							where
								user_id='%s'
								limit 1",
							mysqli_real_escape_string($link,DBin($purchaseCode)),
							mysqli_real_escape_string($link,DBin($status)),
							mysqli_real_escape_string($link,DBin($today)),
							mysqli_real_escape_string($link,DBin($userID))
				);
			$res = mysqli_query($link,$sql);
			if($res)
				echo DBout('1');
			else
				echo DBout(mysqli_error($link));
		}
			break;

		case "load_subs_custom_info":{
			$subsID = DBin($_REQUEST['subs_id']);
			$sql = sprintf("select custom_info from subscribers where id='%s'",
							mysqli_real_escape_string($link,DBin($subsID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$row = mysqli_fetch_assoc($res);
				$info = json_decode($row['custom_info'],true);
				for($i=0; $i<count($info); $i++){
					?>
					<div class="form-group">
						<label><?php echo DBout($info[$i]['field_label'])?></label><br />
						<?php
						if($info[$i]['field_type']=='checkbox'){
							$answers = explode(',',trim($info[$i]['field_value'],','));
							for($j=0; $j<count($answers); $j++){
								echo DBout($answers[$j]);
								?>
								<br>
								<?php
							}
						}else{
							echo DBout($info[$i]['field_value']);
						}
						?>
					</div>
					<?php
				}
			}else{
				echo DBout('Subscriber is already deleted or moved.');
			}
		}
			break;

		case "check_incoming_number":{
			$phoneNumber = DBin($_REQUEST['From']);
			$sql = sprintf("select id,user_id from subscribers where phone_number='%s'",
						mysqli_real_escape_string($link,DBin($phoneNumber))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$row = mysqli_fetch_assoc($res);

				$userID = $row['user_id'];
				$appSettings = getAppSettings($userID);
				$timeZone = $appSettings['time_zone'];
				if(trim($timeZone)!=''){
					date_default_timezone_set($timeZone);
				}
				$receivedDate = date("Y-m-d H:i:s");

				$sql = sprintf("insert into chat_history
								(
									phone_id,
									message,
									direction,
									user_id,
									message_sid,
									created_date
								)
							values
								(
									'%s',
									'%s',
									'in',
									'%s',
									'chat message from mobile',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($row['id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['Body'])),
									mysqli_real_escape_string($link,DBin($row['user_id'])),
									mysqli_real_escape_string($link,DBin($receivedDate))
					);
				mysqli_query($link,$sql);
				echo DBout('{"incoming_number":"true"}');
			}else{
				$text = strtolower($_REQUEST['text']);
				$sel = sprintf("select id, keyword from campaigns where lower(keyword)='%s'",
								mysqli_real_escape_string($link,DBin($text))
					);
				$exe = mysqli_query($link,$sel);
				if(mysqli_num_rows($exe)){
					$row = mysqli_fetch_assoc($exe);
					$url = getServerUrl().'/sms_controlling.php';
					$data = array(
						'To' => DBin($_REQUEST['device_name']),
						'From' => DBin($phoneNumber),
						'Body' => DBin($text),
						'is_mobile' => 'true'
					);
					post_curl_mqs($url,$data);
					echo DBout('{"incoming_number":"true"}');
				}else{
					echo DBout('{"incoming_number":"false"}');
				}
			}
		}
			break;

		case "update_mobile_device":{
			$mobileDevices = json_decode($_REQUEST['devices_json'],true);
			if(count($mobileDevices) > 0){
				for($i=0; $i<count($mobileDevices); $i++){
					$sql = sprintf("update mobile_devices set device_status='%s' where id='%s'",
						mysqli_real_escape_string($link,DBin($mobileDevices[$i]["status"])),
						mysqli_real_escape_string($link,DBin($mobileDevices[$i]["id"]))
					);
					mysqli_query($link,$sql);
				}
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! activated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! No device selected to activate';
			}
			header("location:".$_SERVER['HTTP_REFERER']);
		}
		break;

		case "whtsap":{
			$sql = sprintf("update application_settings set enable_whatsapp='%s' where user_type='1'",
					mysqli_real_escape_string($link,DBin($_REQUEST['whatsapp']))
				);
			$res = mysqli_query($link,$sql);
			echo DBout(($res) ? 1 : 0);
		}
		break;

		case "get_firebase_credentials":{
			$deviceName  = DBin($_REQUEST['device_name']);
			$deviceToken = DBin($_REQUEST['firebase_token']);
			$deviceUrl   = DBin($_REQUEST['app_url']);
			$userID      = DBin($_REQUEST['nm_user_id']);
			$deviceType  = DBin($_REQUEST['device_type']);
			$sel = sprintf("select id from mobile_devices where lower(device_name)='%s' and user_id='%s'",
							mysqli_real_escape_string($link,DBin($deviceName)),
							mysqli_real_escape_string($link,DBin($userID))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$ins = sprintf("insert into mobile_devices
									(
										device_name,
										device_token,
										app_url,
										user_id,
										device_type
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s'
									)",
									mysqli_real_escape_string($link,DBin($deviceName)),
									mysqli_real_escape_string($link,DBin($deviceToken)),
									mysqli_real_escape_string($link,DBin($deviceUrl)),
									mysqli_real_escape_string($link,DBin($userID)),
									mysqli_real_escape_string($link,DBin($deviceType))
					);
				$res = mysqli_query($link,$ins);
				if($res){
					setcookie("nm_user_id", "", time() -3600);
					echo DBout('{"device_response":"true"}');
				}else{
					echo DBout('{"device_response":"false"}');
				}
			}else{
				setcookie("nm_user_id", "", time() -3600);
				echo DBout('{"device_response":"false"}');
			}
		}
		break;

		case "check_device_name":{
			$deviceName = DBin($_REQUEST['device_name']);
			if(trim($deviceName)!=''){
				$sel = sprintf("select id from mobile_devices where device_name='%s' and device_name!=''",
										mysqli_real_escape_string($link,DBin($deviceName))
					);
				$exe = mysqli_query($link,$sel);
				if(mysqli_num_rows($exe)==0){
					echo DBout('{"device_response":"true"}');
				}else{
					echo DBout('{"device_response":"false"}');
				}
			}else{
				echo DBout('{"device_response":"empty"}');
			}
		}
		break;

		case "post_survey_twitter":{
			require_once('twitter/TwitterAPIExchange.php');
			$userInfo = getUserInfo($_SESSION['user_id']);
			$twitter = new TwitterAPIExchange(array(
				'oauth_access_token' => $userInfo['tw_access_token'],
				'oauth_access_token_secret' => $userInfo['tw_access_token_secret'],
				'consumer_key' => $userInfo['tw_consumer_key'],
				'consumer_secret' => $userInfo['tw_consumer_secret']
			));

			$url = 'https://api.twitter.com/1.1/statuses/update.json';
			$requestMethod = 'POST';
			$postData = array('status' => $_REQUEST['surveyUrl']);
			$json_res = $twitter->buildOauth($url, $requestMethod)
				->setPostfields($postData)
				->performRequest();
			$response = json_decode($json_res,true);
			print_r($response);
		}
			break;

		case "post_survey_facebook":{
			$sel = sprintf("select access_token from users where id='%s' and access_token!=''",
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)){
				$row = mysqli_fetch_assoc($exe);
				$surveyUrl = DBin($_REQUEST['surveyUrl']);
				$attachment =  array(

					'access_token' => $row['access_token'],
					'message' => '',
					'name' => '',
					'link' => $surveyUrl,
					'description' => '',
					'picture'=>''
				);
				$url="https://graph.facebook.com/v2.8/me/feed?access_token=".$row['access_token'];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
				curl_setopt($ch, CURLOPT_TIMEOUT, 100);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_POST, true );
				curl_setopt($ch, CURLOPT_POSTFIELDS, $attachment);
				$response = curl_exec($ch);
				$response = json_decode($response,true);
				print_r($appSettings);
			}else{
				echo DBout('Facebook access token is not valid.');
			}
		}
			break;

		case "get_survey_response":{
			$rating = DBin($_REQUEST['rating']);
			$attemptID = DBin($_REQUEST['nmAttemptID']);
			$questionType = DBin($_REQUEST['questionType']);
			$questionID = DBin($_REQUEST['questionID']);
			$surveyID = DBin($_REQUEST['surveyID']);

			$sel = sprintf("select id from survey_responses where id='%s'",
								mysqli_real_escape_string($link,DBin($attemptID))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)){
				$ins = sprintf("insert into survey_answers
									(
										attempt_id,
										question_type,
										question_id,
										survey_id,
										answer
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s'
									)",
											mysqli_real_escape_string($link,DBin($attemptID)),
											mysqli_real_escape_string($link,DBin($questionType)),
											mysqli_real_escape_string($link,DBin($questionID)),
											mysqli_real_escape_string($link,DBin($surveyID)),
											mysqli_real_escape_string($link,DBin($rating))
					);
				mysqli_query($link,$ins);
			}else{

			}

			$sel = sprintf("select * from survey_questions where survey_id='%s' and id > '%s' order by id asc limit 1",
									mysqli_real_escape_string($link,DBin($surveyID)),
									mysqli_real_escape_string($link,DBin($questionID))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)){
				?>
				<script>
					window.onload = function(){
						if(window.jQuery){
						}else{
							var headTag = document.getElementById('mainQuestionContainer');
							var jqTag = document.createElement('script');
							jqTag.type = 'text/javascript';
							jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js';
							headTag.appendChild(jqTag);
						}
					}
				</script>
				<?php
				$questionData = mysqli_fetch_assoc($exe);
				$questionID = $questionData['id'];
				$questionType = $questionData['question_type'];
				if($questionType=='comment_box'){ ?>
					<p>
				   <?php echo DBout($questionData['question']); ?>

					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/uploads/<?php echo DBout($questionData['media'])?>" />
					</p>
					<?php
				}else if($questionType=='star_rating_question'){ ?>
					<p>
				<?php  echo DBout($questionData['question']); ?>
					</p>
					<p>
					<img src="'.getServerUrl().'/uploads/'.$questionData['media'].'" />
					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/images/star-silver.png" alt="1" onclick="getUserResponse(this)" onmouseover="getMouseOver(this)" onmouseout="getMouseOut(this)" title="1" class="surveyEmoticons margin-right-10 pointer" />';
					<img src="<?php echo DBout(getServerUrl())?>/images/star-silver.png" alt="2" onclick="getUserResponse(this)" onmouseover="getMouseOver(this)" onmouseout="getMouseOut(this)" title="2" class="surveyEmoticons margin-right-10 pointer" />';
					<img src="<?php echo DBout(getServerUrl())?>/images/star-silver.png" alt="3" onclick="getUserResponse(this)" onmouseover="getMouseOver(this)" onmouseout="getMouseOut(this)" title="3" class="surveyEmoticons margin-right-10 pointer" />';
					<img src="<?php echo DBout(getServerUrl())?>/images/star-silver.png" alt="4" onclick="getUserResponse(this)" onmouseover="getMouseOver(this)" onmouseout="getMouseOut(this)" title="4" class="surveyEmoticons margin-right-10 pointer" />';
					<img src="<?php echo DBout(getServerUrl())?>/images/star-silver.png" alt="5" onclick="getUserResponse(this)" onmouseover="getMouseOver(this)" onmouseout="getMouseOut(this)" title="5" class="surveyEmoticons margin-right-10 pointer" />';
					</p>
					<script>
						function getMouseOut(obj){
							$(obj).attr('src','<?php echo DBout(getServerUrl())?>/images/star-silver.png')
						}
						function getMouseOver(obj){
							$(obj).attr('src','<?php echo DBout(getServerUrl())?>/images/star-gold.png')
						}
					</script>
					<?php
				}else if($questionType=='vote_question'){ ?>
					<p>
				<?php  echo DBout($questionData['question'])?>
					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/uploads/<?php echo DBout($questionData['media'])?>" />
					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/images/like-green.png" class="margin-right-10 pointer" alt="like-green.png" onclick="getUserResponse(this)" />
					<img src="<?php echo DBout(getServerUrl())?>/images/dislike-red.png" class="margin-right-10 pointer" alt="dislike-red.png" onclick="getUserResponse(this)" />
					</p>
					<?php
				}else if($questionType=='emoticon_question'){ ?>
					<p>
				<?php echo DBout($questionData['question']); ?>
					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/uploads/'.$questionData['media'].'" />';
					</p>
					<p>
					<img src="<?php echo DBout(getServerUrl())?>/images/1-ico.png" alt="1-ico.png" onclick="getUserResponse(this)" class="surveyEmoticons margin-right-10 pointer" />
					<img src="<?php echo DBout(getServerUrl())?>/images/2-ico.png" alt="2-ico.png" onclick="getUserResponse(this)" class="surveyEmoticons margin-right-10 pointer" />
					<img src="<?php echo DBout(getServerUrl())?>/images/3-ico.png" alt="3-ico.png" onclick="getUserResponse(this)" class="surveyEmoticons margin-right-10 pointer" />
					<img src="<?php echo DBout(getServerUrl())?>/images/4-ico.png" alt="4-ico.png" onclick="getUserResponse(this)" class="surveyEmoticons margin-right-10 pointer" />
					<img src="<?php echo DBout(getServerUrl())?>/images/5-ico.png" alt="5-ico.png" onclick="getUserResponse(this)" class="surveyEmoticons margin-right-10 pointer" />
					</p> <?php
				}else if($questionType=='multiple_choice_question'){ ?>
					<p>
				 <?php   echo DBout($questionData['question']); ?>
					</p>
					<ul class="list_style_none">
						<?php
					$questionOptions = explode(',',$questionData['answers']);
					for($i=0;$i<count($questionOptions);$i++){
						?>
						<li><label><input type="radio" name="multiple_choice" value="<?php echo DBout($questionOptions[$i])?>" onclick="getUserResponse(this)"><?php echo DBout($questionOptions[$i])?></label></li>
				 <?php
					} ?>
					</ul>
			<?php    }
			}else{
				echo DBout('Thanks for the survey.');
			}
			?>
			<input type="hidden" id="nmAttemptID" value="<?php echo DBout($attemptID)?>" />
			<input type="hidden" id="nmSurveyID" value="<?php echo DBout($surveyID)?>" />
			<input type="hidden" id="nmQuestionType" value="<?php echo DBout($questionType)?>" />
			<input type="hidden" id="nmQuestionID" value="<?php echo DBout($questionID)?>" />
	   <?php
		}
			break;

		case "show_survey_live":{
			include_once("run_survey.php");
		}
			break;

		case "delete_survey":{

			$sql = sprintf("delete from surveys where id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['surveyID']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$sql = sprintf("delete from survey_questions where survey_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['surveyID']))
					);
				$exe = mysqli_query($link,$sql);
				$sql = sprintf("delete from survey_responses where survey_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['surveyID']))
					);
				$exe = mysqli_query($link,$sql);
				$sql = sprintf("delete from survey_answers where survey_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['surveyID']))
					);
				$exe = mysqli_query($link,$sql);
				if($exe){
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! survey deleted successfully';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! failed to delete survey';
			}
		}
			break;

		case "save_survey":{
			$tab = DBin($_REQUEST['tab']);
			$surveyID = DBin($_REQUEST['surveyID']);
			$type = DBin($_REQUEST['question_type']);
			if($tab=='name_survey'){
				$surveyName = DBin($_REQUEST['surveyName']);
				$surveyDesc = DBin($_REQUEST['surveyDesc']);
				if(trim($surveyID)==''){
					$sql = sprintf("insert into surveys
										(
											survey_name,
											survey_desc,
											user_id
										)
									values
										(
											'%s',
											'%s',
											'%s'
										)",
											mysqli_real_escape_string($link,DBin($surveyName)),
											mysqli_real_escape_string($link,DBin($surveyDesc)),
											mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
					$res = mysqli_query($link,$sql);
					if($res){
						$surveyID = mysqli_insert_id($link);
						$surveyUrl = getServerUrl().'/server.php?cmd=show_survey_live&survey_id='.$surveyID.'&uid='.$_SESSION['user_id'];
						$surveyUrl = bitlyLinkShortner($surveyUrl,$_SESSION['user_id']);
						$up = sprintf("update surveys set
											survey_link='%s'
										where
											id='%s'",
									mysqli_real_escape_string($link,DBin($surveyUrl)),
									mysqli_real_escape_string($link,DBin($surveyID))
						);
						mysqli_query($link,$up);
						echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved","survey_url":"'.$surveyUrl.'"}');
					}else{
						echo DBout('{"id":"","error":"yes","message":"'.mysqli_error($link).'"}');
					}
				}else{
					$sql = sprintf("update surveys set
										survey_name='%s',
										survey_desc='%s'
									where
										id='%s'",
									mysqli_real_escape_string($link,DBin($surveyName)),
									mysqli_real_escape_string($link,DBin($surveyDesc)),
									mysqli_real_escape_string($link,DBin($surveyID))
						);
					mysqli_query($link,$sql);
					echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Updated"}');
				}
			}
			else if($tab=='add_question'){
				if($type == 'comment_box'){
					$question = DBin($_REQUEST['survey_question']);
					$ext = getExtension($_FILES['question_media']['name']);
					$fileName = uniqid().'.'.$ext;
					$tmpName  = $_FILES['question_media']['tmp_name'];
					if(in_array($ext,validImageExtensions())){
						$res = move_uploaded_file($tmpName,'uploads/'.$fileName);
						if($res){
							$sql = sprintf("insert into survey_questions
												(
													survey_id,
													question_type,
													question,
													media
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s'
												)",

													mysqli_real_escape_string($link,DBin($surveyID)),
													mysqli_real_escape_string($link,DBin($type)),
													mysqli_real_escape_string($link,DBin($question)),
													mysqli_real_escape_string($link,DBin($fileName))
								);
							$r = mysqli_query($link,$sql);
							if($r){
								echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved"}');
							}else{
								echo DBout('{"id":"'.$surveyID.'","error":"yes","message":"'.mysqli_error($link).'"}');
							}
						}
					}
				}
				else if($_REQUEST['question_type']=='emoticon_question'){
					$question = DBin($_REQUEST['survey_question']);
					$ext = getExtension($_FILES['question_media']['name']);
					$fileName = uniqid().'.'.$ext;
					$tmpName  = $_FILES['question_media']['tmp_name'];
					if(in_array($ext,validImageExtensions())){
						$res = move_uploaded_file($tmpName,'uploads/'.$fileName);
						if($res){
							$sql = sprintf("insert into survey_questions
												(
													survey_id,
													question_type,
													question,
													media
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s'
												)",
													mysqli_real_escape_string($link,DBin($surveyID)),
													mysqli_real_escape_string($link,DBin($type)),
													mysqli_real_escape_string($link,DBin($question)),
													mysqli_real_escape_string($link,DBin($fileName))
								);
							$r = mysqli_query($link,$sql);
							if($r){
								echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved"}');
							}else{
								echo DBout('{"id":"'.$surveyID.'","error":"yes","message":"'.mysqli_error($link).'"}');
							}
						}
					}
				}
				else if($_REQUEST['question_type']=='star_rating_question'){
					$question = DBin($_REQUEST['survey_question']);
					$ext = getExtension($_FILES['question_media']['name']);
					$fileName = uniqid().'.'.$ext;
					$tmpName  = $_FILES['question_media']['tmp_name'];
					if(in_array($ext,validImageExtensions())){
						$res = move_uploaded_file($tmpName,'uploads/'.$fileName);
						if($res){
							$sql = sprintf("insert into survey_questions
												(
													survey_id,
													question_type,
													question,
													media
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s'
												)",
													mysqli_real_escape_string($link,DBin($surveyID)),
													mysqli_real_escape_string($link,DBin($type)),
													mysqli_real_escape_string($link,DBin($question)),
													mysqli_real_escape_string($link,DBin($fileName))
								);
							$r = mysqli_query($link,$sql);
							if($r){
								echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved"}');
							}else{
								echo DBout('{"id":"'.$surveyID.'","error":"yes","message":"'.mysqli_error($link).'"}');
							}
						}
					}
				}
				else if($_REQUEST['question_type']=='vote_question'){
					$question = DBin($_REQUEST['survey_question']);
					$ext = getExtension($_FILES['question_media']['name']);
					$fileName = uniqid().'.'.$ext;
					$tmpName  = $_FILES['question_media']['tmp_name'];
					if(in_array($ext,validImageExtensions())){
						$res = move_uploaded_file($tmpName,'uploads/'.$fileName);
						if($res){
							$sql = sprintf("insert into survey_questions
												(
													survey_id,
													question_type,
													question,
													media
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s'
												)",
													mysqli_real_escape_string($link,DBin($surveyID)),
													mysqli_real_escape_string($link,DBin($type)),
													mysqli_real_escape_string($link,DBin($question)),
													mysqli_real_escape_string($link,DBin($fileName))
								);
							$r = mysqli_query($link,$sql);
							if($r){
								echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved"}');
							}else{
								echo DBout('{"id":"'.$surveyID.'","error":"yes","message":"'.mysqli_error($link).'"}');
							}
						}
					}
				}
				else if($type=='multiple_choice_question'){
					$question = DBin($_REQUEST['survey_question']);
					$sql = sprintf("insert into survey_questions
										(
											survey_id,
											question_type,
											question,
											answers,
											media
										)
									values
										(
											'%s',
											'%s',
											'%s',
											'%s',
											'%s'
										)",
											mysqli_real_escape_string($link,DBin($surveyID)),
											mysqli_real_escape_string($link,DBin($type)),
											mysqli_real_escape_string($link,DBin($question)),
											mysqli_real_escape_string($link,DBin(implode(',',$_REQUEST['multiple_choices']))),
											mysqli_real_escape_string($link,DBin($fileName))
						);
					$r = mysqli_query($link,$sql);
					if($r){
						echo DBout('{"id":"'.$surveyID.'","error":"no","message":"Saved"}');
					}else{
						echo DBout('{"id":"'.$surveyID.'","error":"yes","message":"'.mysqli_error($link).'"}');
					}
				}

			}else if($tab=='save_share'){

			}else if($tab==''){

			}
		}
			break;

		case "get_group_subscribers":{
			$groupID = DBin($_REQUEST['groupID']);
			$phoneID = DBin($_REQUEST['phoneID']);
			$sql = sprintf("select s.id, s.phone_number,s.first_name,s.last_name,email from subscribers s, subscribers_group_assignment sga where
								sga.group_id='%s' and sga.subscriber_id=s.id and s.status='1'",
								mysqli_real_escape_string($link,DBin($groupID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){ ?>
				<option value="all">All Numbers</option>
				<?php
				while($row=mysqli_fetch_assoc($res)){
					$name = "";
					if($row['first_name']!=""){
						$name = " - ".$row['first_name']." ".$row['last_name'];
					}
					if($phoneID==$row['id'])
						$sel = 'selected="selected"';
					else
						$sel = '';
					?>
					<option <?php echo DBout($sel)?> value="<?php echo DBout($row['id'])?>"><?php echo DBout($row['phone_number'].$name) ?></option>
			  <?php  }
			} else{
			?>
				<option value="all">- No subscriber found -</option>
		  <?php  }
		}
			break;

		case "delete_apt":{
			$aptID = DBin($_REQUEST['aptID']);
			$sql = sprintf("delete from appointments where id='%s'",
			mysqli_real_escape_string($link,DBin($aptID))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				mysqli_query($link,sprintf("delete from schedulers where appt_id='%s'",mysqli_real_escape_string($link,DBin($aptID))));
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! appointment deleted successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to delete appointment';
			}
		}
			break;

		case "delete_appt_temp":{
			$aptID = DBin($_REQUEST['aptID']);
			$sql = sprintf("delete from appointment_templates where id='%s'",
			mysqli_real_escape_string($link,DBin($aptID))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				mysqli_query($link,sprintf("delete from template_reminders where template_id='%s'",mysqli_real_escape_string($link,DBin($aptID))));
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! appointment template deleted successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to delete appointment';
			}
		}
			break;

		case "delete_checked_numbers":{
			$ids = DBin($_REQUEST['checked_numbers']);
			mysqli_query($link,sprintf("delete from subscribers where id in (%s)",mysqli_real_escape_string($link,DBin($ids))));
			mysqli_query($link,sprintf("delete from subscribers_group_assignment where subscriber_id in (%s)",mysqli_real_escape_string($link,DBin($ids))));
			$_SESSION['message_status']=1;
			$_SESSION['message'] = 'Success! subscribers deleted successfully';
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_appointment":{
			$phone_number = '';
			if($_REQUEST['phone_number_id'] == 'all')
			{
				$phone_number = 'all';
			}
			else
			{
				$phone_number_id = DBin($_REQUEST['phone_number_id']);

				$sql1 = sprintf("select * from subscribers where id = %s",
									mysqli_real_escape_string($link,DBin($phone_number_id))
								);
				$query = mysqli_query($link,$sql1);
				$result_array  = mysqli_fetch_assoc($query);
				$phone_number = $result_array['phone_number'];
			}
			$aptDate = date('Y-m-d',strtotime($_REQUEST['apt_date']));
			$aptTime = DBin($_REQUEST['apt_time'].':00');
			$dateTime= $aptDate.' '.$aptTime;
			$sql = sprintf("update appointments set
								title='%s',
								apt_time='%s',
								apt_message='%s',
								group_id='%s',
								phone_number='%s'
							where
								id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['apt_title'])),
								mysqli_real_escape_string($link,DBin($dateTime)),
								mysqli_real_escape_string($link,DBin($_REQUEST['apt_message'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($phone_number)),
								mysqli_real_escape_string($link,DBin($_REQUEST['apt_id']))
				);
			$res = mysqli_query($link,$sql);
			$aptID = DBin($_REQUEST['apt_id']);
			if($res){
				$delAlt = sprintf("delete from appointment_alerts where apt_id='%s'",
									mysqli_real_escape_string($link,DBin($aptID))
					);
				mysqli_query($link,$delAlt);
				for($i=0; $i<count($_REQUEST['before_time']); $i++){
					if((trim($_REQUEST['before_time'][$i])!='')&&(trim($_REQUEST['before_message'][$i])!='')){
						if($_FILES['before_media']['name'][$i]!=''){
							$ext = getExtension($_FILES['before_media']['name'][$i]);
							if(in_array($ext,validImageExtensions())){
								$fileName = uniqid().'.'.$ext;
								$tmpName = $_FILES['before_media']['tmp_name'][$i];
								move_uploaded_file($tmpName,'uploads/'.$fileName);
								unlink('uploads/'.DBin($_REQUEST['delay_hidden_media'][$i]));
							}
						}else{
							$fileName = DBin($_REQUEST['before_hidden_media'][$i]);
						}
						$alerts = sprintf("insert into appointment_alerts
												(
													apt_id,
													message_date,
													message_time,
													apt_message,
													media,
													user_id
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
													mysqli_real_escape_string($link,DBin($aptID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_date'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						mysqli_query($link,$alerts);
					}
				}
				$delFollow = sprintf("delete from appointment_followup_msgs where apt_id='%s'",
										mysqli_real_escape_string($link,DBin($aptID))
					);
				mysqli_query($link,$delFollow);
				for($i=0; $i<count($_REQUEST['delay_time']); $i++){
					if((trim($_REQUEST['delay_time'][$i])!='')&&(trim($_REQUEST['delay_message'][$i])!='')){
						if($_FILES['delay_media']['name'][$i]!=''){
							$ext = getExtension($_FILES['delay_media']['name'][$i]);
							if(in_array($ext,validImageExtensions())){
								$fileName = uniqid().'.'.$ext;
								$tmpName = $_FILES['delay_media']['tmp_name'][$i];
								move_uploaded_file($tmpName,'uploads/'.$fileName);
								unlink('uploads/'.DBin($_REQUEST['delay_hidden_media'][$i]));
							}
						}else{
							$fileName = DBin($_REQUEST['delay_hidden_media'][$i]);
						}
						$followup = sprintf("insert into appointment_followup_msgs
												(
													apt_id,
													message_date,
													message_time,
													apt_message,
													media,
													user_id
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
							mysqli_real_escape_string($link,DBin($aptID)),
							mysqli_real_escape_string($link,DBin($_REQUEST['before_date'][$i])),
							mysqli_real_escape_string($link,DBin($_REQUEST['before_time'][$i])),
							mysqli_real_escape_string($link,DBin($_REQUEST['before_message'][$i])),
							mysqli_real_escape_string($link,DBin($fileName)),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						mysqli_query($link,$followup);
					}
				}
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! appointment updated successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to update appointment';
			}
			header("location: view_apts.php");
		}
			break;

		case "save_appt_template":{
			if(isset($_REQUEST['id'])){
				$sql = sprintf("update appointment_templates set 
											title = '%s', 
											immediate_sms = '%s', 
											group_id = '%s',
											 user_id = '%s' 
											 where id = '%s'",
													mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['immediate_sms'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['id']))
							  );
				$res = mysqli_query($link,$sql);
				$template_id = DBin($_REQUEST['id']);
			}else{
				$sql = sprintf("insert into appointment_templates 
								( 
												title,
												immediate_sms,
												group_id,
												user_id 
								   )
							values
								(
												'%s',
												'%s',
												'%s',
												'%s'
								)",

												mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['immediate_sms'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
												mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
					);
				$res = mysqli_query($link,$sql);
				$template_id = mysqli_insert_id($link);
			}

			if($res){
				if(isset($_REQUEST['id'])){
					$del = sprintf("delete from template_reminders where template_id = '%s'",
											mysqli_real_escape_string($link,DBin($template_id))
						);
					mysqli_query($link,$del);
				}

				for($i=0; $i<count($_REQUEST['reminder_time']); $i++){
					if((trim($_REQUEST['reminder_time'][$i])!='')&&(trim($_REQUEST['sms_text'][$i])!='')){
						if($_FILES['reminder_media']['name'][$i]!=''){
							$ext = getExtension($_FILES['reminder_media']['name'][$i]);
							if(in_array($ext,validImageExtensions())){
								$fileName = uniqid().'.'.$ext;
								$tmpName = $_FILES['reminder_media']['tmp_name'][$i];
								move_uploaded_file($tmpName,'uploads/'.$fileName);
							}
						}else{
							$fileName = '';
						}
						$alerts = sprintf("insert into template_reminders
												(
																template_id, 
																reminder_days,
																reminder_time,
																reminder_type,
																sms_text,
																media,
																user_id
													 )
											values
												(
																'%s',
																'%s', 
																'%s',
																'%s',
																'%s',
																'%s',
																'%s'
													 )",
																mysqli_real_escape_string($link,DBin($template_id)),
																mysqli_real_escape_string($link,DBin($_REQUEST['reminder_days'][$i])),
																mysqli_real_escape_string($link,DBin($_REQUEST['reminder_time'][$i])),
																mysqli_real_escape_string($link,DBin($_REQUEST['reminder_type'][$i])),
																mysqli_real_escape_string($link,DBin($_REQUEST['sms_text'][$i])),
																mysqli_real_escape_string($link,DBin($fileName)),
																mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						mysqli_query($link,$alerts) or die(mysqli_error($link));
					}
				}
			}
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Success! Appointment template is saved successfully';
			header("location: appt_templates.php");
		}
			break;

		case "create_new_appointment":{

			$userPkgStatus = checkUserPackageStatus($_SESSION['user_id']);
			if($userPkgStatus['go']==false){
				$remainingCredits = 0;
				die($userPkgStatus['message']);
			}else{
				$remainingCredits = $userPkgStatus['remaining_credits'];
			}

			$appointment_date = DBin($_REQUEST['apt_date'])." ".DBin($_REQUEST['apt_time']);
			$appointment_date = date("Y-m-d H:i",strtotime($appointment_date));

			$sql_temp = sprintf("select * from appointment_templates where id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['template_id']))
				);
			$exe_temp = mysqli_query($link,$sql_temp) or die(mysqli_error($link));
			$row_temp = mysqli_fetch_assoc($exe_temp);

			$groupID = $row_temp['group_id'];

			$row_camp = getGroupData($groupID);
			$group_number = $row_camp['phone_number'];

			$sql_sub = sprintf("select id,first_name from subscribers where phone_number='%s' and user_id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$exe_sub = mysqli_query($link,$sql_sub);
			if(mysqli_num_rows($exe_sub)==0){
				$subID = addSubscriber(DBin($_REQUEST['name']),DBin($_REQUEST['phone_number']),DBin($_REQUEST['email']),"appointment","","",$_SESSION['user_id'],'1',"");
				assignGroup($subID,$groupID,$_SESSION['user_id'],1);
				$clientName = DBin($_REQUEST['name']);
			}else{
				$row_sub = mysqli_fetch_assoc($exe_sub);
				$subID = $row_sub['id'];
				if($_REQUEST['name']!=""){
					$sql1 = sprintf("update subscribers set first_name = '%s' where id = '%s'",

											mysqli_real_escape_string($link,DBin($_REQUEST['name'])),
											mysqli_real_escape_string($link,DBin($subID))
						);
					mysqli_query($link,$sql1);
					$clientName = DBin($_REQUEST['name']);
				}else{
					$clientName = DBin($row_sub['first_name']);
				}
				if($_REQUEST['email']!=""){
					$sql2 = sprintf("update subscribers set email = '%s' where id = '%s'",
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,DBin($subID))
						);
					mysqli_query($link,$sql2);
				}
				assignGroup($subID,$groupID,$_SESSION['user_id'],1);
			}
			if(isset($_REQUEST['id']) && $_REQUEST['id']!=""){

				$sql = sprintf("update appointments set 
										name = '%s', 
										phone_number = '%s', 
										email = '%s', 
										appointment_date = '%s', 
										template_id = '%s' 
										where id = '%s'",

										mysqli_real_escape_string($link,DBin($_REQUEST['name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
										mysqli_real_escape_string($link,DBin($appointment_date)),
										mysqli_real_escape_string($link,DBin($_REQUEST['template_id'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['id']))

						);
				$exe = mysqli_query($link,$sql);

				$sql3 = sprintf("delete from schedulers where appt_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['id']))
							 );
				mysqli_query($link,$sql3);

			}else{

				$sql = sprintf("insert into appointments
									( 
													name,
													phone_number,
													email,
													appointment_date,
													template_id,
													user_id 
									)
								values
									( 
													'%s',
													'%s',
													'%s',
													'%s',
													'%s', 
													'%s'
									)",
													mysqli_real_escape_string($link,DBin($_REQUEST['name'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
													mysqli_real_escape_string($link,DBin($appointment_date)),
													mysqli_real_escape_string($link,DBin($_REQUEST['template_id'])),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
				$exe = mysqli_query($link,$sql);
				$appointment_id = mysqli_insert_id($link) or die(mysqli_error($link));


				$sms_text = $row_temp['immediate_sms'];
				$sms_text = str_replace("%name%",$clientName,$sms_text);
				$sms_text = str_replace("%apt_date%",DBin($_REQUEST['apt_date']),$sms_text);
				$res_msg = sendMessage($group_number,DBin($_REQUEST['phone_number']),$sms_text,array(),$_SESSION['user_id'],$groupID,false);
			}
			$sel_reminders = sprintf("select * from template_reminders where template_id='%s' order by id asc",
									mysqli_real_escape_string($link,DBin($_REQUEST['template_id']))
				);
			$res_reminders = mysqli_query($link,$sel_reminders) or die(mysqli_error($link));
			if(mysqli_num_rows($res_reminders)>0){
				$i=1;
				while($row_reminders = mysqli_fetch_assoc($res_reminders)){
					$sms_text = $row_reminders['sms_text'];
					$sms_text = str_replace("%name%",$clientName,$sms_text);
					$sms_text = str_replace("%apt_date%",DBin($_REQUEST['apt_date']),$sms_text);
					if($row_reminders['reminder_type']==1){ $reminder_type = "-"; }else{ $reminder_type = "+"; }
					$schedule_date = date("Y-m-d",strtotime(DBin($_REQUEST['apt_date'])." $reminder_type $row_reminders[reminder_days] days"))." ".$row_reminders['reminder_time'];

					$sql_sch = sprintf("insert into schedulers
								( 
												title,
												scheduled_time,
												group_id,
												phone_number,
												message,
												media, 
												user_id, 
												scheduler_type, 
												appt_id)
							values
								(
												%s, 
												'%s',
												'%s', 
												'%s', 
												'%s', 
												'%s',
												'%s',
												'3',
												'%s'
									)",
												mysqli_real_escape_string($link,DBin('R$i (Appt $appointment_id')),
												mysqli_real_escape_string($link,DBin($schedule_date)),
												mysqli_real_escape_string($link,DBin($groupID)),
												mysqli_real_escape_string($link,DBin($subID)),
												mysqli_real_escape_string($link,DBin($sms_text)),
												mysqli_real_escape_string($link,DBin($fileName)),
												mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
												mysqli_real_escape_string($link,DBin($appointment_id))
						);
					mysqli_query($link,$sql_sch) or die(mysqli_error($link));
					$i++;
				}
			}

			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Success! Appointment saved successfully';
			header("location: view_apts.php");


		}
			break;


		case "save_appointment":{
			$aptDate = date('Y-m-d',strtotime($_REQUEST['apt_date']));
			$aptTime = DBin($_REQUEST['apt_time']).':00';
			$dateTime= $aptDate.' '.$aptTime;
			$sql = sprintf("insert into appointments
								(
									title,
									apt_time,
									apt_message,
									group_id,
									phone_number,
									user_id
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($_REQUEST['apt_title'])),
									mysqli_real_escape_string($link,DBin($dateTime)),
									mysqli_real_escape_string($link,DBin($_REQUEST['apt_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['phone_number_id'])),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			$aptID = mysqli_insert_id($link);
			if($res){
				for($i=0; $i<count($_REQUEST['before_time']); $i++){
					if((trim($_REQUEST['before_time'][$i])!='')&&(trim($_REQUEST['before_message'][$i])!='')){
						if($_FILES['before_media']['name'][$i]!=''){
							$ext = getExtension($_FILES['before_media']['name'][$i]);
							if(in_array($ext,validImageExtensions())){
								$fileName = uniqid().'.'.$ext;
								$tmpName = $_FILES['before_media']['tmp_name'][$i];
								move_uploaded_file($tmpName,'uploads/'.$fileName);
							}
						}else{
							$fileName = '';
						}
						$alerts = sprintf("insert into appointment_alerts
												(
													apt_id,
													message_date,
													message_time,
													apt_message,
													media,
													user_id
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",

													mysqli_real_escape_string($link,DBin($aptID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_date'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['before_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						mysqli_query($link,$alerts) or die(mysqli_error($link));
					}
				}
				for($i=0; $i<count($_REQUEST['delay_time']); $i++){
					if((trim($_REQUEST['delay_time'][$i])!='')&&(trim($_REQUEST['delay_message'][$i])!='')){
						if($_FILES['delay_media']['name'][$i]!=''){
							$ext = getExtension($_FILES['delay_media']['name'][$i]);
							if(in_array($ext,validImageExtensions())){
								$fileName = uniqid().'.'.$ext;
								$tmpName = $_FILES['delay_media']['tmp_name'][$i];
								move_uploaded_file($tmpName,'uploads/'.$fileName);
							}
						}else{
							$fileName = '';
						}
						$followup = sprintf("insert into appointment_followup_msgs
												(
													apt_id,
													message_date,
													message_time,
													apt_message,
													media,
													user_id
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
													mysqli_real_escape_string($link,DBin($aptID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_date'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						mysqli_query($link,$followup) or die(mysqli_error($link));
					}
				}

				$aptMessage = DBin($_REQUEST['apt_message']);
				if($_REQUEST['phone_number_id']=='all'){
					$groupID = DBin($_REQUEST['group_id']);
					$sql = sprintf("select 
										s.id, 
										s.phone_number,
										c.phone_number as groupNumber
									from 
										subscribers s, 
										subscribers_group_assignment sga,
										campaigns c
									where
										sga.group_id='%s' and
										sga.subscriber_id=s.id and
										s.status='1' and
										c.id='%s'",
										mysqli_real_escape_string($link,DBin($groupID)),
										mysqli_real_escape_string($link,DBin($groupID))
						);
					$res = mysqli_query($link,$sql);
					if(mysqli_num_rows($res)){
						$userPkgStatus = checkUserPackageStatus($_SESSION['user_id']);
						if($userPkgStatus['go']==false){
							$remainingCredits = 0;
							die($userPkgStatus['message']);
						}else{
							$remainingCredits = $userPkgStatus['remaining_credits'];
						}

						$apt_date = date("Y-m-d h:i a",strtotime($dateTime));
						$aptMessage = str_replace('%apt_date%',$apt_date,$aptMessage);
						while($row = mysqli_fetch_assoc($res)){
							$subsID = $row['id'];
							$toNumber = $row['phone_number'];
							$fromNumber = $row['groupNumber'];
							sendMessage($fromNumber,$toNumber,$aptMessage,array(),$_SESSION['user_id'],$groupID,false);
							$selAlts = sprintf("select * from appointment_alerts where apt_id='%s'",
											mysqli_real_escape_string($link,DBin($aptID))
								);
							$resAlts = mysqli_query($link,$selAlts);
							if(mysqli_num_rows($resAlts)){
								while($rowAlts = mysqli_fetch_assoc($resAlts)){
									$altTime = $rowAlts['message_date']." ".$rowAlts['message_time'].":00";
									$alertMessage = DBout($rowAlts['apt_message']);
									if(trim($rowAlts['media'])!=''){
										$alertMedia = getServerUrl().'/uploads/'.$rowAlts['media'];
									}else{
										$alertMedia = '';
									}
									$userID = $rowAlts['user_id'];

									$shcAlts = sprintf("insert into queued_msgs
															(
																to_number,
																from_number,
																message,
																media,
																type,
																message_time,
																user_id,
																group_id
															)
														values
															(
																'%s',
																'%s',
																'%s',
																'%s',
																'2',
																'%s',
																'%s',
																'%s'
															)",
																mysqli_real_escape_string($link,DBin($toNumber)),
																mysqli_real_escape_string($link,DBin($fromNumber)),
																mysqli_real_escape_string($link,DBin($alertMessage)),
																mysqli_real_escape_string($link,DBin($alertMedia)),
																mysqli_real_escape_string($link,DBin($altTime)),
																mysqli_real_escape_string($link,DBin($userID)),
																mysqli_real_escape_string($link,DBin($groupID))
										);
									mysqli_query($link,$shcAlts);
								}
							}
							$selFollowup = "select * from appointment_followup_msgs where apt_id='".$aptID."'";
							$resFollowup = mysqli_query($link,$selFollowup);
							if(mysqli_num_rows($resFollowup)){
								while($rowFollowup = mysqli_fetch_assoc($resFollowup)){
									$altTime = $rowFollowup['message_date']." ".$rowFollowup['message_time'].":00";
									$alertMessage = DBout($rowFollowup['apt_message']);
									if(trim($rowFollowup['media'])!=''){
										$alertMedia = getServerUrl().'/uploads/'.$rowFollowup['media'];
									}else{
										$alertMedia = '';
									}
									$userID = $rowFollowup['user_id'];
									$shcFollowup = sprintf("insert into queued_msgs
															(
																to_number,
																from_number,
																message,
																media,
																type,
																message_time,
																user_id,
																group_id
															)
														values
															(
																'%s',
																'%s',
																'%s',
																'%s',
																'2',
																'%s',
																'%s',
																'%s'
															)",
										mysqli_real_escape_string($link,DBin($toNumber)),
										mysqli_real_escape_string($link,DBin($fromNumber)),
										mysqli_real_escape_string($link,DBin($alertMessage)),
										mysqli_real_escape_string($link,DBin($alertMedia)),
										mysqli_real_escape_string($link,DBin($altTime)),
										mysqli_real_escape_string($link,DBin($userID)),
										mysqli_real_escape_string($link,DBin($groupID))
									);
									mysqli_query($link,$shcFollowup);
								}
							}
						}
					}
				}
				else{
					$phoneID = DBin($_REQUEST['phone_number_id']);
					$groupID = DBin($_REQUEST['group_id']);
					$sql = sprintf("select
										s.phone_number,
										c.phone_number as groupNumber
									from 
										subscribers s,
										campaigns c ,
										subscribers_group_assignment sga
									where 
										s.id=sga.subscriber_id and
										s.status='1' and
										c.id=sga.group_id and
										sga.group_id = %s",
						mysqli_real_escape_string($link,DBin($groupID))
					);
					$res = mysqli_query($link,$sql);
					if(mysqli_num_rows($res)){
						$row = mysqli_fetch_assoc($res);
						$userPkgStatus = checkUserPackageStatus($_SESSION['user_id']);
						if($userPkgStatus['go']==false){
							$remainingCredits = 0;
							die($userPkgStatus['message']);
						}else{
							$remainingCredits = $userPkgStatus['remaining_credits'];
						}

						$apt_date = date("Y-m-d h:i a",strtotime($dateTime));
						$aptMessage = str_replace('%apt_date%',$apt_date,$aptMessage);
						$toNumber = $row['phone_number'];
						$fromNumber = $row['groupNumber'];
						sendMessage($fromNumber,$toNumber,$aptMessage,array(),$_SESSION['user_id'],$groupID,false);
						$selAlts = sprintf("select * from appointment_alerts where apt_id='%s'",
										mysqli_real_escape_string($link,DBin($aptID))
							);
						$resAlts = mysqli_query($link,$selAlts);
						if(mysqli_num_rows($resAlts)){
							while($rowAlts = mysqli_fetch_assoc($resAlts)){
								$altTime = $rowAlts['message_date']." ".$rowAlts['message_time'].":00";
								$alertMessage = DBout($rowAlts['apt_message']);
								if(trim($rowAlts['media'])!=''){
									$alertMedia = getServerUrl().'/uploads/'.$rowAlts['media'];
								}else{
									$alertMedia = '';
								}
								$userID = $rowAlts['user_id'];

								$shcAlts = sprintf("insert into queued_msgs
															(
																to_number,
																from_number,
																message,
																media,
																type,
																message_time,
																user_id,
																group_id
															)
														values
															(
																'%s',
																'%s',
																'%s',
																'%s',
																'2',
																'%s',
																'%s',
																'%s'
															)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($alertMessage)),
									mysqli_real_escape_string($link,DBin($alertMedia)),
									mysqli_real_escape_string($link,DBin($altTime)),
									mysqli_real_escape_string($link,DBin($userID)),
									mysqli_real_escape_string($link,DBin($groupID))
								);
								mysqli_query($link,$shcAlts);
							}
						}
						$selFollowup = sprintf("select * from appointment_followup_msgs where apt_id='%s'",
												mysqli_real_escape_string($link,DBin($aptID))
							);
						$resFollowup = mysqli_query($link,$selFollowup);
						if(mysqli_num_rows($resFollowup)){
							while($rowFollowup = mysqli_fetch_assoc($resFollowup)){
								$altTime = $rowFollowup['message_date']." ".$rowFollowup['message_time'].":00";
								$alertMessage = DBout($rowFollowup['apt_message']);
								if(trim($rowFollowup['media'])!=''){
									$alertMedia = getServerUrl().'/uploads/'.$rowFollowup['media'];
								}
								$userID = $rowFollowup['user_id'];
								$shcFollowup = sprintf("insert into queued_msgs
															(
																to_number,
																from_number,
																message,
																media,
																type,
																message_time,
																user_id,
																group_id
															)
														values
															(
																'%s',
																'%s',
																'%s',
																'%s',
																'2',
																'%s',
																'%s',
																'%s'
															)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($alertMessage)),
									mysqli_real_escape_string($link,DBin($alertMedia)),
									mysqli_real_escape_string($link,DBin($altTime)),
									mysqli_real_escape_string($link,DBin($userID)),
									mysqli_real_escape_string($link,DBin($groupID))
								);
								mysqli_query($link,$shcFollowup);
							}
						}
					}
					else{
						die('single message not sent');
					}
				}
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! appointment save successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to save appointment';
			}
			header("location: view_apts.php");
		}
			break;

		case "get_message_details":{
			$msgID = DBin($_REQUEST['msg_id']);
			$sql = sprintf("select * from sms_history where id='%s'",
									mysqli_real_escape_string($link,DBin($msgID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$row = mysqli_fetch_assoc($res);
				if($row['is_sent']=='true'){
					if($row['direction']=='out-bound')
						$direction = 'Delivered';
					else
						$direction = 'Received';
					?>
					<div class="form-group">
						<label>Status: <?php echo DBout($direction)?>.</label><br />
						<span><?php echo DBout($row['sms_sid'])?></span>
					</div>
					<?php
				}else{
					?>
					<div class="form-group">
						<label>Status: Failed.</label><br />
						<span><?php echo DBout($row['sms_sid'])?></span>
					</div>
					<?php
				}
			}
		}
			break;

		case "add_nexmo_to_install":{
			$phoneNumber = DBin($_REQUEST['phone_number']);
			$sql = sprintf("insert into users_phone_numbers
								(
									friendly_name,
									phone_number,
									iso_country,
									country,
									phone_sid,
									type,
									user_id
								)
							values
								(
									'%s',
									'%s',
									'US',
									'United States',
									'Nexmo',
									'3',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
		);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! number assigned to application.';
				echo DBout('1');
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to assign number to application.';
				echo DBout('0');
			}
		}
			break;

		case "remove_nexmo_from_install":{
			$phoneNumber = DBin($_REQUEST['phone_number']);
			$sql = sprintf("delete from users_phone_numbers where phone_number='%s' and user_id='%s'",
								mysqli_real_escape_string($link,DBin($phoneNumber)),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! number removed from application.';
				echo DBout('1');
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed! to remove number from application.';
				echo DBout('0');
			}
		}
			break;

		case "get_gateway_countries":{
			$adminSettings = getAppSettings("",true);
			$gateway = DBin($_REQUEST['gateway']);
			if($gateway=='twilio'){
				$countries = getTwilioCountries($adminSettings['twilio_sid'],$adminSettings['twilio_token']);
				for($i=0;$i<count($countries->Countries->Country);$i++){
					if($countries->Countries->Country[$i]->CountryCode=="US") {
						$sele = 'selected="selected"';
					}
					else {
						$sele = '';
					}
					?>
					<option <?php echo DBout($sele)?> value="<?php echo DBout($countries->Countries->Country[$i]->CountryCode)?>"><?php echo DBout($countries->Countries->Country[$i]->Country)?></option>
			   <?php }
			}else if($gateway=='plivo'){ ?>
				<option value="US">United States</option>
		<?php    }else if($gateway=='nexmo'){ ?>
				<option value="US">United States</option>
		<?php    }
		}
			break;

		case "process_bulk_sms":{
			$appSettings = getAppSettings($_SESSION['user_id']);
			date_default_timezone_set($appSettings['time_zone']);
			$bulkType = DBin($_REQUEST['bulk_type']);
			$client   = DBin($_REQUEST['client_id']);
			$fromNum  = DBin($_REQUEST['from_number']);
			$groupID  = DBin($_REQUEST['group_id']);
			$phoneID  = DBin($_REQUEST['phone_number_id']);
			$startDate= DBin($_REQUEST['start_date']);
			$endDate  = DBin($_REQUEST['end_date']);
			$daterangeGroupID = DBin($_REQUEST['daterange_group_id']);
			$smsID    = DBin($_REQUEST['hidden_sms_id']);
			$bulkSMS  = getBulkSMS($smsID);
			$smsText  = DBin($bulkSMS['message']);
			$smsMedia = DBin($bulkSMS['bulk_media']);
			$deviceID = (int) $_REQUEST['device_id'];

			if($client=='all'){
				if($bulkType=='1'){
					if($groupID=='all'){
						$sql = sprintf("select 
										c.id, 
										c.title, 
										s.phone_number
									from 
										campaigns c, 
										subscribers s, 
										subscribers_group_assignment sga
									where
										c.id=sga.group_id and
										sga.subscriber_id=s.id and 
										s.status='1'
									group by
										s.phone_number");
						$res = mysqli_query($link,$sql);
						if(mysqli_num_rows($res)){
							$index = 0;
							while($row = mysqli_fetch_assoc($res)){
								$toNumber   = $row['phone_number'];
								$fromNumber = $fromNum;
								$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($deviceID)),
									mysqli_real_escape_string($link,DBin($smsText)),
									mysqli_real_escape_string($link,DBin($smsMedia)),
									mysqli_real_escape_string($link,DBin($client)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($groupID)),
									mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
									mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

								);
								mysqli_query($link,$ins);
								DBout($index++);
							}
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! message sent to '.$index.' subscribers.';
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Failed! No subscriber found.';
						}
					}
					else{
						if($phoneID=='all'){
							$sql = sprintf("select 
											s.phone_number 
										from 
											subscribers s, 
											subscribers_group_assignment sga 
										where
											sga.group_id='%s' and
											sga.subscriber_id=s.id
										group by s.phone_number",
								mysqli_real_escape_string($link,DBin($groupID))
							);
							$res = mysqli_query($link,$sql);
							if(mysqli_num_rows($res)){
								$index = 0;
								while($row = mysqli_fetch_assoc($res)){
									$toNumber   = $row['phone_number'];
									$fromNumber = $fromNum;

									$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
										mysqli_real_escape_string($link,DBin($toNumber)),
										mysqli_real_escape_string($link,DBin($fromNumber)),
										mysqli_real_escape_string($link,DBin($deviceID)),
										mysqli_real_escape_string($link,DBin($smsText)),
										mysqli_real_escape_string($link,DBin($smsMedia)),
										mysqli_real_escape_string($link,DBin($client)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($groupID)),
										mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
										mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

									);
									mysqli_query($link,$ins);
									DBout($index++);
								}
								$_SESSION['message_status'] = 1;
								$_SESSION['message'] = 'Success! message sent to '.DBout($index).' subscribers.';
							}else{
								$_SESSION['message_status'] = 0;
								$_SESSION['message'] = 'Failed! No subscriber found.';
							}
						}
						else{
							$sql = sprintf("select phone_number from subscribers where id='%s'",
								mysqli_real_escape_string($link,DBin($phoneID))
							);
							$res = mysqli_query($link,$sql);
							if(mysqli_num_rows($res)){
								$row = mysqli_fetch_assoc($res);
								$toNumber   =DBout($row['phone_number']);
								$fromNumber = $fromNum;
								$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($deviceID)),
									mysqli_real_escape_string($link,DBin($smsText)),
									mysqli_real_escape_string($link,DBin($smsMedia)),
									mysqli_real_escape_string($link,DBin($client)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($groupID)),
									mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
									mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

								);
								mysqli_query($link,$ins);
								$_SESSION['message_status'] = 1;
								$_SESSION['message'] = 'Success! message sent to '.DBout($toNumber).' subscriber.';
							}else{
								$_SESSION['message_status'] = 0;
								$_SESSION['message'] = 'Failed! No subscriber found.';
							}
						}
					}
				}
			}
			else{
				if($bulkType=='1'){
					if($groupID=='all'){
						$sql = sprintf("select 
										c.id, 
										c.title, 
										s.phone_number
									from 
										campaigns c, 
										subscribers s, 
										subscribers_group_assignment sga
									where
										c.user_id='%s' and
										c.id=sga.group_id and
										sga.subscriber_id=s.id and 
										s.status='1'
									group by
										s.phone_number",
							mysqli_real_escape_string($link,DBin($client))
						);
						$res = mysqli_query($link,$sql);
						if(mysqli_num_rows($res)){
							$index = 0;
							while($row = mysqli_fetch_assoc($res)){
								$toNumber   = DBout($row['phone_number']);
								$fromNumber = $fromNum;
								$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($deviceID)),
									mysqli_real_escape_string($link,DBin($smsText)),
									mysqli_real_escape_string($link,DBin($smsMedia)),
									mysqli_real_escape_string($link,DBin($client)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($groupID)),
									mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
									mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

								);
								mysqli_query($link,$ins);
								DBout($index++);
							}
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! message sent to '.DBout($index).' subscribers.';
						}
						else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Failed! No subscriber found.';
						}
					}
					else{
						if($phoneID=='all'){
							$sql = sprintf("select 
											s.phone_number 
										from 
											subscribers s, 
											subscribers_group_assignment sga 
										where
											sga.group_id='%s' and
											sga.subscriber_id=s.id
										group by s.phone_number",
								mysqli_real_escape_string($link,DBin($groupID))
							);
							$res = mysqli_query($link,$sql);
							if(mysqli_num_rows($res)){
								$index = 0;
								while($row = mysqli_fetch_assoc($res)){
									$toNumber   = $row['phone_number'];
									$fromNumber = $fromNum;

									$ins =sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
										mysqli_real_escape_string($link,DBin($toNumber)),
										mysqli_real_escape_string($link,DBin($fromNumber)),
										mysqli_real_escape_string($link,DBin($deviceID)),
										mysqli_real_escape_string($link,DBin($smsText)),
										mysqli_real_escape_string($link,DBin($smsMedia)),
										mysqli_real_escape_string($link,DBin($client)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($groupID)),
										mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
										mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

									);
									mysqli_query($link,$ins);
									DBout($index++);
								}
								$_SESSION['message_status'] = 1;
								$_SESSION['message'] = 'Success! message sent to '.DBout($index).' subscribers.';
							}
							else{
								$_SESSION['message_status'] = 0;
								$_SESSION['message'] = 'Failed! No subscriber found.';
							}
						}
						else{
							$sql = sprintf("select phone_number from subscribers where id='%s'",
								mysqli_real_escape_string($link,DBin($phoneID))
							);
							$res = mysqli_query($link,$sql);
							if(mysqli_num_rows($res)){
								$row = mysqli_fetch_assoc($res);
								$toNumber   = DBout($row['phone_number']);
								$fromNumber = $fromNum;
								$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
									mysqli_real_escape_string($link,DBin($toNumber)),
									mysqli_real_escape_string($link,DBin($fromNumber)),
									mysqli_real_escape_string($link,DBin($deviceID)),
									mysqli_real_escape_string($link,DBin($smsText)),
									mysqli_real_escape_string($link,DBin($smsMedia)),
									mysqli_real_escape_string($link,DBin($client)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($groupID)),
									mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
									mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

								);
								mysqli_query($link,$ins)or die(mysqli_error($link));
								$_SESSION['message_status'] = 1;
								$_SESSION['message'] = 'Success! message sent to '.DBout($toNumber).' subscriber.';
							}
							else{
								$_SESSION['message_status'] = 0;
								$_SESSION['message'] = 'Failed! No subscriber found.';
							}
						}
					}
				}
				else{
					if($_REQUEST['daterange_group_id']=='all'){
						$startDate = date('Y-m-d',strtotime(DBin($_REQUEST['start_date'])));
						$endDate   = date('Y-m-d',strtotime(DBin($_REQUEST['end_date'])));
						$daterangeGroupID   = DBin($_REQUEST['daterange_group_id']);
						$sql = sprintf("select
										subscriber_id
									from
										subscribers_group_assignment
									where
										user_id='%s'",
							mysqli_real_escape_string($link,DBin($client))
						);
						$res = mysqli_query($link,$sql)or die(mysqli_error($link));
						if(mysqli_num_rows($res)){
							$index = 0;
							while($row = mysqli_fetch_assoc($res)){
								$sel = sprintf("select
												phone_number
											from
												subscribers
											where
												id='%s' and
												date(created_date) between '%s' and 
												'%s'
											group by
												phone_number",

									mysqli_real_escape_string($link,DBin($row['subscriber_id'])),
									mysqli_real_escape_string($link,DBin($startDate)),
									mysqli_real_escape_string($link,DBin($endDate))
								);
								$exe = mysqli_query($link,$sel);
								if(mysqli_num_rows($exe)){
									while($numbers = mysqli_fetch_assoc($exe)){
										$toNumber  = $numbers['phone_number'];
										$fromNumber= $fromNum;
										$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
											mysqli_real_escape_string($link,DBin($toNumber)),
											mysqli_real_escape_string($link,DBin($fromNumber)),
											mysqli_real_escape_string($link,DBin($deviceID)),
											mysqli_real_escape_string($link,DBin($smsText)),
											mysqli_real_escape_string($link,DBin($smsMedia)),
											mysqli_real_escape_string($link,DBin($client)),
											mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
											mysqli_real_escape_string($link,DBin($groupID)),
											mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
											mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

										);
										mysqli_query($link,$ins);
									}
								}
								DBout($index++);
							}
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! message sent to '.DBout($index).' subscribers.';
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Failed! No subscriber found.';
						}
					}
					else{
						$startDate = date('Y-m-d',strtotime(DBin($_REQUEST['start_date'])));
						$endDate   = date('Y-m-d',strtotime(DBin($_REQUEST['end_date'])));
						$daterangeGroupID   = DBin($_REQUEST['daterange_group_id']);
						$sql = sprintf("select
										*
									from
										subscribers_group_assignment
									where
										group_id='%s' and
										user_id='%s'",
							mysqli_real_escape_string($link,DBin($daterangeGroupID)),
							mysqli_real_escape_string($link,DBin($client))
						);
						$res = mysqli_query($link,$sql);
						if(mysqli_num_rows($res)){
							$index = 0;
							while($row = mysqli_fetch_assoc($res)){
								$sel = sprintf("select
												phone_number
											from
												subscribers
											where
												id='%s' and
												date(created_date) between '%s' and 
												'%s'
											group by
												phone_number",
									mysqli_real_escape_string($link,DBin($row['subscriber_id'])),
									mysqli_real_escape_string($link,DBin($startDate)),
									mysqli_real_escape_string($link,DBin($endDate))
								);
								$exe = mysqli_query($link,$sel);
								if(mysqli_num_rows($exe)){
									while($numbers = mysqli_fetch_assoc($exe)){
										$toNumber  = $numbers['phone_number'];
										$fromNumber= $fromNum;
										$ins = sprintf("insert into queued_msgs
												(
													to_number,
													from_number,
													device_id,
													message,
													media,
													send_to_user,
													user_id,
													group_id,
													sms_gateway,
													created_date
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
											mysqli_real_escape_string($link,DBin($toNumber)),
											mysqli_real_escape_string($link,DBin($fromNumber)),
											mysqli_real_escape_string($link,DBin($deviceID)),
											mysqli_real_escape_string($link,DBin($smsText)),
											mysqli_real_escape_string($link,DBin($smsMedia)),
											mysqli_real_escape_string($link,DBin($client)),
											mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
											mysqli_real_escape_string($link,DBin($groupID)),
											mysqli_real_escape_string($link,DBin($appSettings['sms_gateway'])),
											mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))

										);
										mysqli_query($link,$ins)or die(mysqli_error($link));
									}
									$index++;
								}
							}
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! message sent to '.$index.' subscribers.';
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Failed! no subscriber found.';
						}
					}
				}
			}
			$url = getServerUrl().'/cron.php';
			post_curl_mqs($url,array());
			
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
		break;

		case "load_chat":{
			$phoneID = DBin($_REQUEST['phoneID']);
			$sql = sprintf("select ch.*, s.phone_number, s.first_name from chat_history ch, subscribers s where ch.phone_id='%s' and s.id=ch.phone_id order by id asc",
							mysqli_real_escape_string($link,DBin($phoneID))
				);
			$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			if(mysqli_num_rows($res)>0){
				while($row = mysqli_fetch_assoc($res)){
					$ago = timeAgo($row['created_date']);
					if($row['direction']=='in'){
						?>
						<li class="left clearfix"><span class="chat-img pull-left">
							<img src="images/you.png" alt="User Avatar" class="img-circle" />
						</span>
							<div class="chat-body clearfix">
								<div class="header chat_header">
									<strong class="primary-font"><?php echo DBout($row['first_name'])?></strong> <small class="pull-right text-muted">
										<span class="fa fa-clock-o"></span><?php echo DBout($ago)?></small>
								</div>
								<p><?php echo DBout($row['message'])?></p>
							</div>
						</li>
						<?php
					}
					else{
						?>
						<li class="right clearfix"><span class="chat-img pull-right">
							<img src="images/me.png" alt="User Avatar" class="img-circle" />
						</span>
							<div class="chat-body clearfix">
								<div class="header chat_header">
									<small class=" text-muted"><span class="fa fa-clock-o"></span><?php echo DBout($ago)?></small>
									<strong class="pull-right primary-font"><?php echo DBout($_SESSION['first_name'])?></strong>
								</div>
								<p><?php echo DBout($row['message'])?></p>
							</div>
						</li>
						<?php
					}
					$up = sprintf("update 
										chat_history
									set
										is_read='1'
									where
										id='%s'",

								mysqli_real_escape_string($link,DBin($row['id']))
						);
					mysqli_query($link,$up);
				}
			}else{
				?>
				<li class="right clearfix">
					<div class="chat-body clearfix">
						<p>
							No chat history to display.
						</p>
					</div>
				</li>
				<?php
			}
		}
		break;

		case "save_chat_message":{
			$userPkgStatus = checkUserPackageStatus($_SESSION['user_id']);
			if($userPkgStatus['go']==false){
				$remainingCredits = 0;
				die($userPkgStatus['message']);
			}else{
				$remainingCredits = $userPkgStatus['remaining_credits'];
			}
			$deviceID = DBin($_REQUEST['deviceID']);
			$client = getTwilioConnection($_SESSION['user_id']);
			$to     = DBin(urldecode($_REQUEST['To']));
			$from   = DBin(urldecode($_REQUEST['From']));
			$body   = DBin(urldecode($_REQUEST['chatMessage']));
			$msgSid = sendMessage($from,$to,$body,"",$_SESSION['user_id'],"",$deviceID,true);
			echo DBout($msgSid);
			$direction = 'out';
			$sql = sprintf("insert into chat_history
								(
									phone_id,
									message,
									direction,
									user_id,
									message_sid,
									created_date
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($_REQUEST['phone_id'])),
									mysqli_real_escape_string($link,DBin($body)),
									mysqli_real_escape_string($link,DBin($direction)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($msgSid)),
									mysqli_real_escape_string($link,DBin(date('Y-m-d H:i:s')))
				);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			if($res){
				echo DBout('1');
			}else{
				echo DBout($msgSid);
			}
		}
			break;

		case "generate_apikey":{
			echo DBout(generateAPIKey());
		}
			break;

		case "update_email_templates":{
			$sql = sprintf("update application_settings set
												email_subject='%s',
												new_app_user_email='%s',
												email_subject_for_admin_notification='%s',
												new_app_user_email_for_admin='%s',
												success_payment_email_subject='%s',
												success_payment_email='%s',
												failed_payment_email_subject='%s',
												failed_payment_email='%s',
												payment_noti_subject='%s',
												payment_noti_email='%s'
											where
												user_id='%s'",

												mysqli_real_escape_string($link,DBin($_REQUEST['email_subject'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['new_app_user_email'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['email_subject_for_admin_notification'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['new_app_user_email_for_admin'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['success_payment_email_subject'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['success_payment_email'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['failed_payment_email_subject'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['failed_payment_email'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['payment_noti_subject'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['payment_noti_email'])),
												mysqli_real_escape_string($link,DBin($_SESSION['user_id']))

							);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Email templates are updated.';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_propend_msgs":{
			$sql = sprintf("update application_settings set
								append_text='%s',
								typo_message='%s',
								unsub_message='%s',
								gdpr_message='%s'
							where
								user_id='%s'",

								mysqli_real_escape_string($link,DBin($_REQUEST['append_text'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['typo_message'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['unsub_message'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['gdpr_message'])),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Messages are updated.';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_beacon_credentials":{
			$sql = sprintf("update application_settings set
								estimote_app_id='%s',
								estimote_app_token='%s' 
							where
								user_id='%s'",

							mysqli_real_escape_string($link,DBin($_REQUEST['estimote_app_id'])),
							mysqli_real_escape_string($link,DBin($_REQUEST['estimote_app_token'])),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Beacon Credentials are updated.';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;


		case "update_pricing_details":{
			$sql = sprintf("update application_settings set
								incoming_sms_charge='%s',
								outgoing_sms_charge='%s',
								mms_credit_charges='%s',
								per_credit_charges='%s'
							where
								user_id='%s'",

								mysqli_real_escape_string($link,DBin($_REQUEST['incoming_sms_charge'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['outgoing_sms_charge'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['mms_credit_charges'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['per_credit_charges'])),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Pricing details updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_payment_processor":{
			$sql = sprintf("update application_settings set
								payment_processor='%s',
								stripe_secret_key = '%s',
								stripe_publishable_key = '%s',
								auth_net_trans_key='%s',
								auth_net_api_login_id='%s',
								paypal_switch='%s',
								paypal_sandbox_email='%s',
								paypal_email='%s'
							where
								user_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['payment_processor'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['stripe_secret_key'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['stripe_publishable_key'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['auth_net_trans_key'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['auth_net_api_login_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['paypal_switch'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['paypal_sandbox_email'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['paypal_email'])),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))

				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Payment processor updated.';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_sms_gateways":{
			if(trim($_REQUEST['enable_sender_id'])=='')
				$_REQUEST['enable_sender_id'] = '0';

			if(trim($_REQUEST['enable_whatsapp'])=='')
				$_REQUEST['enable_whatsapp'] = '0';

			$sql = sprintf("update application_settings set
								sms_gateway='%s',
								nexmo_api_key='%s',
								nexmo_api_secret='%s',
								plivo_auth_id='%s',
								plivo_auth_token='%s',
								plivo_app_id='%s',
								twilio_sid='%s',
								twilio_token='%s',
								enable_sender_id='%s',
								enable_whatsapp='%s',
								twilio_sender_id='%s',
								signalwire_space_url='%s',
								signalwire_project_key='%s',
								signalwire_token='%s'
							where
								user_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['sms_gateway'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['nexmo_api_key'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['nexmo_api_secret'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['plivo_auth_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['plivo_auth_token'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['plivo_app_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['twilio_sid'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['twilio_token'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['enable_sender_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['enable_whatsapp'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['twilio_sender_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['signalwire_space_url'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['signalwire_project_key'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['signalwire_token'])),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))

				);
			$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			if($res){
				if($_REQUEST['whatsapp_business_number']){
					$sql_wa = sprintf("SELECT * FROM `users_phone_numbers` where type = '4' AND user_id='%s'",
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
					$res_wa = mysqli_query($link,$sql_wa);
					$nos = mysqli_num_rows($res_wa);

					if($nos==0){
						$sql_wa = sprintf("insert into `users_phone_numbers` (
														friendly_name,
														phone_number,
														type,
														user_id
											)
											values (
														'%s',
														'%s',
														4,
														'%s'
											)",
													mysqli_real_escape_string($link,DBin($_REQUEST['whatsapp_business_number'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['whatsapp_business_number'])),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_wa = mysqli_query($link,$sql_wa);
						if($res_wa) {
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! SMS gateway is updated.';
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Error! while adding whatsapp';
						}
					}
					else{
						$row_wa = mysqli_fetch_assoc($res_wa);
						$sql_wa = sprintf("update `users_phone_numbers` set 
													friendly_name = '%s',
													phone_number = '%s' 
													where id = '%s'",
												mysqli_real_escape_string($link,DBin($_REQUEST['whatsapp_business_number'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['whatsapp_business_number'])),
												mysqli_real_escape_string($link,DBin($row_wa['id']))
							);
						$res_wa = mysqli_query($link,$sql_wa);
						$_SESSION['message_status'] = 1;
						$_SESSION['message'] = 'Success! SMS gateway is updated.';
						if($res_wa) {
							$_SESSION['message_status'] = 1;
							$_SESSION['message'] = 'Success! SMS gateway is updated.';
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Error! while updating whatsapp';
						}
					}
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Success! SMS gateway settings are updated.';
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
		break;

		case "update_general_settings":{
			if($_FILES['app_logo']['name']!=''){
				$file = $_FILES['app_logo']['tmp_name'];
				$appLogo = uniqid().'.png';
				$output = 'images/'.$appLogo;
				ResizeImage($file,null,170,50,false,$output,false,false,100);
				if(trim($_REQUEST['hidden_app_logo'])!='nimble_messaging.png'){
					unlink('images/'.DBin($_REQUEST['hidden_app_logo']));
				}
			}else{
				$appLogo = DBin($_REQUEST['hidden_app_logo']);
				if(trim($appLogo)==''){
					$appLogo = DBin('nimble_messaging.png');
				}
			}

			if(trim($_REQUEST['is_double_optin'])=='')
				$_REQUEST['is_double_optin'] = '0';

			if(trim($_REQUEST['released_version'])!='')
				$version = DBin($_REQUEST['released_version']);
			else
				$version = '1.0.0';

			$sel = sprintf("select id from application_settings where user_id='%s'",
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("insert into application_settings
									(
										sidebar_color,
										admin_phone,
										time_zone,
										app_date_format,
										banned_words,
										user_id,
										device_id,
										user_type
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'0',
										'%s'
									)",
										mysqli_real_escape_string($link,DBin($_REQUEST['sidebar_color'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['admin_phone'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['time_zone'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['app_date_format'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['banned_words'])),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($_SESSION['user_type']))

					);
			}else{
				$sql = sprintf("update application_settings set
											sidebar_color='%s',
											admin_phone='%s',
											time_zone='%s',
											app_date_format='%s',
											admin_email='%s',
											banned_words='%s',
											app_logo='%s',
											api_key='%s',
											bitly_key='%s',
											bitly_token='%s',
											cron_stop_time_from='%s',
											cron_stop_time_to='%s'
										where
											user_id='%s'",
											mysqli_real_escape_string($link,DBin($_REQUEST['sidebar_color'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['admin_phone'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['time_zone'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['app_date_format'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['admin_email'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['banned_words'])),
											mysqli_real_escape_string($link,DBin($appLogo)),
											mysqli_real_escape_string($link,DBin($_REQUEST['api_key'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['bitly_key'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['bitly_token'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['cron_stop_time_from'])),
											mysqli_real_escape_string($link,DBin($_REQUEST['cron_stop_time_to'])),
											mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
								 );
			}
			$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			if($res){

				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! General settings updated.';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! while updating.';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "get_nexmo_existing_numbers":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			$apiKey = $appSettings['nexmo_api_key'];
			$apiSecret = $appSettings['nexmo_api_secret'];
			$url = "https://rest.nexmo.com/account/numbers/$apiKey/$apiSecret";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept: application/json'));
			$response = curl_exec($ch);
			curl_close($ch);
			$response = json_decode($response,true);
			$total = count($response['numbers']);
			$firstNum = $response['numbers'][0]['msisdn'];
			if(empty($firstNum)){
				echo DBout('No existing number found in your nexmo account.');
			}else{ ?>
				<table width="100%" align="center" class="table table-striped table-bordered table-hover">
				<tr>
				<td>Sr#</td>
				<td>Phone Number</td>
				<td>Country</td>
				<td>Capabilities</td>
				<td>Manage</td>
				</tr>
					<?php
				$index = 1;
				for($i=0; $i<$total; $i++){
					?>
					<tr>
					<td><?php echo DBout($index)?></td>
					<td><?php echo DBout($response['numbers'][$i]['msisdn'])?></td>
					<td><?php echo DBout($response['numbers'][$i]['country'])?></td>
					<td>
						<?php
					if($response[$i]['features'][0]=='VOICE'){ ?>
					   Voice <img src="images/tick.gif">
			  <?php      }else{ ?>
						Voice <img src="images/cross.png">
			  <?php     } if($response[$i]['features'][1]=='SMS'){ ?>
						SMS <img src="images/tick.gif">
			   <?php     } else { ?>
						SMS <img src="images/cross.png">
			   <?php     } ?>
				   </td>
				   <td align="center">
					   <?php
					if($_SESSION['user_type']=='1'){ ?>
						<a href="#nexmoInfoModel" data-toggle="modal"><i class="fa fa-exclamation-triangle nexmo_modal_style" aria-hidden="true"></i></a>

						<a href="javascript:void(0)" onclick="addNexmoToInstall('<?php echo DBout($response['numbers'][$i]['msisdn'])?>')"><img src="images/add-number.png" title="Add Number" class="add_number_style"></a>
				  <?php  } ?>
				   <img src="images/cross.png" width="20" class="pointer" title="Release Number" onclick="removeNexmoFromInstall('<?php echo DBout($response['numbers'][$i]['msisdn'])?>')">
				   </td>
					</tr>
				   <?php DBout($index++);
				} ?>
				</table>
				<?php
			}
		}
			break;

		case "get_nexmo_existing_numbers_in_subaccount":{
			$userID = DBin($_REQUEST['user_id']);
			$sql = sprintf("select * from users_phone_numbers where user_id='%s' and type='3'",
							mysqli_real_escape_string($link,DBin($userID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$index = 1; ?>
				<table width="100%" align="center" class="table table-striped table-bordered table-hover">
				<tr>
				<td width="5%">Sr#</td>
				<td>Phone Number</td>
				</tr>
					<?php
				while($row = mysqli_fetch_assoc($res)){ ?>
					<tr>
					<td><?php echo DBout($index++)?></td>
					<td><?php echo DBout($row['phone_number'])?></td>
					</tr>
		   <?php     } ?>
				</table>
		   <?php }else{ ?>

				<tr>
				<td colspan="2">No number found.</td>
				</tr>
		 <?php   }
		}
			break;

		case "buy_nexmo_number":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			$apiKey = $appSettings['nexmo_api_key'];
			$apiSecret = $appSettings['nexmo_api_secret'];
			$phoneNumber= DBin($_REQUEST['phoneNumber']);
			$ISOCountry = DBin($_REQUEST['isoCountry']);
			$base_url = 'https://rest.nexmo.com';
			$action =   '/number/buy';
			$theurl = $base_url . $action . "?" .  http_build_query(array(
					'api_key' => $apiKey,
					'api_secret' => $apiSecret,
					'country' => $ISOCountry,
					'msisdn' => $phoneNumber,
					'answer_url' => getServerUrl().'/sms_controlling.php'
				));
			$ch = curl_init($theurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_HTTPHEADER,array("Accept: application/json","Content-Length: 0"));
			curl_setopt($ch,CURLOPT_HEADER,array('Content-Type: application/x-www-form-urlencoded'));
			curl_setopt($ch,CURLOPT_HEADER,1);
			$response = curl_exec($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = DBin(substr($response, $header_size));
			if(strpos($header, '200')){
				$phoneSid = 'Nexmo';
				$sel = sprintf("select id from users_phone_numbers where phone_number='%s'",
								mysqli_real_escape_string($link,DBin($phoneNumber))
					);
				$exe = mysqli_query($link,$sel);
				if(mysqli_num_rows($exe)==0){
					$sql = sprintf("insert into users_phone_numbers
								(friendly_name,phone_number,user_id,type,phone_sid)values
								('%s','%s','%s','3','%s')",
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($phoneSid))
						);
					$res = mysqli_query($link,$sql);
					if($res){
						$_SESSION['message_status'] = 1;
						$_SESSION['message'] = 'Success! Phone number has been purchased successfully.';
					}else{
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Unknown error occured.';
					}
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = "Your request failed because: ".$body;
			}
		}
			break;

		case "search_nexmo_numbers":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			$apiKey = $appSettings['nexmo_api_key'];
			$apiSecret = $appSettings['nexmo_api_secret'];
			$ISOCountry = DBin($_REQUEST['ISOCountry']);
			$base_url = 'https://rest.nexmo.com';
			$action =   '/number/search';
			$theurl = $base_url . $action . "?" .  http_build_query(array(
					'api_key' => $apiKey,
					'api_secret' => $apiSecret,
					'country' => $ISOCountry
				));
			$ch = curl_init($theurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
			curl_setopt($ch, CURLOPT_HEADER,array('Content-Type: application/x-www-form-urlencoded'));
			curl_setopt($ch, CURLOPT_HEADER, 1);
			$response = curl_exec($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			if (strpos($header, '200')){
				$virtual_numbers = json_decode($body, true);
				if(!empty($virtual_numbers)){
					?>
					<table width="100%" align="center" class="table table-striped table-bordered table-hover">
					<tr>
					<td>&nbsp;</td>
					<td>Phone Number</td>
					<td>Country</td>
					<td>Monthly Fee</td>
					<td>Type</td>
					<td>Capabilities</td>
					</tr>
						<?php
					foreach($virtual_numbers['numbers'] as $number){ ?>
						<tr>
						<td><input type="radio" name="nexmo_buy_number" class="nexmo_buy_number" value="'.$number['msisdn'].'"></td>
						<td><?php echo DBout($number['msisdn'])?></td>
						<td><?php echo DBout($number['country'])?></td>
						<td><?php echo DBout($number['cost'])?></td>
						<td><?php echo DBout($number['type'])?></td>
						<td> <?php
						if($number['features'][0]=='VOICE'){ ?>
							Voice <img src="images/tick.gif">
						<?php  } else{ ?>
							Voice <img src="images/cross.png">
					<?php    }
						if($number['features'][1]=='SMS'){ ?>
							SMS <img src="images/tick.gif">
					  <?php  }else{ ?>
							SMS <img src="images/cross.png">
					<?php    } ?>
						</td>
				 <?php   } ?>
					 </table>
					<input type="button" value="Buy Number" class="btn btn-primary" onclick="buyNexmoNumber();">
			   <?php
				}else{
					echo DBout("No number found or country not supported.");
				}
			}else{
				echo DBout("Your request failed because:\n");
				echo DBout($body);
			}
		}
		break;

		case "remove_plivo_from_install":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			$phoneNumber = DBin($_REQUEST['phoneNumber']);
			$sel = sprintf("select id from users_phone_numbers where phone_number='%s'",
			mysqli_real_escape_string($link,DBin($phoneNumber))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)){
				$sql = sprintf("delete from users_phone_numbers where phone_number='%s'",
						mysqli_real_escape_string($link,DBin($phoneNumber))
					);
				$res = mysqli_query($link,$sql);
				if($res){
					require_once("plivo/vendor/autoload.php");
					require_once("plivo/vendor/plivo/plivo-php/plivo.php");
					$p = new RestAPI($appSettings['plivo_auth_id'], $appSettings['plivo_auth_token']);

					$params = array(
						'number' => $phoneNumber
					);
					$p->unlink_application_number($params);

					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Phone number has been successfully removed from this install.';
					echo DBout('1');
				}else{
					echo DBout('Unknown error occured.');
				}
			}else{
				echo DBout('This number not assigned to this install.');
			}
		}
			break;

		case "get_post_message":{
			$sel = sprintf("select post_message from campaigns where id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['camp_id']))
				);
			$exe = mysqli_query($link,$sel);
			$row = mysqli_fetch_assoc($exe);
			echo DBout($row['post_message']);
		}
			break;

		case "twitter_credentials":{

			$qry = sprintf("update users set tw_access_token='%s', 
					tw_access_token_secret='%s',
					tw_consumer_key='%s',
					tw_consumer_secret='%s'
					 where id ='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['tw_access_token'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['tw_access_token_secret'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['tw_consumer_key'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['tw_consumer_secret'])),
						mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			mysqli_query($link, $qry);
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Twitter Credentials Saved Successfully.';
			?>
			<script> window.location = 'profile.php'; </script>
			<?php
			die();
			exit;

		}
			break;

		case "add_plivo_number_to_install":{
			$appSettings = getAppSettings($_SESSION['user_id']);
			$phoneNumber = $_REQUEST['phoneNumber'];
			require_once("plivo/vendor/autoload.php");
			require_once("plivo/vendor/plivo/plivo-php/plivo.php");
			$p = new RestAPI($appSettings['plivo_auth_id'], $appSettings['plivo_auth_token']);

			if(trim($appSettings['plivo_app_id'])==''){ // Creating new app
				$url = "https://api.plivo.com/v1/Account/".$appSettings['plivo_auth_id']."/Application/";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_USERPWD, $appSettings['plivo_auth_id'].":".$appSettings['plivo_auth_token']);
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPGET, true );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0');
				$data = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($data,true);

				$appID = '';
				for($i=0; $i < count($data["objects"]); $i++){
					$appName = $data["objects"][$i]['app_name'];
					if(trim($appName) == 'Nimble Messaging Ranksol'){
						$appID = $data["objects"][$i]['app_id'];
						break;
					}
				}

				if($appID == ''){ //create new app
					$params = array(
						'message_url' => getServerUrl().'/sms_controlling.php',
						'app_name' => 'Nimble Messaging Ranksol',
						'message_method' => 'GET'
					);
					$response   = $p->create_application($params);
					$appID = $response['response']['app_id'];
					$appParams = array(
						'number' => $phoneNumber,
						'app_id' => $appID
					);
					$p->link_application_number($appParams);
				}else{ // assign app
					$appParams = array(
						'number' => $phoneNumber,
						'app_id' => $appID
					);
					$p->link_application_number($appParams);
				}
				$sql = sprintf("update application_settings set plivo_app_id='%s' where user_id='%s'",
						mysqli_real_escape_string($link,DBin($appID)),
						mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
					);
				
				mysqli_query($link,$sql);
			}else{ // linking app
				$appParams = array(
					'number' => $phoneNumber,
					'app_id' => $appSettings['plivo_app_id']
				);
				$p->link_application_number($appParams);
			}
			$sel = sprintf("select id from users_phone_numbers where phone_number='%s'",
					mysqli_real_escape_string($link,DBin($phoneNumber))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("insert into users_phone_numbers
						(
							friendly_name,
							phone_number,
							user_id,
							type
						)
					values
						(
							'%s',
							'%s',
							'%s',
							'2'
						)",
					mysqli_real_escape_string($link,DBin($phoneNumber)),
					mysqli_real_escape_string($link,DBin($phoneNumber)),
					mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
				$res = mysqli_query($link,$sql);
				if($res){
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Phone number has been successfully assigned to this install.';
					echo DBout('1');
				}else{
					$_SESSION['message_status'] = 0;
					echo DBout('Unknown error occured.');
				}
			}else{
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Phone number has been successfully assigned to this install.';
				echo '1';
			}
		}
		break;

		case "buy_plivo_number":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			require_once("plivo/vendor/autoload.php");
			require_once("plivo/vendor/plivo/plivo-php/plivo.php");
			$p = new RestAPI($appSettings['plivo_auth_id'], $appSettings['plivo_auth_token']);
			$buyNums = array(
				'number' => DBin($_REQUEST['phoneNumber'])
			);
			$numResponse = $p->buy_phone_number($buyNums);
			if($numResponse['status']=='201'){
				$purchasedNumber = DBin($numResponse['response']['numbers'][0]['number']);
				$phoneSid = DBin($numResponse['response']['api_id']);
				$finalNum = DBin($purchasedNumber);
				$sel = sprintf("select id from users_phone_numbers where phone_number='%s'",
							mysqli_real_escape_string($link,DBin($phoneNumber))
					);
				$exe = mysqli_query($link,$sel);
				if(mysqli_num_rows($exe)==0){
					$sql = sprintf("insert into users_phone_numbers
								(friendly_name,phone_number,user_id,type,phone_sid)values
								('%s','%s','%s','2','%s')",
								mysqli_real_escape_string($link,DBin($finalNum)),
								mysqli_real_escape_string($link,DBin($finalNum)),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
								mysqli_real_escape_string($link,DBin($phoneSid))
						);
					$res = mysqli_query($link,$sql);
					if($res){
						$_SESSION['message_status'] = 1;
						$_SESSION['message'] = 'Success! Phone number has been purchased successfully.';
						if(trim($appSettings['plivo_app_id'])==''){
							$params = array(
								'message_url' => getServerUrl().'/sms_controlling.php',
								'app_name' => 'Nimble Messaging Ranksol',
								'message_method' => 'GET'
							);
							$response   = $p->create_application($params);
							$plivoAppID = $response['response']['app_id'];
							$appParams = array(
								'number' => $purchasedNumber,
								'app_id' => $plivoAppID
							);
							$p->link_application_number($appParams);
							$sql2 = sprintf("update application_settings set plivo_app_id='%s' where user_id='%s'",
												mysqli_real_escape_string($link,DBin($plivoAppID)),
												mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
								);
							mysqli_query($link,$sql2);
						}else{
							$appParams = array(
								'number' => $purchasedNumber,
								'app_id' => $appSettings['plivo_app_id']
							);
							$p->link_application_number($appParams);
						}
						echo DBout('1');
					}else{
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Unknown error occured.';
					}
				}
			}
		}
			break;

		case "get_plivo_existing_numbers_for_subaccount":{
			$userID = DBin($_REQUEST['user_id']);
			$sql = sprintf("select * from users_phone_numbers where user_id='%s' and type='2'",
			mysqli_real_escape_string($link,DBin($userID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$index = 1;
				?>
				<table width="100%" align="center" class="table table-striped table-bordered table-hover">
				<tr>
				<td>Sr#</td>
				<td>Phone Number</td>
				</tr>
					<?php while($row = mysqli_fetch_assoc($res)){ ?>
					<tr>
					<td><?php echo DBout($index++)?></td>
					<td><?php echo DBout($row['phone_number'])?></td>
					</tr>
			<?php    } ?>
				</table>
		<?php    }else{ ?>
				<tr>
				<td colspan="2">No number found.</td>
				<t/r>
		<?php   }
		}
			break;

		case "get_plivo_existing_numbers":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			require_once("plivo/vendor/autoload.php");
			require_once("plivo/vendor/plivo/plivo-php/plivo.php");
			$p = new RestAPI($appSettings['plivo_auth_id'], $appSettings['plivo_auth_token']);
			$index = 1;
			for($i=0;$i<=500;$i+=20){
				$response = $p->get_numbers(array('limit'=>'0','offset'=>$i));
				if($response['response']['objects'][0]!=''){ ?>
					<table width="100%" align="center" class="table table-striped table-bordered table-hover">';
					<tr>
					<td>Sr#</td>
					<td>Phone Number</td>
					<td>Country</td>
					<td>Capabilities</td>'
					<td>Manage</td>
					</tr>
				   <?php foreach($response['response']['objects'] as $number){ ?>
					   <tr>
						<td><?php echo DBout($index++)?></td>
						<td><?php echo DBout($number['number'])?></td>
						<td><?php echo DBout($number['region'])?></td>
						<td>
							<?php if($number['sms_enabled']=='1'){ ?>
							Voice <img src="images/tick.gif">
					 <?php   }else{ ?>
							Voice <img src="images/cross.png">
					 <?php   }
						if($number['voice_enabled']=='1'){ ?>
							SMS <img src="images/tick.gif">
					 <?php   }else{ ?>
							SMS <img src="images/cross.png">
				   <?php     } ?>
						</td>
						   <td align="center">
							   <?php
						if($_SESSION['user_type']=='1'){ ?>
							<img src="images/add-number.png" title="Add Number" class="add_number_style" onclick="addPlivoToInstall('<?php echo DBout($number['number'])?>')">
					  <?php  } ?>
							   <img src="images/cross.png" width="20" class="pointer" title="Release Number" onclick="removePlivoFromInstall(<?php echo DBout($number['number'])?>')">
						</td>
						</tr>

				 <?php   } ?>
					</table>
					<?php
				}
				else{ ?>
					<tr><td colspan="3">No number found.</td></tr>
				 <?php   break;
				}
			}
		}
			break;

		case "search_plivo_numbers":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);
			$pattern = DBin($_REQUEST['pattern']);
			$state   = DBin($_REQUEST['state']);
			require_once("plivo/vendor/autoload.php");
			require_once("plivo/vendor/plivo/plivo-php/plivo.php");
			$p = new RestAPI($appSettings['plivo_auth_id'], $appSettings['plivo_auth_token']);
			$params = array(
				'country_iso' => 'US',
				'type' => 'local',
				'pattern' => $pattern,
				'region' => $state
			);
			$response = $p->search_phone_numbers($params);
			if($response['status']=='200'){ ?>
				<table id="plivoTable" width="100%" align="center" class="table table-striped table-bordered table-hover">
				<thead>
				<tr>
				<th>&nbsp;</th>
				<th>Phone Number</th>
				<th>Country</th>
				<th>Monthly Fee</th>
				<th>Capabilities</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach($response['response']['objects'] as $number){ ?>
					<tr>
					<td><input type="radio" name="plivo_buy_number" class="plivo_buy_number" value="<?php echo DBout($number['number'])?>"></td>
					<td><?php echo DBout($number['number'])?></td>
					<td><?php echo DBout($number['region'])?></td>
					<td><?php echo DBout($number['monthly_rental_rate'])?></td>
					<td>
							   <?php if($number['sms_enabled']=='1'){ ?>


										Voice <img src="images/tick.gif">
							   <?php     }else{ ?>
										Voice <img src="images/cross.png">
							   <?php    } if($number['voice_enabled']=='1'){ ?>
										SMS <img src="images/tick.gif">
							   <?php    }else{ ?>
										SMS <img src="images/cross.png">
							   <?php    } ?>
					</td>
						<?php   } ?>
				</tbody>
			   </table>
				<input type="button" value="Buy Number" class="btn btn-primary" onclick="buyPlivoNumber();">
		 <?php   }else{
				print_r($response);
			}
		}
			break;

		case "upgrade_user_package":{
			$pkgID    = DBin($_REQUEST['pkg_id']);
			$pkgPrice = DBin($_REQUEST['pkg_price']);
			$pkgTitle = DBin($_REQUEST['pkg_title']);
			$userID   = DBin($_REQUEST['user_id']);
			$pkgInfo  = getPackageInfo($pkgID);
			$appSettings = getAppSettings($userID,true);

			if($appSettings['payment_processor']==2){
			}
			else{
				$redirectUrl = getServerUrl();
				$notifyUrl   = getServerUrl().'/upgrade_pkg_notify.php';
				if($appSettings['paypal_switch']=='1'){
					$endPoint   = 'https://www.paypal.com/cgi-bin/webscr';
					$businessEmail = $appSettings['paypal_email'];
				}else{
					$endPoint   = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
					$businessEmail = $appSettings['paypal_sandbox_email'];
				}
				echo DBout("Redirecting to paypal..."); ?>
				<form action="<?php echo DBout($endPoint)?>" name="" method="post" id="recurring_payment_form">
							<input type="hidden" value="<?php echo DBout($businessEmail)?>" name="business">
							<input type="hidden" name="return" value="<?php echo DBout($redirectUrl)?>" />
							<input type="hidden" name="cancel_return" value="<?php echo DBout($notifyUrl)?>" />
							<input type="hidden" name="notify_url" value="<?php echo DBout($notifyUrl)?>" />
							<input type="hidden" name="cmd" value="_xclick-subscriptions" />
							<input type="hidden" name="no_note" value="1" />

							<input type="hidden" name="no_shipping" value="1">
							<input type="hidden" name="currency_code" value="USD">
							<input type="hidden" value="<?php echo DBout($pkgTitle)?> SMS Plan" name="item_name">
							<input type="hidden" name="a3" value="<?php echo DBout($pkgPrice)?>" />
							<input type="hidden" name="p3" value="1" />
							<input type="hidden" name="t3" value="M" />
							<input type="hidden" name="src" value="1" />
							<input type="hidden" name="sra" value="1" />
							<input type="hidden" name="custom" value="<?php echo DBout($pkgID.'_'.$userID)?>" />';
				<?php  if($pkgInfo['is_free_days']=='1'){ ?>
					<input type="hidden" name="a1" value="0">';
					<input type="hidden" name="p1" value="<?php echo DBout($pkgInfo['free_days'])?>">
					<input type="hidden" name="t1" value="D">
			   <?php } ?>
				</form>
				<script>document.forms["recurring_payment_form"].submit();</script>
		  <?php  }
		}
			break;

		case "export_subs":{
			$campaignID = DBin($_REQUEST['export_campaign_id']);
			exportSubscribers($campaignID,$_SESSION['user_id']);
			downloadFile('subscribers.csv');
			unlink("subscribers.csv");
		}
			break;

		case "export_history":{
			$file = exportHistory();
			downloadFile($file);
			unlink($file);
		}
			break;

		case "forgot_pass":{
			$sql = sprintf("select email,business_name from users where email ='%s' ",
								mysqli_real_escape_string($link,DBin($_REQUEST['email'])));
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)==0){
				$_SESSION['message'] = 0;
			}else{
				$rowu = mysqli_fetch_assoc($res);
				$companyName = DBin($rowu['business_name']);
				if(trim($companyName)==''){
					$companyName = DBin('Company Name');
				}

				$randompass = generatePassword();
				$subject = "Welcome To ".$companyName." (Password Reset)";
				$to = DBin($_REQUEST['email']);
				$from = 'admin@'.$_SERVER['SERVER_NAME'];
				$msg = "Please use this password to login into your account.<br><br><strong>Password: ".$randompass."</strong><br><br>Best Regards: ".$companyName.".<br><br>Thanks";
				$FullName= 'Admin';
				sendEmail($subject,$to,$from,$msg,$FullName);

				$randompass = password_hash($randompass,PASSWORD_DEFAULT);
				$qry = sprintf("update users set password='%s' where email ='%s' ",
							mysqli_real_escape_string($link,$randompass),
							mysqli_real_escape_string($link,DBin($_POST['email']))
					);
				mysqli_query($link, $qry);
				$_SESSION['message'] = 1;
			}
			header("location:forgot_password.php");
		}
		break;

		case "create_new_file":{
			$redirectURL = str_replace('&page=new','&page='.$_REQUEST['new_file_name'], $_SERVER['HTTP_REFERER']);
			$script = $_REQUEST['new_script'];
			$res = file_put_contents($_REQUEST['new_file_name'],$script);
			if($res===false){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Failed to create new file.';
			}else{
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'File created.';
			}
			header('location: '.$redirectURL);
		}
			break;

		case "update_script":{
			$script = $_REQUEST['script'];
			$res = file_put_contents($_REQUEST['file_name'],$script);
			if($res===false){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = ' Failed to update file.';
			}else{
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = ' Updated.';
			}
			header('location: '.$_SERVER['HTTP_REFERER']);
		}
			break;

		case "download_sample_csv":{
			downloadFile('sample.csv');
		}
			break;

		case "import_subs":{
			$ext = getExtension($_FILES['imported_csv']['name']);
			if($ext=='csv'){
				$campaignID = DBin($_REQUEST['imported_campaign_id']);
				$fileName = uniqid().'.'.$ext;
				$tmpName  = $_FILES['imported_csv']['tmp_name'];
				$res = move_uploaded_file($tmpName,'uploads/'.$fileName);
				if($res){
					importSubscribers($fileName,$campaignID,$_SESSION['user_id']);
					unlink('uploads/'.$fileName);
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Process completed successfully.';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Unkown error has occured while saving info, please try again.';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Not a valid csv file';
			} ?>
			<script>window.location="view_subscribers.php"</script>
	 <?php   }
			break;

		case "add_admin_account":{
			$password = DBin($_REQUEST['password']);
			$rePassword = DBin($_REQUEST['retype_password']);
			if($password==$rePassword){
				$ins = sprintf("insert into users               
									(
										first_name,
										last_name,
										email,
										password,
										type,
										business_name,
										tcap_ctia,
										msg_and_data_rate
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'%s',
										'1',
										'%s',
										'%s',
										'%s'
									)",
									mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,password_hash($_REQUEST['password'],PASSWORD_DEFAULT)),
									mysqli_real_escape_string($link,DBin($_REQUEST['business_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['tcap_ctia'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_and_data_rate']))
					);
				$exe = mysqli_query($link,$ins);
				if($exe){
					$userID = mysqli_insert_id($link);
					$appVersion = DBin($_REQUEST['app_version']);
					$sqls   = sprintf("insert into application_settings 
											(
												sms_gateway,
												version,
												user_id,
												app_logo,
												app_date_format,
												user_type,
												payment_processor,
												paypal_switch,
												incoming_sms_charge,
												outgoing_sms_charge,
												mms_credit_charges,
												per_credit_charges,
												sidebar_color,
												android_app_server_key,
												time_zone,
												product_purchase_code,
												device_id
											)
										values
											(
												'twilio',
												'%s',
												'%s',
												'nimble_messaging.png',
												'm-d-Y',
												'1',
												'1',
												'0',
												'1',
												'1',
												'2',
												'0.1',
												'purple',
								'AAAAbQYAco4:APA91bH7DQomggZ-XUXhwzWF5RW8TKo80jTOkDYeepjM-OfPMYHMCOtjM69zn6cdrhknBBve4V8QJ8052jS7OOvK55B0s4hMtLcgwFozsgCKHFt9Da8NSkj64MDusvkWmaqjSjIqsRh2',
												'%s',
												'%s',
												'0'
											)",
												mysqli_real_escape_string($link,DBin($appVersion)),
												mysqli_real_escape_string($link,DBin($userID)),
												mysqli_real_escape_string($link,DBin($_REQUEST['time_zone'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['pro_purchase_code']))
						);
					mysqli_query($link,$sqls);
					$appUrl  = getServerUrl();
					$subject = 'Welcome To Nimble Messaging';
					$to      = DBin($_REQUEST['email']);
					$from    = 'admin@'.$_SERVER['SERVER_NAME'];
					$msg     = "Hi ".DBin($_REQUEST['first_name']).' '.DBin($_REQUEST['last_name']).",<br>";
					$msg    .= "Welcome to Nimble Messaging applicaiton, your login credentials are mentioned below.<br>";
					$msg    .= "Login email: ".DBin($_REQUEST['email']).",<br>";
					$msg    .= "Login Password : ".DBin($_REQUEST['password'])."<br>";
					$msg    .= "Please login by clicking on below mentioned URL.<br>";
					$msg    .= '<a href="'.DBin($appUrl).'">'.DBin($appUrl).'</a>';
					$FullName= 'Admin';
					sendEmail($subject,$to,$from,$msg,$FullName);
					header('location: installer/thanku.php?id='.encode($userID));
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Unkown error has occured while saving info, please try again.';
					header('location: '.$_SERVER['HTTP_REFERER']);
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Re-Password is not matching with your password.';
				header('location: '.$_SERVER['HTTP_REFERER']);
			}
		}
			break;

		case "save_installer_db_info":{
			$hname = $_REQUEST['hostname'];
			$dbname= $_REQUEST['dbname'];
			$uname = $_REQUEST['username'];
			$pword = $_REQUEST['password'];
			$con = mysqli_connect($hname, $uname, $pword, $dbname);
			if(!$con){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Provided database information is wrong.';
				header('location: '.$_SERVER['HTTP_REFERER']);
			}else{
				$dbFile = fopen('database.php','w') or die("Unable to open file!");
				$content= '<?php
									$hostname = "'.$hname.'";
									$username = "'.$uname.'";
									$password = "'.$pword.'";
									$database = "'.$dbname.'";
									$link = mysqli_connect($hostname, $username, $password, $database);
								?>';
				fwrite($dbFile, $content);
				fclose($dbFile);
				$dbStructure = dirname(__FILE__).'/installer/structure_nimble_messaging.sql';
				$lines = file($dbStructure);
				if(is_array($lines)){
					$importSql = "";
					foreach($lines as $line){
						$importSql .= $line;
						if(substr(trim($line), strlen(trim($line))-1) == ";"){
							mysqli_query($con,$importSql);
							$importSql = "";
						}
					}
				}
				header('location: installer/add_personal_info.php');
			}
		}
			break;

		case "check_db_conn":{
			$hname = $_REQUEST['hostname'];
			$dbname= $_REQUEST['dbname'];
			$uname = $_REQUEST['username'];
			$pword = $_REQUEST['password'];
			$con = mysqli_connect($hname, $uname, $pword, $dbname);
			if(!$con){
				echo DBout('Error: '.mysqli_error($con));
			}else{
				echo DBout('1');
			}
		}
			break;
		case "delete_bulk_sms":{
			$sql = sprintf("delete from bulk_sms where id='%s'",
			mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Message deleted';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Message not deleted';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_bulk_sms":{
			if($_FILES['bulk_media']['name']!=''){
				$ext = getExtension($_FILES['bulk_media']['name']);
				$extns = array('jpg','jpeg','png','bmp','gif','mp3','mp4','pdf','txt');
				if(!in_array($ext,$extns)){
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Select a valid file type';
					header("location:".$_SERVER['HTTP_REFERER']);
				}else{
					$fileName = uniqid().'_'.$_FILES['bulk_media']['name'];
					$tmpName  = $_FILES['bulk_media']['tmp_name'];
					move_uploaded_file($tmpName,'uploads/'.$fileName);
					$bulk_media = getServerUrl().'/uploads/'.$fileName;
				}
			}else{
				$bulk_media = DBin($_REQUEST['hidden_bulk_media']);
			}

			$bulkMessage = DBin($_REQUEST['bulk_sms']);
			$sql = sprintf("update bulk_sms set
								message='%s',
								bulk_media='%s'
							where
								id='%s'",
							mysqli_real_escape_string($link,DBin($bulkMessage)),
							mysqli_real_escape_string($link,DBin($bulk_media)),
							mysqli_real_escape_string($link,DBin($_REQUEST['bulk_id']))
				);
			$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Message updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while updating message';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "save_bulk_sms":{
			if($_FILES['bulk_media']['name']!=''){
				$ext = getExtension($_FILES['bulk_media']['name']);
				$extns = array('jpg','jpeg','png','bmp','gif','mp3','mp4','pdf','txt');
				if(!in_array($ext,$extns)){
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Select a valid file type';
					header("location:".$_SERVER['HTTP_REFERER']);
				}else{
					$fileName = uniqid().'_'.$_FILES['bulk_media']['name'];
					$tmpName  = $_FILES['bulk_media']['tmp_name'];
					move_uploaded_file($tmpName,'uploads/'.$fileName);
					$bulk_media = getServerUrl().'/uploads/'.$fileName;
				}
			}
			$bulkMessage = DBin($_REQUEST['bulk_sms']);
			$sql = sprintf("insert into bulk_sms
								(message,user_id,bulk_media)
							values
								('%s','%s','%s')",
							mysqli_real_escape_string($link,DBin($bulkMessage)),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
							mysqli_real_escape_string($link,DBin($bulk_media))
				);
			$res = mysqli_query($link,$sql);
			if($res){

				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Message saved';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while saving message';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "buy_credits":{
			$appSettings = getAppSettings($_SESSION['user_id'],true);

			if($appSettings['payment_processor']==2){
				include_once("pay_with_authrize.php");
			}else{
				if($appSettings['paypal_switch']=='1'){
					$endPoint   = 'https://www.paypal.com/cgi-bin/webscr';
					$businessEmail = $appSettings['paypal_email'];
				}else{
					$endPoint   = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
					$businessEmail = $appSettings['paypal_sandbox_email'];
				}
				$quantity = DBin($_REQUEST['credit_quantity']);
				$redirectUrl = getServerUrl();
				$notifyUrl   = getServerUrl().'/credits_notify.php';
				$perCreditRate = $appSettings['per_credit_charges'];
				echo DBout("Redirecting to paypal..."); ?>
				<form action="<?php echo DBout($endPoint)?>" id="one_time_payment_from" name="one_time_payment_from" method="post">
							<input type="hidden" name="business" value="<?php echo DBout($businessEmail)?>" />
							<input type="hidden" name="return" value="<?php echo DBout($redirectUrl)?>" />
							<input type="hidden" name="cancel_return" value="<?php echo DBout($redirectUrl)?>" />
							<input type="hidden" name="notify_url" value="<?php echo DBout($notifyUrl)?>" />
							<input type="hidden" name="cmd" value="_xclick" />
							<input type="hidden" name="no_note" value="1" />
							<input type="hidden" name="no_shipping" value="1">
							<input type="hidden" value="USD" name="currency_code">
							<input type="hidden" name="country" value="USA" />
							<input type="hidden" name="item_name" value="<?php echo DBout($quantity)?> SMS Credits" />
							<input type="hidden" name="amount" value="<?php echo DBout(round($perCreditRate,2))?>" />
							<input type="hidden" name="custom" value="<?php echo DBout($quantity.'_'.$_SESSION['user_id'])?>" />
							<input name="quantity" id="credits_value" type="hidden" value="<?php echo DBout($quantity)?>">
						</form>
				<script>document.forms["one_time_payment_from"].submit();</script>
	<?php
			}
		}
			break;

		case "save_webform_subscriber":{
			$campaignID = $_REQUEST['campaign_id'];
			$email		= $_REQUEST['email'];
			$name		= $_REQUEST['name'];
			$phone		= $_REQUEST['phone'];
			$userID		= $_REQUEST['user_id'];
			$customSubsInfo = $_REQUEST['customSubsInfo'];
			
			$sql = sprintf("select id from subscribers where phone_number='%s' and user_id='%s'",
						mysqli_real_escape_string($link,$phone),
						mysqli_real_escape_string($link,DBin($userID))
					);
			$exe = mysqli_query($link,$sql);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("select keyword, phone_number from campaigns where id='%s'",
						mysqli_real_escape_string($link,$campaignID)
					);
				$res = mysqli_query($link,$sql);
				if(mysqli_num_rows($res)){
					$row = mysqli_fetch_assoc($res);
					$url		= getServerUrl().'/sms_controlling.php';
					$dataArray	= array(
						"To" => $row['phone_number'],
						"to" => $row['phone_number'],

						"From" => $phone,
						"msisdn" => trim($phone,"+"),

						"text" => $row['keyword'],
						"Text" => $row['keyword'],
						"Body" => $row['keyword'],


						"subscriber_type" => 'webform',
						"subs_email" => $email,
						"name" => $name,
						'customSubsInfo' => $customSubsInfo
					);
				   postData($url,$dataArray);
				}
				echo DBout('success');
			}else{
				$rec   = mysqli_fetch_assoc($exe);
				$subID = $rec['id'];
				
				$sqlc = sprintf("select id,status from subscribers_group_assignment where subscriber_id='%s' and group_id='%s' and user_id='%s'",
						mysqli_real_escape_string($link,$subID),
						mysqli_real_escape_string($link,DBin($campaignID)),
						mysqli_real_escape_string($link,DBin($userID))
					);
				$resc = mysqli_query($link,$sqlc);
				if(mysqli_num_rows($resc)==0){
					$sql = sprintf("insert into subscribers_group_assignment
							(
								group_id,
								subscriber_id,
								user_id
							)
						values
							(
								'%s',
								'%s',
								'%s'
							)",
						mysqli_real_escape_string($link,$campaignID),
						mysqli_real_escape_string($link,DBin($subID)),
						mysqli_real_escape_string($link,DBin($userID))
					);
					mysqli_query($link,$sql);
					echo DBout('success');
				}else{
					echo DBout('exists');
				}
			}
		}
		break;

		case "generate_embed_code":{
			$webFormID = DBin($_REQUEST['wbf_id']);
			$sql = sprintf("select showing_method from webforms where id='%s'",
								mysqli_real_escape_string($link,DBin($webFormID))
				);
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){
				$row = mysqli_fetch_assoc($res);
				if($row['showing_method']=='1'){
					$url   = getServerUrl().'/getwbf.php?wbfid='.encode($webFormID).'&wbtype='.encode($row['showing_method']); ?>
					<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
					<script type="text/javascript" src="<?php echo DBout($url)?>"></script>
					<img src="<?php echo DBout(getServerUrl())?>/images/subscribe.png" id="mynm_id" class="pointer"/>
		<?php
		}else{
					$url   = getServerUrl().'/getwbf.php?wbfid='.encode($webFormID).'&wbtype='.encode($row['showing_method']); ?>
					<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
					<script type="text/javascript" src="<?php echo DBout($url)?>"></script>
					<div id="nmModalData"></div>
		  <?php      }
			}
		}
			break;

		case "delete_webform":{
			$sql = sprintf("delete from webforms where id='%s'",
							 mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Webform deleted successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while deleting webform';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_webform":{

			$newCustomFields = array();
			if(isset($_REQUEST['customFields']) && count($_REQUEST['customFields'])>0){
				foreach($_REQUEST['customFields'] as $customFields){
					$field = array();
					foreach($customFields as $key=>$value){
						$field[$key] = DBin($value);
					}
					$newCustomFields[] = $field;
				}
			}

			$sql = sprintf("update webforms set
						webform_name='%s',
						campaign_id='%s',
						label_for_name_field='%s',
						label_for_phone_field='%s',
						label_for_email_field='%s',
						disclaimer_text='%s',
						field_width='%s',
						field_height='%s',
						color_for_label='%s',
						frame_width='%s',
						frame_height='%s',
						frame_bg_color='%s',
						subs_btn_bg_color='%s',
						close_btn_bg_color='%s',
						webform_type='%s',
						custom_fields='%s',
						label_for_disclaimer_text='%s',
						heading_for_custom_info_panel='%s',
						showing_method='%s'
					where
						id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['webform_name'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['label_for_name_field'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['label_for_phone_field'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['label_for_email_field'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['disclaimer_text'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['field_width'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['field_height'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['color_for_label'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['frame_width'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['frame_height'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['frame_bg_color'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['subs_btn_bg_color'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['close_btn_bg_color'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['webform_type'])),
						mysqli_real_escape_string($link,DBin(json_encode($newCustomFields,JSON_UNESCAPED_UNICODE))),
						mysqli_real_escape_string($link,DBin($_REQUEST['label_for_disclaimer_text'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['heading_for_custom_info_panel'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['showing_method'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['wbID']))
				);
			$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Webform has been updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while updating webform';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "add_new_webform":{
			$sql = sprintf("insert into webforms(
								webform_name,
								campaign_id,
								label_for_name_field,
								label_for_phone_field,
								label_for_email_field,
								disclaimer_text,
								field_width,
								field_height,
								color_for_label,
								frame_width,
								frame_height,
								frame_bg_color,
								user_id,
								subs_btn_bg_color,
								close_btn_bg_color,
								webform_type,
								custom_fields,
								label_for_disclaimer_text,
								heading_for_custom_info_panel,
								showing_method
							)values(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s'
								)",
									mysqli_real_escape_string($link,DBin($_REQUEST['webform_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['label_for_name_field'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['label_for_phone_field'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['label_for_email_field'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['disclaimer_text'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['field_width'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['field_height'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['color_for_label'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['frame_width'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['frame_height'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['frame_bg_color'])),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['subs_btn_bg_color'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['close_btn_bg_color'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['webform_type'])),
									mysqli_real_escape_string($link,(json_encode($_REQUEST['customFields']))),
									mysqli_real_escape_string($link,DBin($_REQUEST['label_for_disclaimer_text'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['heading_for_custom_info_panel'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['showing_method']))
							);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Webform is saved';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while saving webform';
			}
			header("location: view_webform.php");
		}
			break;

		case "delete_app_user":{
			$userID = decode(DBin($_REQUEST['id']));
			$res = mysqli_query($link,sprintf("delete from users where id='%s'",mysqli_real_escape_string($link,DBin($userID))));
			if($res){
				mysqli_query($link,sprintf("delete from campaigns where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from webforms where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from schedulers where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from subscribers where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from subscribers_group_assignment where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from sms_history where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from user_package_assignment where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				mysqli_query($link,sprintf("delete from payment_history where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
				$appSettings = getAppSettings($userID);
				if((trim($appSettings['twilio_sid'])!='')&&(trim($appSettings['twilio_token'])!='')){
					$client = getTwilioConnection($userID);
					$sql = sprintf("select phone_sid from users_phone_numbers where user_id='%s'",
						mysqli_real_escape_string($link,DBin($userID))
						);
					$exe = mysqli_query($link,$sql);
					if(mysqli_num_rows($exe)){
						while($row = mysqli_fetch_assoc($exe)){
							$phoneSid = $row['phone_sid'];
							if(trim($phoneSid)!=''){
								try{
									$client->account->incoming_phone_numbers->delete($phoneSid);
								}
								catch(Services_Twilio_RestException $e){
								}
								mysqli_query($link,sprintf("delete from users_phone_numbers where phone_sid='%s'",mysqli_real_escape_string($link,DBin($phoneSid))));
							}
						}
					}
					try{
						$account = $client->account;
						$account->update(array(
							'Status' => "suspended"
						));
					}catch(Services_Twilio_RestException $e){
						echo DBout($e->getMessage());
					}
				}
				mysqli_query($link,sprintf("delete from application_settings where user_id='%s'",mysqli_real_escape_string($link,DBin($phoneSid))));
			}
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'User has been deleted';
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_app_user_by_admin":{
			$startDate = date('Y-m-d',strtotime(DBin($_REQUEST['start_date'])));
			$endDate   = date('Y-m-d',strtotime(DBin($_REQUEST['end_date'])));
			if($startDate>$endDate){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = "Please select valid package date.";
				header("location: ".$_SERVER['HTTP_REFERER']);
			}
			$startDate = $startDate.' '.date('H').':00:00';
			$endDate   = $endDate.' '.date('H').':00:00';
			$password = $_REQUEST['password'];
			$rePassword = $_REQUEST['retype_password'];
			if(isset($_REQUEST['subs_lookup']))
				$subslookUp = DBin($_REQUEST['subs_lookup']);
			else
				$subslookUp = '0';

			if($password==$rePassword){
				$check = sprintf("select id from users where email='%s' and id!='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
					);
				$resep = mysqli_query($link,$check);
				if(mysqli_num_rows($resep)==0){
					$sql = sprintf("update users set
									first_name='%s',
									last_name='%s',
									email='%s',
									password='%s',
									business_name='%s'
								where
									id='%s'",
									mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,password_hash($password,PASSWORD_DEFAULT)),
									mysqli_real_escape_string($link,DBin($_REQUEST['business_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
					$res = mysqli_query($link,$sql)or die(mysqli_error($link));
					$userID = DBin($_REQUEST['user_id']);
					$sql1 = sprintf("update application_settings set time_zone='%s', subs_lookup='%s' where user_id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['time_zone'])),
										mysqli_real_escape_string($link,DBin($subslookUp)),
										mysqli_real_escape_string($link,DBin($userID))
						);
					mysqli_query($link,$sql1);
					$sel = sprintf("select id from user_package_assignment where user_id='%s' and pkg_id='%s'",
									mysqli_real_escape_string($link,DBin($userID)),
									mysqli_real_escape_string($link,DBin($_REQUEST['pkg_id']))
								 );
					$exe = mysqli_query($link,$sel);
					if(mysqli_num_rows($exe)==0){
						mysqli_query($link,sprintf("delete from user_package_assignment where user_id='%s'",mysqli_real_escape_string($link,DBin($userID))));
						$pkgInfo= getPackageInfo(DBin($_REQUEST['pkg_id']));
						$insPkg = sprintf("insert into user_package_assignment
													(user_id,pkg_id,start_date,end_date,sms_credits,phone_number_limit,pkg_country,iso_country)
													values ('%s','%s','%s','%s','%s','%s','%s','%s')",
													mysqli_real_escape_string($link,DBin($userID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['pkg_id'])),
													mysqli_real_escape_string($link,DBin($startDate)),
													mysqli_real_escape_string($link,DBin($endDate)),
													mysqli_real_escape_string($link,DBin($pkgInfo['sms_credits'])),
													mysqli_real_escape_string($link,DBin($pkgInfo['phone_number_limit'])),
													mysqli_real_escape_string($link,DBin($pkgInfo['country'])),
													mysqli_real_escape_string($link,DBin($pkgInfo['iso_country']))
							);
						mysqli_query($link,$insPkg);
					}else{
						$up = sprintf("update user_package_assignment set                               
												start_date='%s',
												end_date='%s'
											where
												user_id='%s' and
												pkg_id='%s'",
										mysqli_real_escape_string($link,DBin($startDate)),
										mysqli_real_escape_string($link,DBin($endDate)),
										mysqli_real_escape_string($link,DBin($userID)),
										mysqli_real_escape_string($link,DBin($_REQUEST['pkg_id']))
							);
						$exe = mysqli_query($link,$up);
					}
					if($res){
						$_SESSION['message_status'] = 1;
						$_SESSION['message'] = 'Success! User updated successfully';
					}else{
						$_SESSION['message_status'] = 1;
						$_SESSION['message'] = 'No changes were made to update';
					}
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'An account is already exists with same email, try another';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Re-Password is not matching with your password';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "add_app_user_by_stripe":{
			$AppSettings = getAppSettings("",true);
			if((trim($AppSettings['stripe_secret_key'])=='') || (trim($AppSettings['stripe_publishable_key'])=='')){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Stripe payment gateway is not configured by the application admin.';
				header("location: pricing_plans.php");
			}
			else{
				$pkgInfo = getPackageInfo(DBin($_REQUEST['pkg_id']));
				$check = sprintf("select id from users where email='%s'",
					mysqli_real_escape_string($link,DBin($_REQUEST['email']))
				);
				$resep = mysqli_query($link,$check);
				if(mysqli_num_rows($resep)==0){
					if(isset($_REQUEST['stripeToken']) && $_REQUEST['stripeToken']!=""){
						$password   = DBin($_REQUEST['password']);
						$rePassword = DBin($_REQUEST['retype_password']);
						if(trim($pkgInfo['free_days'])!=''){
							$freeDays = (int) $pkgInfo['free_days'];
						}else{
							$freeDays = 0;
						}
						if($password==$rePassword){
							require_once('stripe-php/init.php');
							\Stripe\Stripe::setApiKey($AppSettings['stripe_secret_key']);
							$planID = $pkgInfo['id'].'_'.uniqid();
							$ammount= (int)($pkgInfo['price']*100);

							$product = \Stripe\Product::create([
								'name' => $pkgInfo['title'],
								'type' => 'service',
							]);
							$plan = \Stripe\Plan::create(array(
								"interval" => "month",
								"currency" => "usd",
								"amount" => $ammount,
								'product' => ['name' => $product->name]
							));


							$customer = \Stripe\Customer::create(array(
								'description'=>'Nimble Messaging application user',
								"email" => $_REQUEST['email']
							));
							function postCurlStripe( $url, $cardDAta, $sk ) {
								;
								$ch = curl_init();
								curl_setopt( $ch, CURLOPT_URL, $url );
								curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
								curl_setopt( $ch, CURLOPT_POST, 1 );
								curl_setopt( $ch, CURLOPT_POSTFIELDS, $cardDAta );
								curl_setopt( $ch, CURLOPT_USERPWD, $sk . ':' . '' );
								$headers   = array();
								$headers[] = 'Content-Type: application/x-www-form-urlencoded';
								curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
								$result = curl_exec( $ch );
								curl_close( $ch );

								return $result;
							}
							$url="https://api.stripe.com/v1/customers/".$customer->id."/sources";
							$data = "source=".$_REQUEST['stripeToken'];
							$source = postCurlStripe($url,$data,$AppSettings['stripe_secret_key']);
							$source = json_decode($source);
							$subscriptiondata = "customer=" . $customer->id . "&items[0][plan]=".$plan->id."&default_payment_method=".$source->id;
							$subscriptionUrl = "https://api.stripe.com/v1/subscriptions";
							$subscription = postCurlStripe($subscriptionUrl, $subscriptiondata, $AppSettings['stripe_secret_key']);
							$subscription = json_decode($subscription);
							$customerID = $customer->id;
							$subscriptionID = $subscription->id;
							$subscriptionData = json_encode($subscription);
							try{
								$adminInfo = getAdminInfo();
								$adminID   = $adminInfo['id'];
								if($pkgInfo['sms_gateway']=='twilio'){
									$client = getTwilioConnection($adminID);
									$account= $client->accounts->create(array(
										"FriendlyName" => DBin($_REQUEST['email']
										)));
									$subAccountSid = $account->sid;
									$subAccountToken = $account->auth_token;
								}
								$encryptedPassword = password_hash($password,PASSWORD_DEFAULT);
								$sql = sprintf("insert into users
													(
														first_name,
														last_name,
														email,
														password,
														business_name,
														tcap_ctia,
														msg_and_data_rate,
														type,
														customerID,
														subscriptionID,
														subscriptionData,
														parent_user_id
													)
												values
													(
														'%s',
														'%s',
														'%s',
														'%s',
														'%s',
														'1',
														'1',
														'2',
														'%s',
														'%s',
														'%s',
														'%s'
													)",

									mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,$encryptedPassword),
									mysqli_real_escape_string($link,DBin($_REQUEST['business_name'])),
									mysqli_real_escape_string($link,DBin($customerID)),
									mysqli_real_escape_string($link,DBin($subscriptionID)),
									mysqli_real_escape_string($link,DBin($subscriptionData)),
									mysqli_real_escape_string($link,DBin($adminID))
								);
								$res = mysqli_query($link,$sql);
								if($res){
									$newUserID = mysqli_insert_id($link);
									$today  = date('Y-m-d H').':00:00';
									$endDate= date('Y-m-d H:i',strtotime('+1 month'.$today));
									$ins = sprintf("insert into user_package_assignment
															(
																user_id,
																pkg_id,
																start_date,
																end_date,
																sms_credits,
																phone_number_limit,
																pkg_country,
																iso_country
															)
														values
															(
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s'
															)",

										mysqli_real_escape_string($link,DBin($newUserID)),
										mysqli_real_escape_string($link,DBin($pkgInfo['id'])),
										mysqli_real_escape_string($link,DBin($today)),
										mysqli_real_escape_string($link,DBin($endDate)),
										mysqli_real_escape_string($link,DBin($pkgInfo['sms_credits'])),
										mysqli_real_escape_string($link,DBin($pkgInfo['phone_number_limit'])),
										mysqli_real_escape_string($link,DBin($pkgInfo['country'])),
										mysqli_real_escape_string($link,DBin($pkgInfo['iso_country']))
									);
									mysqli_query($link,$ins);
									$appSetts = sprintf("insert into application_settings
																(
																	twilio_sid,
																	twilio_token,
																	user_id,
																	user_type,
																	time_zone
																)
															values
																(
																	'%s',
																	'%s',
																	'%s',
																	'2',
																	'%s'
																)",
										mysqli_real_escape_string($link,DBin($subAccountSid)),
										mysqli_real_escape_string($link,DBin($subAccountToken)),
										mysqli_real_escape_string($link,DBin($newUserID)),
										mysqli_real_escape_string($link,DBin($_REQUEST['time_zone']))
									);
									mysqli_query($link,$appSetts);
								}else{
									$message = '<div class="alert alert-danger">Error! an error occured while creating new user.</div>';
								}

								$appUrl      = getServerUrl();
								$subject = DBout($AppSettings['email_subject']);
								$to      = DBin($_REQUEST['email']);
								$from    = 'admin@'.$_SERVER['SERVER_NAME'];
								$msg     = $AppSettings['new_app_user_email'];
								$msg     = str_replace('%first_name%',DBin($_REQUEST['first_name']),$msg);
								$msg     = str_replace('%last_name%',DBin($_REQUEST['last_name']),$msg);
								$msg     = str_replace('%login_email%',DBin($_REQUEST['email']),$msg);
								$msg     = str_replace('%login_pass%',$password,$msg);
								$msg     = str_replace('%login_url%',$appUrl,$msg);
								$FullName= 'Admin';
								sendEmail($subject,$to,$from,$msg,$FullName);

								$subject = $AppSettings['email_subject_for_admin_notification'];
								$to      = $AppSettings['admin_email'];
								$from    = 'admin@'.str_replace('www.','',$_SERVER['SERVER_NAME']);
								$msg     = str_replace('%email%',DBin($_REQUEST['email']),$AppSettings['new_app_user_email_for_admin']);
								$FullName= 'Admin';
								sendEmail($subject,$to,$from,$msg,$FullName);
								$sql1 = sprintf("insert into payment_history(payer_email,payer_status,txn_id,payment_status,gross_payment,product_name,user_id,payment_processor)
											values('%s','1','%s','Completed','%s','%s','%s','3')",
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,DBin($subscriptionID)),
									mysqli_real_escape_string($link,DBin($pkgInfo['price'])),
									mysqli_real_escape_string($link,DBin($pkgInfo['title'])),
									mysqli_real_escape_string($link,DBin($newUserID))
								);
								mysqli_query($link,$sql1) or die(mysqli_error($link));

								$_SESSION['message_status'] = 1;
								$_SESSION['message'] = 'Your Account has been created successfully.';
								header("location: index.php");
							}catch(Services_Twilio_RestException $e){
								$message = $e->getMessage();
							}
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Re-Password is not matching with your password';
						}
						header("location: pricing_plans.php");
					}
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'An account is already exists with same email, try another';
				}
			}
			header("location: index.php");

		}
			break;

		case "add_app_user_by_admin":{
			date_default_timezone_set($_REQUEST['time_zone']);
			$pkgInfo = getPackageInfo($_REQUEST['pkg_id']);
			if(isset($_REQUEST['parent_user_id']) && $_REQUEST['parent_user_id']!=""){
				$user_id = DBin($_REQUEST['parent_user_id']);
			}else{
				$user_id = DBin($_SESSION['user_id']);
			}
			$password   = DBin($_REQUEST['password']);
			$rePassword = DBin($_REQUEST['retype_password']);
			if($password==$rePassword){
				$check = sprintf("select id from users where email='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['email']))
					);
				$resep = mysqli_query($link,$check);
				if(mysqli_num_rows($resep)==0){
					try{
						$subAccountSid   = '';
						$subAccountToken = '';

						if($pkgInfo['sms_gateway']=='twilio'){
							$twilio = getTwilioInfo($_SESSION['user_id']);
							$twilio_sid = $twilio['twilio_sid'];
							$twilio_token = $twilio['twilio_token'];

							$data = array("FriendlyName" => DBin($_REQUEST['email']));
							$url  = "https://$twilio_sid:$twilio_token@api.twilio.com/2010-04-01/Accounts/";
							$account = sendTwilioCurl($data,$url,"POST");
							$subAccountSid = (string)$account->Account->Sid;;
							$subAccountToken = (string)$account->Account->AuthToken;
						}

						$encryptedPassword = password_hash($password,PASSWORD_DEFAULT);
						$sql = sprintf("insert into users
									(first_name,last_name,email,password,parent_user_id,business_name,tcap_ctia,msg_and_data_rate,type)
									values
											(
												'%s',
												'%s',
												'%s',
												'%s',
												'%s',
												'%s',
												'1',
												'1',
												'2'
											)",
												mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
												mysqli_real_escape_string($link,$encryptedPassword),
												mysqli_real_escape_string($link,DBin($user_id)),
												mysqli_real_escape_string($link,DBin($_REQUEST['business_name']))
										 );
						$res = mysqli_query($link,$sql);
						if($res){
							$newUserID = mysqli_insert_id($link);
							if(isset($_REQUEST['response']) && $_REQUEST['response']!=""){
								$selss = sprintf("update users set 
														response = '%s', 
														response_code = '%s',
														subscription_id = '%s'
														where id='%s'",
													mysqli_real_escape_string($link,DBin($_REQUEST['response'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['response_code'])),
													mysqli_real_escape_string($link,DBin($_REQUEST['subscription_id'])),
													mysqli_real_escape_string($link,DBin($newUserID))
												  );
								mysqli_query($link,$selss);
							}

							$today  = date('Y-m-d H').':00:00';
							$endDate= date('Y-m-d H:i',strtotime('+1 month'.$today));

							$ins = sprintf("insert into user_package_assignment
												(
													user_id,
													pkg_id,
													start_date,
													end_date,
													sms_credits,
													phone_number_limit,
													pkg_country,
													iso_country,
													sms_gateway
												)
											values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
														mysqli_real_escape_string($link,DBin($newUserID)),
														mysqli_real_escape_string($link,DBin($pkgInfo['id'])),
														mysqli_real_escape_string($link,DBin($today)),
														mysqli_real_escape_string($link,DBin($endDate)),
														mysqli_real_escape_string($link,DBin($pkgInfo['sms_credits'])),
														mysqli_real_escape_string($link,DBin($pkgInfo['phone_number_limit'])),
														mysqli_real_escape_string($link,DBin($pkgInfo['country'])),
														mysqli_real_escape_string($link,DBin($pkgInfo['iso_country'])),
														mysqli_real_escape_string($link,DBin($pkgInfo['sms_gateway']))

								);
							mysqli_query($link,$ins);
							$sqls = sprintf("insert into application_settings 
											(
												twilio_sid,
												twilio_token,
												sms_gateway,
												user_id,
												app_logo,
												app_date_format,
												user_type,
												sidebar_color,
												time_zone,
												device_id
											)
										values
											(
												'%s',
												'%s',
												'%s',
												'%s',
												'nimble_messaging.png',
												'm-d-Y',
												'2',
												'purple',
												'%s',
												'0'
											)",
												mysqli_real_escape_string($link,DBin($subAccountSid)),
												mysqli_real_escape_string($link,DBin($subAccountToken)),
												mysqli_real_escape_string($link,DBin($pkgInfo['sms_gateway'])),
												mysqli_real_escape_string($link,DBin($newUserID)),
												mysqli_real_escape_string($link,DBin($_REQUEST['time_zone']))
								);
							mysqli_query($link,$sqls) or die(mysqli_error($link));
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Error! an error occured while creating new user';
						}

						$appSettings = getAppSettings($user_id);
						$appUrl      = getServerUrl();
						$subject = DBout($appSettings['email_subject']);
						$to      = DBin($_REQUEST['email']);
						$from    = 'admin@'.$_SERVER['SERVER_NAME'];
						$msg     = $appSettings['new_app_user_email'];
						$msg     = str_replace('%first_name%',DBin($_REQUEST['first_name']),$msg);
						$msg     = str_replace('%last_name%',DBin($_REQUEST['last_name']),$msg);
						$msg     = str_replace('%login_email%',DBin($_REQUEST['email']),$msg);
						$msg     = str_replace('%login_pass%',$password,$msg);
						$msg     = str_replace('%login_url%',$appUrl,$msg);
						$FullName= 'Admin';
						sendEmail($subject,$to,$from,$msg,$FullName);

						$appSettings = getAppSettings($userID,true);
						$subject = $appSettings['email_subject_for_admin_notification'];
						$to      = $appSettings['admin_email'];
						$from    = 'admin@'.$_SERVER['SERVER_NAME'];
						$msg     = str_replace('%email%',DBin($_REQUEST['email']),$appSettings['new_app_user_email_for_admin']);
						$FullName= 'Admin';
						sendEmail($subject,$to,$from,$msg,$FullName);

						$_SESSION['message_status'] =1;
						$_SESSION['message'] = 'New application user has been created successfully';
					}catch(Services_Twilio_RestException $e){
						echo DBout($e->getMessage());
					}
				}else{
					$_SESSION['message_status'] =0;
					$_SESSION['message'] = 'An account is already exists with same email, try another.';
				}
			}else{
				$_SESSION['message_status'] =0;
				$_SESSION['message'] = 'Re-Password is not matching with your password';
			}
			header("location: index.php");
		}
			break;

		case "add_web_user":{
			$webUserID = "";
			$pkgPrice = DBin($_REQUEST['pkg_price']);
			$password = DBin($_REQUEST['password']);
			$uid = DBin($_REQUEST['uid']);
			$rePassword = DBin($_REQUEST['retype_password']);

			$adminSettings = getAppSettings("",true);
			if($adminSettings['paypal_switch']=='1'){
				$endPoint   = 'https://www.paypal.com/cgi-bin/webscr';
				$businessEmail = $adminSettings['paypal_email'];
			}else{
				$endPoint   = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				$businessEmail = $adminSettings['paypal_sandbox_email'];
			}
			if(trim($businessEmail)==''){
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! Paypal is not configured by the application admin.';
				header("location: pricing_plans.php");
				die("Paypal is not configured by application admin.");
			}else{
				if($password==$rePassword){
					$check = sprintf("select id from users where email='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['email']))
						);
					$resep = mysqli_query($link,$check);
					if(mysqli_num_rows($resep)==0){
						$sql = sprintf("insert into web_user_info
												 (
												 pkg_id,
												 first_name,
												 last_name,
												 email,
												 password,
												 parent_user_id,
												 business_name,
												 tcap_ctia,
												 msg_and_data_rate,
												 city,
												 state,
												 zip,
												 address
												 )
												 values
														(
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s',
																'%s'
														)",
																 mysqli_real_escape_string($link,DBin($_REQUEST['pkg_id'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
																mysqli_real_escape_string($link,DBin($password)),
																mysqli_real_escape_string($link,DBin($_REQUEST['parent_user_id'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['business_name'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['tcap_ctia'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['msg_and_data_rate'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['city'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['state'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['zip'])),
																mysqli_real_escape_string($link,DBin($_REQUEST['address']))
										  );
						$res = mysqli_query($link,$sql);
						if($res){
							$webUserID = mysqli_insert_id($link);
							$pkgInfo = getPackageInfo(DBin($_REQUEST['pkg_id']));
							redirectToPaypal($_REQUEST['parent_user_id'],$_REQUEST['pkg_title'],$pkgPrice,$webUserID,$pkgInfo);
						}else{
							$_SESSION['message_status'] = 0;
							$message = 'Error occured while saving your profile information! please try again.';
							$_SESSION['message'] = $message;
							header("location: ".$_SERVER['HTTP_REFERER']);
						}
					}else{
						$_SESSION['message_status'] = 0;
						$message = 'An account is already exists with same email, try another.';
						$_SESSION['message'] = $message;
						header("location: ".$_SERVER['HTTP_REFERER']);
					}
				}else{
					$_SESSION['message_status'] = 0;
					$message = 'Re-Password is not matching with your password.';
					$_SESSION['message'] = $message;
					header("location: ".$_SERVER['HTTP_REFERER']);
				}
			}
		}
			break;

		case "delete_plan":{
			$sql = sprintf("delete from package_plans where id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Pricing plan deleted successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error occured while deleting plan';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_plan":{
			if($_REQUEST['is_free_days']=='')
				$_REQUEST['is_free_days'] = '0';

			$sql = sprintf("update package_plans set
								title='%s',
								sms_credits='%s',
								phone_number_limit='%s',
								currency='%s',
								price='%s',
								country='%s',
								iso_country='%s',
								is_free_days='%s',
								free_days='%s',
								sms_gateway='%s'
							where
								id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['sms_credits'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['phone_number_limit'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['currency'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['price'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['pkg_country'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['country'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['is_free_days'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['free_days'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['sms_gateway'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['pkg_id']))

						);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Pricing plan has been updated';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while updating pricing pla';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "save_pkg":{
			if($_REQUEST['is_free_days']=='')
				$isFreeDays = '0';
			else
				$isFreeDays = DBin($_REQUEST['is_free_days']);

			$_REQUEST['free_days'] = ($_REQUEST['free_days']=="") ? 0 : $_REQUEST['free_days'];

			$AppSettings = getAppSettings($_SESSION['user_id']);
			$sql = sprintf("insert into package_plans                   
									(
										title,
										sms_credits,
										phone_number_limit,
										currency,
										price,
										user_id,
										iso_country,
										country,
										is_free_days,
										free_days,
										pkg_id,
										sms_gateway
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s'
									)",
										 mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['sms_credits'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['phone_number_limit'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['currency'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['price'])),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['country'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['pkg_country'])),
										mysqli_real_escape_string($link,DBin($isFreeDays)),
										mysqli_real_escape_string($link,DBin($_REQUEST['free_days'])),
										mysqli_real_escape_string($link,DBin($pkgID)),
										mysqli_real_escape_string($link,DBin($_REQUEST['sms_gateway']))
							);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Package plan has been saved';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! occured while saving package plan';
			}
			header("location: view_package.php");
		}
			break;

		case "update_profile":{
			if($_REQUEST['password'] == $_REQUEST['retype_password']){
				$sql = sprintf("update users set
									first_name='%s',
									last_name='%s',
									email='%s',
									password='%s',
									business_name='%s',
									phone_number='%s'
								where
									id='%s'",
									mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['last_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,password_hash($_REQUEST['password'],PASSWORD_DEFAULT)),
									mysqli_real_escape_string($link,DBin($_REQUEST['business_name'])),
							   		mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
				$res = mysqli_query($link,$sql) or die(mysqli_error($link));
				if($res){
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Successfully updated';
				}else{
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'You made no changes to update.';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Password does not match with retype password';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "buy_number":{
			$numbers = trim(DBin($_REQUEST['numbers']));
			$country = DBin($_REQUEST['country']);
			$ISOcountry = DBin($_REQUEST['ISOcountry']);
			$numbers = explode(',',$numbers);
			if((is_array($numbers)) || (trim($numbers[0])!='')){
				$smsURL     = getServerURL().'/sms_controlling.php';
				$callURL    = getServerURL().'/call_controlling.php';
				$client     = getTwilioConnection($_SESSION['user_id']);

				$twilio = getTwilioInfo($_SESSION['user_id']);
				$twilio_sid = $twilio['twilio_sid'];
				$twilio_token = $twilio['twilio_token'];

				$totalNum = 0;
				if($client==false){ ?>
					<span class="red">Not connected to twilio.</span>
		   <?php     }else{
					for($i=0;$i<count($numbers);$i++){
						$phoneNumer = $numbers[$i];
						if($_SESSION['user_type']=='1'){
							$totalNumbers = 0;
							$userPkgInfo['phone_number_limit'] = 5000;
						}else{
							$totalNumbers = checkUserNumberslimit($_SESSION['user_id']);
							$userPkgInfo  = getAssingnedPackageInfo($_SESSION['user_id']);
						}
						if($totalNumbers<$userPkgInfo['phone_number_limit']){
							$sqln = sprintf("select id from users_phone_numbers where phone_number='%s'",
										mysqli_real_escape_string($link,DBin($phoneNumer))
								);
							$resn = mysqli_query($link,$sqln);
							if(mysqli_num_rows($resn)==0){

								$data = array("PhoneNumber"=>trim($phoneNumer), "VoiceUrl" => $callURL, "SmsUrl" => $smsURL);

								$url  = "https://$twilio_sid:$twilio_token@api.twilio.com/2010-04-01/Accounts/$twilio_sid/IncomingPhoneNumbers";
								$twilioNumber = sendTwilioCurl($data,$url,"POST");

								if(isset($twilioNumber->RestException->Code))
								{
									$_SESSION['message_status'] = 0;
									$_SESSION['message'] = $twilioNumber->RestException->Message;
								}else{
									$twilio_number      = (string)$twilioNumber->IncomingPhoneNumber->PhoneNumber;
									$twilio_number_sid  = (string)$twilioNumber->IncomingPhoneNumber->Sid;

									$totalNum++;
									$ins = sprintf("insert into users_phone_numbers
												(
												friendly_name,
												phone_number,
												phone_sid,
												user_id,
												iso_country,
												country
												)values
												(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
												)",
												mysqli_real_escape_string($link,DBin($phoneNumer)),
												mysqli_real_escape_string($link,DBin($phoneNumer)),
												mysqli_real_escape_string($link,DBin($twilio_number_sid)),
												mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
												mysqli_real_escape_string($link,DBin($ISOcountry)),
												mysqli_real_escape_string($link,DBin($country))
										);
									mysqli_query($link,$ins);
									$_SESSION['message_status'] = 1;
									$_SESSION['message'] = 'Successfully purchased '.$totalNum.' number(s)';
								}
							}
						}else{
							$_SESSION['message_status'] = 0;
							$_SESSION['message'] = 'Your purchased numbers limit is exceeded, Currently purchased '.$totalNum.' number(s).';
						}
					}
				}
			}
		}
			break;

		case "remove_from_install":{
			$phoneSid   = DBin($_REQUEST['phoneSid']);
			$phone      = DBin($_REQUEST['number']);
			$client     = getTwilioConnection($_SESSION['user_id']);
			if($client==false){ ?>
				<span class="red">Not connected to twilio.</span>
	<?php
			}else{
				$number     = $client->account->incoming_phone_numbers->get($phoneSid);
				$number->update(array(
					"VoiceUrl" => '',
					"SmsUrl" => ''
				));
				$sql = sprintf("delete from users_phone_numbers where phone_number='%s'",
							mysqli_real_escape_string($link,DBin($phone))
					);
				mysqli_query($link,$sql);
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Phone number successfully removed from this install';
				echo DBout('1');
			}
		}
			break;

		case "assign_to_install":{
			$phoneNumber= DBin($_REQUEST['phone_number']);
			$phoneSid   = DBin($_REQUEST['phoneSid']);
			$country    = DBin($_REQUEST['country']);
			$isoCountry = DBin($_REQUEST['isoCountry']);
			$smsURL     = getServerURL().'/sms_controlling.php';
			$callURL    = getServerURL().'/call_controlling.php';
			$client     = getTwilioConnection($_SESSION['user_id']);

			$twilio = getTwilioInfo($_SESSION['user_id']);
			$twilio_sid = $twilio['twilio_sid'];
			$twilio_token = $twilio['twilio_token'];

			$data = array("VoiceUrl" => $callURL, "SmsUrl" => $smsURL);

			$url  = "https://$twilio_sid:$twilio_token@api.twilio.com/2010-04-01/Accounts/$twilio_sid/IncomingPhoneNumbers/$phoneSid";
			$numbers = sendTwilioCurl($data,$url,"PUT");

			$sel = sprintf("select id from users_phone_numbers where phone_number='%s'",
							mysqli_real_escape_string($link,DBin($phoneNumber))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("insert into users_phone_numbers
								(
								friendly_name,
								phone_number,
								phone_sid,
								user_id,
								iso_country,country
								)values
								(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s'
								)",
								mysqli_real_escape_string($link,DBin($phoneNumber)),
								mysqli_real_escape_string($link,DBin($phoneNumber)),
								mysqli_real_escape_string($link,DBin($phoneSid)),
								mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
								mysqli_real_escape_string($link,DBin($isoCountry)),
								mysqli_real_escape_string($link,DBin($country))

					);
				$res = mysqli_query($link,$sql) or die(mysqli_error($link));
			}
			$_SESSION['message_status'] = 1;
			$_SESSION['message'] = 'Success! Phone number has been successfully assigned to this install';
			echo DBout('1');

		}
			break;

		case "get_numbers":{
			$state   = DBin($_REQUEST['state']);
			$areacode= DBin($_REQUEST['areacode']);
			$country = DBin($_REQUEST['country']);
			$client = getTwilioConnection($_SESSION['user_id'],true);
			$numbers = searchTwilioNumbers($client,$country,$state,"Local",$areacode,"",$_SESSION['user_id']);
			if($client==false){ ?>
				<span class="red">Not connected to twilio.</span>
	<?php
			}else{ ?>
				<table class="table table-striped table-bordered table-hover" id="dataTables-example2" width="90%">
				<thead>
						<tr>
							<th></th>
							<th>Friendly Name</th>
							<th>Phone Number</th>
							<th>Capabilities</th>
						</tr>
				</thead>
					<tbody>
								<?php

				if(empty($numbers->AvailablePhoneNumber)){
					?>
					<tr><td colspan="4">No number found.</td></tr>
				<?php
																  }else{
					foreach($numbers->AvailablePhoneNumber as $number){
						?>
						<tr>
							<td align="center" class="text-center">
								<input type="checkbox" name="buy_num" class="buy_num" value="<?php echo DBout($number->PhoneNumber)?>">
							</td>
							<td align="center" class="text-center"><?php echo DBout($number->FriendlyName)?></td>
							<td align="center" class="text-center"><?php echo DBout($number->PhoneNumber)?></td>
							<td align="center" class="text-center">
								<?php if($number->Capabilities->Voice == 1 || $number->Capabilities->Voice==true) { ?>
								   Voice:<span class="green display-inline"><img src="images/tick.gif"/> </span>
							  <?php } else{ ?>
									Voice:<span class="display-inline green"><img src="images/cross.png"/></span>
							<?php } if($number->Capabilities->SMS == 1 || $number->Capabilities->SMS == true) { ?>
									SMS:<span class="display-inline green"><img src="images/tick.gif"> </span>
							<?php    }else{ ?>
									SMS:<span class="display-inline green"><img src="images/cross.png"> </span>
								<?php } if($number->Capabilities->MMS == 1 || $number->Capabilities->MMS == true) { ?>
								   MMS:<span class="display-inline green"><img src="images/tick.gif"> </span>

							<?php    } else { ?>
									MMS:<span class="display-inline green"><img src="images/cross.png"> </span>
							<?php   } ?>
							</td>
						</tr>
						<?php
					}?>

					</tbody></table>
					<input type="button" value="Buy Number(s)" class="btn btn-primary" onclick="buyNumber();">
					<?php
				}
			}
		}
			break;

		case "get_area_codes":{
			$stateCode = DBin($_REQUEST['state_code']);
			$sql = sprintf("select * from area_codes where state_code='%s'",
						mysqli_real_escape_string($link,DBin($stateCode))
				);
			$res = mysqli_query($link,$sql);
			if(count($res)>0){ ?>
				<option value="">- Select One -</option>
				<?php
				foreach($res as $row){ ?>
					<option value="<?php echo DBout($row['code_number']) ?>"><?php echo DBout($row['code_number'])?></option>
		  <?php      }
			}
		}
			break;

		case "get_existing_numbers":{
			if($_SESSION['user_type']=='1'){
				$client = getTwilioConnection($_SESSION['user_id']);
				if($client==false){ ?>
					<span class="red">Not connected to twilio.</span>
					<?php
				}else{
					$twilio = getTwilioInfo($_SESSION['user_id']);
					$twilio_sid = $twilio['twilio_sid'];
					$twilio_token = $twilio['twilio_token'];

					$url  = "https://$twilio_sid:$twilio_token@api.twilio.com/2010-04-01/Accounts/$twilio_sid/IncomingPhoneNumbers";
					$numbers = sendTwilioCurl("",$url);

					$settings = getAppSettings($_SESSION['user_id']);
					$sr = 1; ?>
					<table width="100%" align="center" class="table table-striped table-bordered table-hover">
					<tr>
					<td>Sr#</td>
					<td>Phone Number</td>
					<td>Current Install</td>
					<td>Country</td>
					<td>Capabilities</td>
					<td>Manage</td>
					</tr>
						<?php
					try{
						foreach($numbers->IncomingPhoneNumbers->IncomingPhoneNumber as $number){
							$lookUp = numberLookUp($settings['twilio_sid'],$settings['twilio_token'],$number->PhoneNumber);
							?>

							<tr>
							<td><?php echo  DBout($sr++)?></td>
							<td><?php echo  DBout($number->PhoneNumber)?></td>
							<td>
								<?php
							$array = parse_url($number->VoiceUrl);
							$directory = explode('/',trim($array['path'],'/'));
							if(trim($array['host'])!="")
								echo DBout($array['host'].'/'.$directory[0]);
							else
								echo DBout('Not assigned yet.');
							?>
							</td>
							<td align="center">
								<?php
							$countries = countries();
							$country   = 'N/A';
							$ISOcountry= $lookUp['country_code'];
							foreach($countries as $key => $value){
								if($key==$lookUp['country_code']){
									echo DBout($countries[$key]);
									$country = $countries[$key];
									break;
								}
							} ?>
							</td>
							<td>
								<?php
							if($number->Capabilities->Voice=='1' || $number->Capabilities->Voice==true){ ?>
							   Voice <img src="images/tick.gif">
						<?php    } else{ ?>
							   Voice <img src="images/cross.png">
					   <?php     } if($number->Capabilities->SMS=='1' || $number->Capabilities->SMS==true){ ?>
								SMS <img src="images/tick.gif">
					   <?php     } else{ ?>
								SMS <img src="images/cross.png">
					   <?php     } if($number->Capabilities->MMS=='1' || $number->Capabilities->MMS==true){ ?>
								MMS <img src="images/tick.gif">
					  <?php      } else{ ?>
								MMS <img src="images/cross.png">
					  <?php      } ?>
							</td>
							<td align="center">
								<?php
							if($_SESSION['user_type']=='1'){ ?>
								<img src="images/add-number.png" title="Add Number" class="add_number_style" onclick="addToInstall('<?php echo DBout($number->Sid)?>','<?php echo DBout($number->PhoneNumber)?>','<?php echo DBout($country)?>','<?php echo DBout($ISOcountry)?>')">
						 <?php   } ?>
							<img src="images/cross.png" width="20" class="pointer" itle="Release Number" onclick="removeFromInstall('<?php echo DBout($number->Sid)?>','<?php echo DBout($number->PhoneNumber)?>')">
							</td>
							</tr>
							<?php
						}

					}catch(Services_Twilio_RestException $e){
						echo DBout('Authentication error! Twilio sid and token is incorrect.');
					} ?>
					</table>
					<?php
				}
			}else if($_SESSION['user_type']=='2'){
				$sr = 1;
				?>
				<table width="100%" align="center" class="table table-striped table-bordered table-hover">';
				<tr>
				<td>Sr#</td>
				<td>Phone Number</td>
				</tr>
					<?php
				$sql = sprintf("select * from users_phone_numbers where user_id='%s'",
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
				$res = mysqli_query($link,$sql);
				if(mysqli_num_rows($res)){
					while($row=mysqli_fetch_assoc($res)){ ?>
						<tr>
					   <td><?php echo DBout($sr++)?></td>
						<td><?php echo DBout($row['phone_number'])?></td>
						</tr>
				<?php    }
				}else{ ?>
					<tr><td colspan="3">No phone number found.</td></tr>
		  <?php      } ?>
			   </table>
				<?php
			}
		}
			break;

		case "get_numbers_areacode":{
			$state   = DBin($_REQUEST['state']);
			$areacode= DBin($_REQUEST['areacode']);
			$country = DBin($_REQUEST['country']);
			$client = getTwilioConnection($_SESSION['user_id']);
			$numbers = searchTwilioNumbers($client,$country,$state,"Local",$areacode,"",$_SESSION['user_id']);
			if($client==false){ ?>
				<span class="red">Not connected to twilio.</span>
				<?php
			}else{ ?>
				<table class="table table-striped table-bordered table-hover" id="dataTables-example2" width="90%">
				<thead>
									<tr>
										<th></th>
										<th>Friendly Name</th>
										<th>Phone Number</th>
										<th>Capabilities</th>
									</tr>
								</thead>
								<tbody>
				<?php
				foreach($numbers->AvailablePhoneNumber as $number){
					//echo '<pre>';
					//print_r($number);
					?>
					<tr>
						<td align="center" class="text-center">
							<input type="checkbox" name="buy_num" class="buy_num" value="<?php echo DBout($number->PhoneNumber)?>">
						</td>
						<td align="center" class="text-center"><?php echo DBout($number->FriendlyName)?></td>
						<td align="center" class="text-center"><?php echo DBout($number->PhoneNumber)?></td>
						<td align="center" class="text-center">
							<?php
							if($number->Capabilities->Voice == true){ ?>
								Voice:<span class="green display-inline"><img src="images/tick.gif"> </span>
						   <?php }else{ ?>
								Voice:<span class="green display-inline"><img src="images/cross.png"></span>
						  <?php } if($number->Capabilities->SMS == true){ ?>
								SMS:<span class="green display-inline"><img src="images/tick.gif"> </span>
						  <?php  } else{ ?>
								SMS:<span class="green display-inline"><img src="images/cross.png"> </span>
									<?php }
							if($number->Capabilities->MMS == true){ ?>
								MMS:<span class="green display-inline"><img src="images/tick.gif"> </span>
						 <?php  } else { ?>
								MMS:<span class="green display-inline"><img src="images/cross.png"> </span>
								<?php } ?>
						</td>
					</tr>
					<?php
				}?>

								</tbody></table>
				<input type="button" value="Buy Number(s)" class="btn btn-primary" onclick="buyNumber();">
	<?php
			}
		}
			break;

		case "delete_subscriber":{
			$id = $_REQUEST['id'];
			//$up = sprintf("update subscribers set status='3' where id='%s'",mysqli_real_escape_string($link,$id));
			$up = "delete from subscribers where id='".$id."'";
			$res = mysqli_query($link,$up);
			if($res){
				$sql = sprintf("delete from subscribers_group_assignment where subscriber_id='%s'",
						mysqli_real_escape_string($link,$id));
				$sql = sprintf("delete from schedulers where phone_number='%s'",
						mysqli_real_escape_string($link,$_REQUEST['id'])
					);
				mysqli_query($link,"delete from chat_history where phone_id='".$_REQUEST['id']."'");
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Subscriber deleted successfully';
			}else{
				$_SESSION['message_status']=0;
				$_SESSION['message'] = 'Error! an error occured while deleting subscriber';
			}

			header("location: ".$_SERVER['HTTP_REFERER']);
		}
		break;
		case "update_subscriber":{
			$subsID = DBin($_REQUEST['subscriber_id']);
			$sql = sprintf("update subscribers set
								first_name='%s',
								email='%s',
								phone_number='%s',
								city='%s',
								state='%s'
							where
								id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['city'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['state'])),
										mysqli_real_escape_string($link,DBin($subsID))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$up = sprintf("update subscribers_group_assignment set 
									group_id='%s'
								where
									id='%s'",

								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['assignment_id']))
					);
				mysqli_query($link,$up);
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Subscriber updated successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! Error occured while updating subscriber';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "add_subscriber":{
			$sel = sprintf("select id from subscribers where phone_number='%s' and user_id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
							mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("insert into subscribers
								(
									first_name,
									phone_number,
									email,
									city,
									state,
									user_id,
									subs_type
								)values
								(
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'campaign'
								)",
									mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['city'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['state'])),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
					);
				$res = mysqli_query($link,$sql);
				if($res){
					$subsID = mysqli_insert_id($link);
					$sql1 = sprintf("insert into subscribers_group_assignment
									(group_id,subscriber_id,user_id)values
									('%s','%s','%s')",
									mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
									mysqli_real_escape_string($link,DBin($subsID)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
					mysqli_query($link,$sql1);
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Error occured while adding subscriber';
				}
			}else{
				$row = mysqli_fetch_assoc($exe);
				$sel = sprintf("select id from subscribers_group_assignment where group_id='%s' and subscriber_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($row['id']))
					);
				$res = mysqli_query($link,$sel);
				if(mysqli_num_rows($res)==0){
					$sql2 = sprintf("insert into subscribers_group_assignment
									(group_id,subscriber_id,user_id)values
									('%s','%s','%s')",
									mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
									mysqli_real_escape_string($link,DBin($row['id'])),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
					mysqli_query($link,$sql2);
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}else{
					$sql3 = sprintf("update subscribers_group_assignment set status='1' where group_id='%s' and subscriber_id='%s'",
									mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
									mysqli_real_escape_string($link,DBin($row['id']))
						);
					mysqli_query($link,$sql3);
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}
			}
			header("location: view_subscribers.php");
		}
			break;

		case "pagebuilder_subscriber":{

			$data = DBin($_REQUEST['data']);
			parse_str($data, DBin($_REQUEST));

			$sel = sprintf("select id from subscribers where phone_number='%s' and user_id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
				);
			$exe = mysqli_query($link,$sel);
			if(mysqli_num_rows($exe)==0){
				$sql = sprintf("insert into subscribers
								(first_name,phone_number,email,birthday,anniversary,user_id,subs_type)values
								(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'page_builder'
								)",
								mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['birthday'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['anniversary'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
					);
				$res = mysqli_query($link,$sql);
				if($res){
					$subsID = mysqli_insert_id($link);
					$sql4 = sprintf("insert into subscribers_group_assignment
									(group_id,subscriber_id,user_id)values
									('%s','%s','%s')",
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($subsID)),
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
					mysqli_query($link,$sql4);
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Error occured while adding subscriber';
				}
			}else{
				$row = mysqli_fetch_assoc($exe);

				mysqli_query($link,sprintf("update subscribers set first_name='%s', email='%s', birthday='%s', anniversary='%s' where id = '%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['first_name'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['email'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['birthday'])),
						mysqli_real_escape_string($link,DBin($_REQUEST['anniversary'])),
						mysqli_real_escape_string($link,DBin($row['id']))
					));


				$sel = sprintf("select id from subscribers_group_assignment where group_id='%s' and subscriber_id='%s'",
							mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
							mysqli_real_escape_string($link,DBin($row['id']))
					);
				$res = mysqli_query($link,$sel);
				if(mysqli_num_rows($res)==0){
					$sql3 = sprintf("insert into subscribers_group_assignment
									(group_id,subscriber_id,user_id)values
									('%s','%s','%s')",
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($row['id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
					mysqli_query($link,$sql3);
					$_SESSION['message_status'] =1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}else{
					mysqli_query($link,sprintf("update subscribers_group_assignment set status='1' where group_id='%s' and subscriber_id='%s'",
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($row['id']))
						));
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Subscriber added successfully';
				}
			}
			echo DBout(1);

		}
			break;

		case "delete_scheduler":{
			$sel = sprintf("select media from schedulers where id='%s' and media!=''",
						mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$exe = mysqli_query($link,$sel);
			$m   = mysqli_fetch_assoc($exe);
			$media = $m['media'];

			$sql = sprintf("delete from schedulers where id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				if(trim($media)!=''){
					removeMedia($media);
				}
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Scheduler is deleted.';
			}
			else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! While deleting scheduler';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}

		case "update_scheduler":{
			if($_FILES['media']['name']!=''){
				$ext = getExtension($_FILES['media']['name']);
				$extns = array('jpg','jpeg','png','bmp','gif');
				if(!in_array($ext,$extns)){
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Select a valid file type';
					header("location:".$_SERVER['HTTP_REFERER']);
				}else{
					$fileName = uniqid().'_'.$_FILES['media']['name'];
					$tmpName  = $_FILES['media']['tmp_name'];
					move_uploaded_file($tmpName,'uploads/'.$fileName);
					$fileName = getServerUrl().'/uploads/'.$fileName;
					removeMedia(DBin($_REQUEST['hidden_media']));
				}
			}else{
				$fileName = DBin($_REQUEST['hidden_media']);
			}

			if(is_array($_REQUEST['phone_number'])){
				$_REQUEST['phone_number'] = implode(",",DBin($_REQUEST['phone_number']));
			}
			if($_REQUEST['group_id']==""){
				$_REQUEST['group_id'] = 0;
			}

			if(!isset($_REQUEST['search'])){
				$_REQUEST['search']="";
			}
			if(!isset($_REQUEST['custom'])){
				$_REQUEST['custom']="0";
			}


			$time = date('Y-m-d',strtotime(DBin($_REQUEST['date'])));
			$time = $time.' '.DBin($_REQUEST['time']);
			if($_REQUEST['attach_mobile_device']!='1')
				$_REQUEST['attach_mobile_device'] = '0';

			$sql = sprintf("update schedulers set
								title='%s',
								scheduled_time='%s',
								group_id='%s',
								phone_number='%s',
								message='%s',
								media='%s',
								attach_mobile_device='%s'
							where
								id='%s'",
				mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
								mysqli_real_escape_string($link,DBin($time)),
								mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['message'])),
								mysqli_real_escape_string($link,DBin($fileName)),
								mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
								mysqli_real_escape_string($link,DBin($_REQUEST['scheduler_id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Your message has been scheduled successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! an error occured while scheduling message';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "save_scheduler":{
			if($_FILES['media']['name']!=''){
				$ext = getExtension($_FILES['media']['name']);
				$extns = array('jpg','jpeg','png','bmp','gif');
				if(!in_array($ext,$extns)){
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! Select a valid file type';
					header("location:".$_SERVER['HTTP_REFERER']);
				}else{
					$fileName = uniqid().'_'.$_FILES['media']['name'];
					$tmpName  = $_FILES['media']['tmp_name'];
					move_uploaded_file($tmpName,'uploads/'.$fileName);
					$fileName = getServerUrl().'/uploads/'.$fileName;
				}
			}

			if(is_array($_REQUEST['phone_number'])){
				$_REQUEST['phone_number'] = implode(",",DBin($_REQUEST['phone_number']));
			}
			if($_REQUEST['group_id']==""){
				$_REQUEST['group_id'] = 0;
			}

			if(!isset($_REQUEST['search'])){
				$_REQUEST['search']="";
			}
			if(!isset($_REQUEST['custom'])){
				$_REQUEST['custom']="0";
			}
			
			if($_REQUEST['send_immediate']!='1'){
				$_REQUEST['send_immediate']="0";
			}

			$time = date('Y-m-d',strtotime(DBin($_REQUEST['date'])));
			$time = $time.' '.DBin($_REQUEST['time']);
			
			
			if($_REQUEST['attach_mobile_device']!='1')
				$_REQUEST['attach_mobile_device'] = '0';
			$sql = sprintf("insert into schedulers
								(
									title,
									scheduled_time,
									group_id,
									phone_number,
									message,
									media,
									user_id,
									scheduler_type,
									attach_mobile_device,
									send_immediate,
									custom,
									search
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'1',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
									 mysqli_real_escape_string($link,DBin($_REQUEST['title'])),
									mysqli_real_escape_string($link,DBin($time)),
									mysqli_real_escape_string($link,DBin($_REQUEST['group_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['phone_number'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['message'])),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['send_immediate'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['search']))
				);
			$res = mysqli_query($link,$sql)or die(mysqli_error($link));
			if($res){
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Your message has been scheduled successfully';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! an error occured while scheduling message';
			}
			if($_REQUEST['send_immediate'] == '1') {
				$url = getServerUrl() . '/cron.php';
				post_curl_mqs($url, array());
			}
			header("location: view_scheduler2.php");
		}
			break;

		case "get_scheduler_numbers":{
			$groupID = DBin($_REQUEST['group_id']);
			$numberID= DBin($_REQUEST['numberID']);
			$sql = sprintf("select s.id, s.phone_number from subscribers s, subscribers_group_assignment sga where 
									sga.group_id='%s' and sga.subscriber_id=s.id",
					mysqli_real_escape_string($link,DBin($groupID))
				);
			$exe = mysqli_query($link,$sql);
			$res=array();
			while($row = mysqli_fetch_array($exe)){
				$res[]=array($row['id'],$row['phone_number']);
			}
			$res1[]=array('','All Numbers');
			$result = array_merge($res1, $res);
			echo DBout(json_encode($result));
		}
			break;

		case "get_group_numbers":{
			$groupID = DBin($_REQUEST['group_id']);
			$numberID= DBin($_REQUEST['numberID']);
			$client_id = DBin($_REQUEST['client_id']);

			$pos = strpos($numberID, ",");
			if($pos!==false){
				$sql = sprintf("select s.id, s.phone_number,first_name,last_name,email from subscribers s, 
								subscribers_group_assignment sga where s.id in (%s) and sga.subscriber_id=s.id and s.status='1' group by 
									s.phone_number",
							mysqli_real_escape_string($link,DBin($numberID))
					);
			}else{
				if($groupID=="all"){
					if($client_id=="all"){
						$sql = sprintf("select s.id, s.phone_number,first_name,last_name,email from subscribers s, subscribers_group_assignment sga where sga.subscriber_id=s.id and s.status='1'");
					}else{
						$sql = sprintf("select s.id, s.phone_number,first_name,last_name,email from subscribers s, subscribers_group_assignment sga where s.user_id = '%s' and sga.subscriber_id=s.id and s.status='1'",
									mysqli_real_escape_string($link,DBin($client_id))
							);
					}
				}else{
					$sql = sprintf("select s.id, s.phone_number,first_name,last_name,email from subscribers s, subscribers_group_assignment sga where sga.group_id='%s' and sga.subscriber_id=s.id and s.status='1'",
								mysqli_real_escape_string($link,DBin($groupID))
						);
				}
			}
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){

				if($pos!==false){
					$selll = "selected";
				}
				else{
					if($numberID=="all"){
						$selll = "selected";
					} ?>
					<option value="">Select One</option>';
					<option value="all" <?php echo DBout($selll)?>>All Numbers</option>
		   <?php     }
				while($row = mysqli_fetch_assoc($res)){
					if($numberID==$row['id'])
						$selected = 'selected="selected"';
					else
						$selected = '';

					if($pos!==false){
						$selected = "selected='selected'";
					}

					if(trim($row['first_name'])!=''){
						$name = $row['first_name'].' '.$row['last_name'].", ";
					}else{
						$name = '';
					}
					if(trim($row['email'])!=''){
						$email = ", ".$row['email'];
					}else{
						$email = '';
					}
					$info = $name.' '.$row['phone_number'].$email;
					?>
					<option <?php echo DBout($selected)?> value="<?php echo DBout($row['id'])?>"><?php echo DBout($info)?></option>
			  <?php  }
			} else { ?>

	<option value="">No subscribers found.</option>
	<?php
			}
		}
			break;

		case "get_groups":{
			if($_REQUEST['user_id']=="all"){
				$sql = sprintf("select id, title from campaigns");
			}else{
				$sql = sprintf("select id, title from campaigns where user_id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
					);
			}

			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)){ ?>
				<option value="">Select One</option>
				<option value="all">All Groups</option>
	<?php while($row = mysqli_fetch_assoc($res)){ ?>
					<option value="<?php echo DBout($row['id'])?>"><?php echo DBout($row['title'])?></option>
			   <?php } }else{ ?>
				<option value="">No Groups Found</option>
	<?php
			}
		}
			break;

		case "get_twilio_numbers":{
			$AppSettings = getAppSettings($_SESSION['user_id']);
			if($_REQUEST['user_id']=="all"){
				if($AppSettings['sms_gateway']=='twilio'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='1'",
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
				}else if($AppSettings['sms_gateway']=='plivo'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='2'",
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
				}
				else if($AppSettings['sms_gateway']=='nexmo'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='3'",
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
						);
				}
			}else{
				if($AppSettings['sms_gateway']=='twilio'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='1'",
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
				}else if($AppSettings['sms_gateway']=='plivo'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='2'",
								mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
				}
				else if($AppSettings['sms_gateway']=='nexmo'){
					$seln = sprintf("select id, phone_number from users_phone_numbers where user_id='%s' and type='3'",
							mysqli_real_escape_string($link,DBin($_REQUEST['user_id']))
						);
				}
			}
			$resn = mysqli_query($link,$seln);
			if(mysqli_num_rows($resn)){
				while($rown = mysqli_fetch_assoc($resn)){ ?>
					<option value="<?php echo DBout($rown['phone_number']) ?>"><?php echo DBout($rown['phone_number'])?></option>
			   <?php
				}
			}else{ ?>
				<option value="">No phone number found.</option>
		<?php    }
		}
			break;

		case "delete_autores":{
			$sql = sprintf("delete from campaigns where id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				removeMedia(DBin($_REQUEST['media']));
				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Autoresponder is deleted';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! While deleting autoresponder';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_autores":{
			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
						removeMedia(DBin($_REQUEST['hidden_campaign_media']));
					}
				}else{
					$fileName = DBin($_REQUEST['hidden_campaign_media']);
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				if($_REQUEST['direct_subscription']!='1')
					$_REQUEST['direct_subscription'] = '0';

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				$sql = sprintf("update campaigns set
									title='%s',
									keyword='%s',
									phone_number='%s',
									type='2',
									welcome_sms='%s',
									already_member_msg='%s',
									media='%s',
									attach_mobile_device='%s',
									direct_subscription='%s',
									share_with_subaccounts='%s'
								where
									id='%s'",
												mysqli_real_escape_string($link,DBin($title)),
												mysqli_real_escape_string($link,DBin($keyword)),
												mysqli_real_escape_string($link,DBin($phoneNumber)),
												mysqli_real_escape_string($link,DBin($welcomeSms)),
												mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
												mysqli_real_escape_string($link,DBin($fileName)),
												mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['direct_subscription'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
												mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))

					);
				$res = mysqli_query($link,$sql) or die(mysqli_error($link));
				if($res){
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Autoresponder update successfully';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while updating autoresponder';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! '.DBout($_REQUEST['keyword']).' is already used or maybe reserve keyword, try another';
			}
			header('location: '.$_SERVER['HTTP_REFERER']);
		}
			break;

		case "create_autores":{
			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type.';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
					}
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				if($_REQUEST['direct_subscription']!='1')
					$_REQUEST['direct_subscription'] = '0';

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				$sql = sprintf("insert into campaigns
								(title,keyword,phone_number,type,welcome_sms,already_member_msg,media,user_id,attach_mobile_device,direct_subscription,share_with_subaccounts)values
								(
									'%s',
									'%s',
									'%s',
									'2',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['direct_subscription'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts']))
					);
				$res = mysqli_query($link,$sql)or die(mysqli_error($link));
				if($res){
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Autoresponder saved successfully';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while saving autoresponder';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! '.DBin($_REQUEST['keyword']).' is already used or maybe reserve keyword, try another';
			}
			header('location: view_autores.php');
		}
			break;

		case "delete_campaign":{
			$sel = sprintf("select media from campaigns where id='%s' and media!=''",
						mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sel);
			$m   = mysqli_fetch_assoc($res);
			$media = $m['media'];

			$sql = sprintf("delete from campaigns where id='%s'",
						mysqli_real_escape_string($link,DBin($_REQUEST['id']))
				);
			$res = mysqli_query($link,$sql);
			if($res){
				mysqli_query($link,sprintf("delete from subscribers_group_assignment where group_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));
				mysqli_query($link,sprintf("delete from follow_up_msgs where group_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));
				mysqli_query($link,sprintf("delete from schedulers where group_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));
				if(trim($media)!=''){
					removeMedia($media);
				}


				mysqli_query($link,sprintf("delete from trivia_questions where campaign_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));
				mysqli_query($link,sprintf("delete from trivia_answers where campaign_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));


				$_SESSION['message_status'] = 1;
				$_SESSION['message'] = 'Success! Campaign is deleted';
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! While deleting campaign';
			}
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "delete_page":{
			mysqli_query($link,sprintf("delete from pages where id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['id']))));
			$_SESSION['message_status'] = 0;
			$_SESSION['message'] = 'Success! Page deleted successfully';
			header("location: ".$_SERVER['HTTP_REFERER']);
		}
			break;

		case "update_campaign":{
			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
						removeMedia(DBin($_REQUEST['hidden_campaign_media']));
					}
				}else{
					$fileName = DBin($_REQUEST['hidden_campaign_media']);
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				$doubleOptin = DBin($_REQUEST['double_optin']);

				if(isset($_REQUEST['get_subs_email'])){
					$get_email = DBin($_REQUEST['get_subs_email']);
				}else{
					$get_email = '0';
				}

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				if($_REQUEST['double_optin_check']!='1')
					$_REQUEST['double_optin_check'] = '0';
				if($_REQUEST['get_subs_name_check']!='1')
					$_REQUEST['get_subs_name_check'] = '0';
				if($_REQUEST['campaign_expiry_check']!='1')
					$_REQUEST['campaign_expiry_check'] = '0';
				if($_REQUEST['followup_msg_check']!='1')
					$_REQUEST['followup_msg_check'] = '0';

				$reply_email = DBin($_REQUEST['reply_email']);
				$email_updated = DBin($_REQUEST['email_updated']);

				if($_REQUEST['campaign_beacon_check']!=1){
					$_REQUEST['campaign_beacon_check']=0;
				}

				$sql = sprintf("update campaigns set
									title='%s',
									keyword='%s',
									phone_number='%s',
									type='1',
									welcome_sms='%s',
									already_member_msg='%s',
									media='%s',
									double_optin='%s',
									get_email='%s',
									reply_email='%s',
									email_updated='%s',
									start_date='%s',
									end_date='%s',
									expire_message = '%s',
									attach_mobile_device='%s',
									device_id='%s',
									double_optin_check='%s',
									get_subs_name_check='%s',
									msg_to_get_subscriber_name='%s',
									name_received_confirmation_msg='%s',
									campaign_expiry_check='%s',
									double_optin_confirm_message='%s',
									followup_msg_check='%s',
									share_with_subaccounts='%s',

									campaign_beacon_check='%s',
									beacon='%s',
									beacon_url_type='%s',
									coupon='%s',
									custom_url='%s'

								where
									id='%s'",
									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($doubleOptin)),
									mysqli_real_escape_string($link,DBin($get_email)),
									mysqli_real_escape_string($link,DBin($reply_email)),
									mysqli_real_escape_string($link,DBin($email_updated)),
									mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['device_id'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom_url'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))

					);
				$res = mysqli_query($link,$sql);
				if($res){
					if($_REQUEST['campaign_beacon_check']=="1" && $_REQUEST['beacon']!=""){

						$sql_as = sprintf("select estimote_app_id,estimote_app_token from application_settings where user_id='%s'",
										mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_as = mysqli_query($link,$sql_as);
						$row_as = mysqli_fetch_assoc($res_as);
						$AppID = $row_as['estimote_app_id'];
						$AppToken = $row_as['estimote_app_token'];

						$identifier = DBin($_REQUEST['beacon']);

						if($_REQUEST['beacon_url_type']=="1"){

							$sql_pages = sprintf("select * from pages where id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon']))
								);
							$res_pages = mysqli_query($link,$sql_pages);
							$row_pages = mysqli_fetch_assoc($res_pages);
							$eddystone_url = $row_pages['short_url'];
						}else{
							$eddystone_url = DBin($_REQUEST['custom_url']);
						}

						$url = "https://$AppID:$AppToken@cloud.estimote.com/v2/devices/$identifier";
						$data = '{
								   "settings": {
									 "advertisers": {
									   "eddystone_url": [{
										 "index": 1,
										 "name": "Eddystone URL",
										 "enabled": true,
										 "interval": 300,
										 "power": "-4",
										 "url" : "'.$eddystone_url.'"        
									   }]
									 }
								   }
								}';

						$res = curl_process22($url,$data);

					}
					$campaignID = $_REQUEST['campaign_id'];
					$mediaCount = 0;
					$failedMediaCount = 0;
					$followUpCount = 0;
					mysqli_query($link,sprintf("delete from follow_up_msgs where group_id='%s'",mysqli_real_escape_string($link,DBin($campaignID))));
					for($i=0;$i<count($_REQUEST['delay_day']);$i++){
						if((trim($_REQUEST['delay_day'][$i])!='') && (trim($_REQUEST['delay_message'][$i])!='')){
							if($_FILES['delay_media']['name'][$i]!=''){
								$ext = getExtension($_FILES['delay_media']['name'][$i]);
								$extns = array('jpg','jpeg','png','bmp','gif');
								if(!in_array($ext,$extns)){
									$failedMediaCount++;
								}else{
									removeMedia(DBin($_REQUEST['hidden_delay_media'][$i]));
									$fileName = uniqid().'_'.$_FILES['delay_media']['name'][$i];
									$tmpName  = $_FILES['delay_media']['tmp_name'][$i];
									move_uploaded_file($tmpName,'uploads/'.$fileName);
									$fileName = getServerUrl().'/uploads/'.$fileName;
									$mediaCount++;
								}
							}else{
								$fileName = DBin($_REQUEST['hidden_delay_media'][$i]);
							}
							$sqlFollow = sprintf("insert into follow_up_msgs
											(
											group_id,delay_day,delay_time,message,media,user_id)values
											(
											'%s',
											'%s',
											'%s',
											'%s',
											'%s',
											'%s'
											)",
													mysqli_real_escape_string($link,DBin($campaignID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_day'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
								);
							$resFollow = mysqli_query($link,$sqlFollow);
							if($resFollow){
								$followUpCount++;
							}
						}
					}
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Campaign has been updated successfully with '.DBin($followUpCount).' follow up messages';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while updating campaign';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! '.DBout($_REQUEST['keyword']).' is already used or maybe reserve keyword, try another';
			}
			header('location: '.$_SERVER['HTTP_REFERER']);
		}
			break;


		case "add_trivia":{
			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
						removeMedia(DBin($_REQUEST['hidden_campaign_media']));
					}
				}else{
					$fileName = DBin($_REQUEST['hidden_campaign_media']);
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				$doubleOptin = DBin($_REQUEST['double_optin']);

				if(isset($_REQUEST['get_subs_email'])){
					$get_email = DBin($_REQUEST['get_subs_email']);
				}else{
					$get_email = '0';
				}

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				if($_REQUEST['double_optin_check']!='1')
					$_REQUEST['double_optin_check'] = '0';
				if($_REQUEST['get_subs_name_check']!='1')
					$_REQUEST['get_subs_name_check'] = '0';
				if($_REQUEST['campaign_expiry_check']!='1')
					$_REQUEST['campaign_expiry_check'] = '0';
				if($_REQUEST['followup_msg_check']!='1')
					$_REQUEST['followup_msg_check'] = '0';
				if($_REQUEST['campaign_beacon_check']!=1){
					$_REQUEST['campaign_beacon_check']=0;
				}

				$reply_email = DBin($_REQUEST['reply_email']);
				$email_updated = DBin($_REQUEST['email_updated']);


				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){

					$sql = sprintf("update campaigns set
								title='%s',
								keyword='%s',
								phone_number='%s',
								type='3',
								welcome_sms='%s',
								correct_sms='%s',
								wrong_sms='%s',
								complete_sms='%s',
								already_member_msg='%s',
								media='%s',
								double_optin='%s',
								get_email='%s',
								reply_email='%s',
								email_updated='%s',
								start_date='%s',
								end_date='%s',
								expire_message = '%s',
								attach_mobile_device='%s',
								double_optin_check='%s',
								get_subs_name_check='%s',
								msg_to_get_subscriber_name='%s',
								name_received_confirmation_msg='%s',
								campaign_expiry_check='%s',
								double_optin_confirm_message='%s',
								followup_msg_check='%s',
								share_with_subaccounts='%s',
								campaign_beacon_check='%s',
								beacon='%s',
								beacon_url_type='%s',
								coupon='%s',
								custom_url='%s'
							where
								id='%s'",
									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,DBin($_REQUEST['correct_sms'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['wrong_sms'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['complete_sms'])),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($doubleOptin)),
									mysqli_real_escape_string($link,DBin($get_email)),
									mysqli_real_escape_string($link,DBin($reply_email)),
									mysqli_real_escape_string($link,DBin($email_updated)),
									mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),

									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom_url'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))
						);

				}
				else{
					$sql = sprintf("insert into campaigns
								(
									title,
									keyword,
									phone_number,
									type,
									welcome_sms,
									correct_sms,
									wrong_sms,
									complete_sms,
									already_member_msg,
									media,
									user_id,
									double_optin,
									get_email,
									reply_email,
									email_updated,
									start_date,
									end_date,
									expire_message,
									attach_mobile_device,
									double_optin_check,
									get_subs_name_check,
									msg_to_get_subscriber_name,
									name_received_confirmation_msg,
									campaign_expiry_check,
									double_optin_confirm_message,
									followup_msg_check,
									share_with_subaccounts,
									campaign_beacon_check,
									beacon,
									beacon_url_type,
									coupon,
									custom_url
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'3',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
										mysqli_real_escape_string($link,DBin($title)),
										mysqli_real_escape_string($link,DBin($keyword)),
										mysqli_real_escape_string($link,DBin($phoneNumber)),
										mysqli_real_escape_string($link,DBin($welcomeSms)),
										mysqli_real_escape_string($link,DBin($_REQUEST['correct_sms'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['wrong_sms'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['complete_sms'])),
										mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
										mysqli_real_escape_string($link,DBin($fileName)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($doubleOptin)),
										mysqli_real_escape_string($link,DBin($get_email)),
										mysqli_real_escape_string($link,DBin($reply_email)),
										mysqli_real_escape_string($link,DBin($email_updated)),
										mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['custom_url']))
						);
				}
				$res = mysqli_query($link,$sql) or die(mysqli_error($link));

				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){
					$campaignID = DBin($_REQUEST['campaign_id']);

					mysqli_query($link,sprintf("delete from trivia_questions where campaign_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))));
					mysqli_query($link,sprintf("delete from trivia_answers where campaign_id='%s'",mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))));

				}else{
					$campaignID = mysqli_insert_id($link);
				}


				if(isset($_REQUEST['field']) && count($_REQUEST['field'])>0)
				{
					foreach($_REQUEST['field'] as $question){

						$sql_q = sprintf("insert into trivia_questions (question,user_id,campaign_id) 
										values (
										'%s',
										'%s',
										'%s'
										)",
										mysqli_real_escape_string($link,DBin($question['question'])),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($campaignID))
							);
						mysqli_query($link,$sql_q);
						$questionID = mysqli_insert_id($link);

						if(isset($question['answers']) && count($question['answers'])>0)
						{
							foreach($question['answers'] as $answer){

								if($answer['correct']!="1"){
									$answer['correct']=0;
								}

								$sql_n = sprintf("insert into trivia_answers (
												answer,value,correct,question_id,user_id,campaign_id)
												 values (
												 '%s',
												 '%s',
												 '%s',
												 '%s',
												 '%s',
												 '%s'
												 )",
										mysqli_real_escape_string($link,DBin($answer['answer'])),
										mysqli_real_escape_string($link,DBin($answer['value'])),
										mysqli_real_escape_string($link,DBin($answer['correct'])),
										mysqli_real_escape_string($link,DBin($questionID)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($campaignID))

									);
								mysqli_query($link,$sql_n);

							}
						}
					}
				}
				if($res){
					if($_REQUEST['campaign_beacon_check']=="1" && $_REQUEST['beacon']!=""){
						$sql_as = sprintf("select estimote_app_id,estimote_app_token from application_settings where 
											user_id='%s'",
										mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_as = mysqli_query($link,$sql_as);
						$row_as = mysqli_fetch_assoc($res_as);
						$AppID = $row_as['estimote_app_id'];
						$AppToken = $row_as['estimote_app_token'];
						$identifier = DBin($_REQUEST['beacon']);
						if($_REQUEST['beacon_url_type']=="1"){
							$sql_pages = sprintf("select * from pages where id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon']))
								);
							$res_pages = mysqli_query($link,$sql_pages);
							$row_pages = mysqli_fetch_assoc($res_pages);
							$eddystone_url = $row_pages['short_url'];
						}else{
							$eddystone_url = DBin($_REQUEST['custom_url']);
						}
						$url = "https://$AppID:$AppToken@cloud.estimote.com/v2/devices/$identifier";
						$data = '{
								   "settings": {
									 "advertisers": {
									   "eddystone_url": [{
										 "index": 1,
										 "name": "Eddystone URL",
										 "enabled": true,
										 "interval": 300,
										 "power": "-4",
										 "url" : "'.$eddystone_url.'"        
									   }]
									 }
								   }
								}';

						$res = curl_process22($url,$data);

					}
					$mediaCount = 0;
					$failedMediaCount = 0;
					$followUpCount = 0;
					$sql1 = sprintf("delete from follow_up_msgs where group_id='%s'",
							mysqli_real_escape_string($link,DBin($campaignID))
						);
					mysqli_query($link,$sql1);
					for($i=0;$i<count($_REQUEST['delay_day']);$i++){
						if((trim($_REQUEST['delay_day'][$i])!='') && (trim($_REQUEST['delay_message'][$i])!='')){
							if($_FILES['delay_media']['name'][$i]!=''){
								$ext = getExtension($_FILES['delay_media']['name'][$i]);
								$extns = array('jpg','jpeg','png','bmp','gif');
								if(!in_array($ext,$extns)){
									$failedMediaCount++;
								}else{
									removeMedia(DBin($_REQUEST['hidden_delay_media'][$i]));
									$fileName = uniqid().'_'.$_FILES['delay_media']['name'][$i];
									$tmpName  = $_FILES['delay_media']['tmp_name'][$i];
									move_uploaded_file($tmpName,'uploads/'.$fileName);
									$fileName = getServerUrl().'/uploads/'.$fileName;
									$mediaCount++;
								}
							}else{
								$fileName = DBin($_REQUEST['hidden_delay_media'][$i]);
							}
							$sqlFollow = sprintf("insert into follow_up_msgs
											(group_id,delay_day,delay_time,message,media,user_id)values
											(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
											)",
													mysqli_real_escape_string($link,DBin($campaignID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_day'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))

								);
							$resFollow = mysqli_query($link,$sqlFollow);
							if($resFollow){
								$followUpCount++;
							}
						}
					}
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Campaign has been saved successfully with '.DBin($followUpCount).' follow up messages';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while updating campaign';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! <b>'.DBout($_REQUEST['keyword']).'</b> is already used or maybe reserve keyword, try another';
			}
			header('location: trivias.php');




		}
			break;
		case "add_viral":{
			  $specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
						removeMedia(DBin($_REQUEST['hidden_campaign_media']));
					}
				}else{
					$fileName = DBin($_REQUEST['hidden_campaign_media']);
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				$doubleOptin = DBin($_REQUEST['double_optin']);

				if(isset($_REQUEST['get_subs_email'])){
					$get_email = DBin($_REQUEST['get_subs_email']);
				}else{
					$get_email = '0';
				}

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				if($_REQUEST['double_optin_check']!='1')
					$_REQUEST['double_optin_check'] = '0';
				if($_REQUEST['get_subs_name_check']!='1')
					$_REQUEST['get_subs_name_check'] = '0';
				if($_REQUEST['campaign_expiry_check']!='1')
					$_REQUEST['campaign_expiry_check'] = '0';
				if($_REQUEST['followup_msg_check']!='1')
					$_REQUEST['followup_msg_check'] = '0';
				if($_REQUEST['campaign_beacon_check']!=1){
					$_REQUEST['campaign_beacon_check']=0;
				}

				$reply_email = DBin($_REQUEST['reply_email']);
				$email_updated = DBin($_REQUEST['email_updated']);

				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){

					$sql = sprintf("update campaigns set
								title='%s',
								keyword='%s',
								phone_number='%s',
								type='4',
								welcome_sms='%s',

								code_message='%s',
								notification_msg='%s',
								winning_number='%s',
								winner_msg='%s',

								already_member_msg='%s',
								media='%s',
								double_optin='%s',
								get_email='%s',
								reply_email='%s',
								email_updated='%s',
								start_date='%s',
								end_date='%s',
								expire_message = '%s',
								attach_mobile_device='%s',
								double_optin_check='%s',
								get_subs_name_check='%s',
								msg_to_get_subscriber_name='%s',
								name_received_confirmation_msg='%s',
								campaign_expiry_check='%s',
								double_optin_confirm_message='%s',
								followup_msg_check='%s',
								share_with_subaccounts='%s',

								campaign_beacon_check='%s',
								beacon='%s',
								beacon_url_type='%s',
								coupon='%s',
								custom_url='%s'
							where
								id='%s'",
									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,$_REQUEST['code_message']),
									mysqli_real_escape_string($link,$_REQUEST['notification_msg']),
									mysqli_real_escape_string($link,DBin($_REQUEST['winning_number'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['winner_msg'])),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($doubleOptin)),
									mysqli_real_escape_string($link,DBin($get_email)),
									mysqli_real_escape_string($link,DBin($reply_email)),
									mysqli_real_escape_string($link,DBin($email_updated)),
									mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom_url'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))
						);
				}else{

					$sql = sprintf("insert into campaigns
								(
									title,
									keyword,
									phone_number,
									type,
									welcome_sms,

									code_message,
									notification_msg,
									winning_number,
									winner_msg,

									already_member_msg,
									media,
									user_id,
									double_optin,
									get_email,
									reply_email,
									email_updated,
									start_date,
									end_date,
									expire_message,
									attach_mobile_device,
									double_optin_check,
									get_subs_name_check,
									msg_to_get_subscriber_name,
									name_received_confirmation_msg,
									campaign_expiry_check,
									double_optin_confirm_message,
									followup_msg_check,
									share_with_subaccounts,

									campaign_beacon_check,
									beacon,
									beacon_url_type,
									coupon,
									custom_url
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'4',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",

									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,$_REQUEST['code_message']),
									mysqli_real_escape_string($link,$_REQUEST['notification_msg']),
									mysqli_real_escape_string($link,DBin($_REQUEST['winning_number'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['winner_msg'])),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($doubleOptin)),
									mysqli_real_escape_string($link,DBin($get_email)),
									mysqli_real_escape_string($link,DBin($reply_email)),
									mysqli_real_escape_string($link,DBin($email_updated)),
									mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom_url']))

						);

				}

				$res = mysqli_query($link,$sql) or die(mysqli_error($link));

				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){
					$campaignID = DBin($_REQUEST['campaign_id']);
				}else{
					$campaignID = mysqli_insert_id($link);
				}


				if($res){
					if($_REQUEST['campaign_beacon_check']=="1" && $_REQUEST['beacon']!=""){

						$sql_as = sprintf("select estimote_app_id,estimote_app_token from application_settings where 
										user_id='%s'",
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_as = mysqli_query($link,$sql_as);
						$row_as = mysqli_fetch_assoc($res_as);
						$AppID = $row_as['estimote_app_id'];
						$AppToken = $row_as['estimote_app_token'];

						$identifier = DBin($_REQUEST['beacon']);

						if($_REQUEST['beacon_url_type']=="1"){

							$sql_pages = sprintf("select * from pages where id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon']))
								);
							$res_pages = mysqli_query($link,$sql_pages);
							$row_pages = mysqli_fetch_assoc($res_pages);
							$eddystone_url = $row_pages['short_url'];
						}else{
							$eddystone_url = DBin($_REQUEST['custom_url']);
						}

						$url = "https://$AppID:$AppToken@cloud.estimote.com/v2/devices/$identifier";
						$data = '{
								   "settings": {
									 "advertisers": {
									   "eddystone_url": [{
										 "index": 1,
										 "name": "Eddystone URL",
										 "enabled": true,
										 "interval": 300,
										 "power": "-4",
										 "url" : "'.$eddystone_url.'"        
									   }]
									 }
								   }
								}';

						$res = curl_process22($url,$data);

					}
					$mediaCount = 0;
					$failedMediaCount = 0;
					$followUpCount = 0;
					mysqli_query($link,sprintf("delete from follow_up_msgs where group_id='%s'",mysqli_real_escape_string($link,DBin($campaignID))));
					for($i=0;$i<count($_REQUEST['delay_day']);$i++){
						if((trim($_REQUEST['delay_day'][$i])!='') && (trim($_REQUEST['delay_message'][$i])!='')){
							if($_FILES['delay_media']['name'][$i]!=''){
								$ext = getExtension($_FILES['delay_media']['name'][$i]);
								$extns = array('jpg','jpeg','png','bmp','gif');
								if(!in_array($ext,$extns)){
									$failedMediaCount++;
								}else{
									removeMedia(DBin($_REQUEST['hidden_delay_media'][$i]));
									$fileName = uniqid().'_'.$_FILES['delay_media']['name'][$i];
									$tmpName  = $_FILES['delay_media']['tmp_name'][$i];
									move_uploaded_file($tmpName,'uploads/'.$fileName);
									$fileName = getServerUrl().'/uploads/'.$fileName;
									$mediaCount++;
								}
							}else{
								$fileName = DBin($_REQUEST['hidden_delay_media'][$i]);
							}
							$sqlFollow = sprintf("insert into follow_up_msgs
											(group_id,delay_day,delay_time,message,media,user_id)values
											(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
											)",
													mysqli_real_escape_string($link,DBin($campaignID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_day'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))

								);
							$resFollow = mysqli_query($link,$sqlFollow);
							if($resFollow){
								$followUpCount++;
							}
						}
					}
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Campaign has been saved successfully with '.DBin($followUpCount).' follow up messages';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while updating campaign';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! '.DBout($_REQUEST['keyword']).' is already used or maybe reserve keyword, try another';
			}
			header('location: virals.php');




		}
			break;

		case "add_contest":{



			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
						removeMedia(DBin($_REQUEST['hidden_campaign_media']));
					}
				}else{
					$fileName = DBin($_REQUEST['hidden_campaign_media']);
				}
				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				$doubleOptin = DBin($_REQUEST['double_optin']);

				if(isset($_REQUEST['get_subs_email'])){
					$get_email = $_REQUEST['get_subs_email'];
				}else{
					$get_email = '0';
				}

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				if($_REQUEST['double_optin_check']!='1')
					$_REQUEST['double_optin_check'] = '0';
				if($_REQUEST['get_subs_name_check']!='1')
					$_REQUEST['get_subs_name_check'] = '0';
				if($_REQUEST['campaign_expiry_check']!='1')
					$_REQUEST['campaign_expiry_check'] = '0';
				if($_REQUEST['followup_msg_check']!='1')
					$_REQUEST['followup_msg_check'] = '0';
				if($_REQUEST['campaign_beacon_check']!=1){
					$_REQUEST['campaign_beacon_check']=0;
				}

				$reply_email = DBin($_REQUEST['reply_email']);
				$email_updated = DBin($_REQUEST['email_updated']);

				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){

					$sql = sprintf("update campaigns set
								title='%s',
								keyword='%s',
								phone_number='%s',
								type='0',
								winning_number='%s',
								winner_msg='%s',
								looser_msg='%s',
								already_member_msg='%s',
								media='%s',
								double_optin='%s',
								get_email='%s',
								reply_email='%s',
								email_updated='%s',
								start_date='%s',
								end_date='%s',
								expire_message = '%s',
								attach_mobile_device='%s',
								double_optin_check='%s',
								get_subs_name_check='%s',
								msg_to_get_subscriber_name='%s',
								name_received_confirmation_msg='%s',
								campaign_expiry_check='%s',
								double_optin_confirm_message='%s',
								followup_msg_check='%s',
								share_with_subaccounts='%s',
								campaign_beacon_check='%s',
								beacon='%s',
								beacon_url_type='%s',
								coupon='%s',
								custom_url='%s'
							where
								id='%s'",
										mysqli_real_escape_string($link,DBin($title)),
										mysqli_real_escape_string($link,DBin($keyword)),
										mysqli_real_escape_string($link,DBin($phoneNumber)),
										mysqli_real_escape_string($link,DBin($_REQUEST['winning_number'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['winner_msg'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['looser_msg'])),
										mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
										mysqli_real_escape_string($link,DBin($fileName)),
										mysqli_real_escape_string($link,DBin($doubleOptin)),
										mysqli_real_escape_string($link,DBin($get_email)),
										mysqli_real_escape_string($link,DBin($reply_email)),
										mysqli_real_escape_string($link,DBin($email_updated)),
										mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['custom_url'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_id']))

						);

				}else{

					$sql = sprintf("insert into campaigns
								(
									title,
									keyword,
									phone_number,
									type,
									welcome_sms,
									code_message,
									notification_msg,
									winning_number,
									winner_msg,
									looser_msg,
									already_member_msg,
									media,
									user_id,
									double_optin,
									get_email,
									reply_email,
									email_updated,
									start_date,
									end_date,
									expire_message,
									attach_mobile_device,
									double_optin_check,
									get_subs_name_check,
									msg_to_get_subscriber_name,
									name_received_confirmation_msg,
									campaign_expiry_check,
									double_optin_confirm_message,
									followup_msg_check,
									share_with_subaccounts,
									campaign_beacon_check,
									beacon,
									beacon_url_type,
									coupon,
									custom_url
								)
							values
								(
									'%s',
									'%s',
									'%s',
									'0',
									'%s',

									'%s',
									'%s',
									'%s',
									'%s',
									'%s',

									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',
									'%s',

									'%s',
									'%s',
									'%s',
									'%s',
									'%s'
								)",
									mysqli_real_escape_string($link,DBin($title)),
									mysqli_real_escape_string($link,DBin($keyword)),
									mysqli_real_escape_string($link,DBin($phoneNumber)),
									mysqli_real_escape_string($link,DBin($welcomeSms)),
									mysqli_real_escape_string($link,DBin($_REQUEST['code_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['notification_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['winning_number'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['winner_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['looser_msg'])),
									mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
									mysqli_real_escape_string($link,DBin($fileName)),
									mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
									mysqli_real_escape_string($link,DBin($doubleOptin)),
									mysqli_real_escape_string($link,DBin($get_email)),
									mysqli_real_escape_string($link,DBin($reply_email)),
									mysqli_real_escape_string($link,DBin($email_updated)),
									mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['beacon_url_type'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['coupon'])),
									mysqli_real_escape_string($link,DBin($_REQUEST['custom_url']))
						);

				}

				$res = mysqli_query($link,$sql);

				if(isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!=""){
					$campaignID = DBin($_REQUEST['campaign_id']);
				}else{
					$campaignID = mysqli_insert_id($link);
				}


				if($res){

					if($_REQUEST['campaign_beacon_check']=="1" && $_REQUEST['beacon']!=""){

						$sql_as = sprintf("select estimote_app_id,estimote_app_token from application_settings where 
						user_id='%s'",
								mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_as = mysqli_query($link,$sql_as);
						$row_as = mysqli_fetch_assoc($res_as);
						$AppID = $row_as['estimote_app_id'];
						$AppToken = $row_as['estimote_app_token'];

						$identifier = DBin($_REQUEST['beacon']);

						if($_REQUEST['beacon_url_type']=="1"){

							$sql_pages = sprintf("select * from pages where id=%s",mysqli_real_escape_string($link,DBin($_REQUEST['coupon']))
						);
							$res_pages = mysqli_query($link,$sql_pages);
							$row_pages = mysqli_fetch_assoc($res_pages);
							$eddystone_url = $row_pages['short_url'];
						}else{
							$eddystone_url = DBin($_REQUEST['custom_url']);
						}

						$url = "https://$AppID:$AppToken@cloud.estimote.com/v2/devices/$identifier";
						$data = '{
								   "settings": {
									 "advertisers": {
									   "eddystone_url": [{
										 "index": 1,
										 "name": "Eddystone URL",
										 "enabled": true,
										 "interval": 300,
										 "power": "-4",
										 "url" : "'.$eddystone_url.'"        
									   }]
									 }
								   }
								}';

						$res = curl_process22($url,$data);

					}
					$mediaCount = 0;
					$failedMediaCount = 0;
					$followUpCount = 0;
					mysqli_query($link,sprintf("delete from follow_up_msgs where group_id='%s'",mysqli_real_escape_string($link,DBin($campaignID))));
					for($i=0;$i<count($_REQUEST['delay_day']);$i++){
						if((trim($_REQUEST['delay_day'][$i])!='') && (trim($_REQUEST['delay_message'][$i])!='')){
							if($_FILES['delay_media']['name'][$i]!=''){
								$ext = getExtension($_FILES['delay_media']['name'][$i]);
								$extns = array('jpg','jpeg','png','bmp','gif');
								if(!in_array($ext,$extns)){
									$failedMediaCount++;
								}else{
									removeMedia(DBin($_REQUEST['hidden_delay_media'][$i]));
									$fileName = uniqid().'_'.$_FILES['delay_media']['name'][$i];
									$tmpName  = $_FILES['delay_media']['tmp_name'][$i];
									move_uploaded_file($tmpName,'uploads/'.$fileName);
									$fileName = getServerUrl().'/uploads/'.$fileName;
									$mediaCount++;
								}
							}else{
								$fileName = DBin($_REQUEST['hidden_delay_media'][$i]);
							}
							$sqlFollow = sprintf("insert into follow_up_msgs
											(
											group_id,delay_day,delay_time,message,media,user_id)values
											(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
											)",
													mysqli_real_escape_string($link,DBin($campaignID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_day'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
								);
							$resFollow = mysqli_query($link,$sqlFollow);
							if($resFollow){
								$followUpCount++;
							}
						}
					}
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Campaign has been saved successfully with '.DBin($followUpCount).' follow up messages.';
				}else{
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Error! while updating campaign';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! <b>'.DBout($_REQUEST['keyword']).'</b> is already used or maybe reserve keyword, try another';
			}
			header('location: contest.php');
		}
		break;

		case "create_campaign":{
			$specialChars = specialCharacters();
			$keyword = str_replace($specialChars,'',DBin($_REQUEST['keyword']));
			if(checkKeyword($_SESSION['user_id'],$keyword,$_REQUEST['campaign_id'])){
				if($_FILES['campaign_media']['name']!=''){
					$ext = getExtension($_FILES['campaign_media']['name']);
					$extns = array('jpg','jpeg','png','bmp','gif');
					if(!in_array($ext,$extns)){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Error! Select a valid file type.';
						header("location:".$_SERVER['HTTP_REFERER']);
					}else{
						$fileName = uniqid().'_'.$_FILES['campaign_media']['name'];
						$tmpName  = $_FILES['campaign_media']['tmp_name'];
						move_uploaded_file($tmpName,'uploads/'.$fileName);
						$fileName = getServerUrl().'/uploads/'.$fileName;
					}
				}

				$title = DBin($_REQUEST['title']);
				$phoneNumber = DBin($_REQUEST['phone_number']);
				$welcomeSms  = DBin($_REQUEST['welcome_sms']);
				$alreadyMemberSms = DBin($_REQUEST['already_member_sms']);
				$doubleOptin = DBin($_REQUEST['double_optin']);

				if(isset($_REQUEST['get_subs_email'])){
					$get_email = $_REQUEST['get_subs_email'];
				}else{
					$get_email = '0';
				}

				if($_REQUEST['attach_mobile_device']!='1')
					$_REQUEST['attach_mobile_device'] = '0';

				if($_REQUEST['double_optin_check']!='1')
					$_REQUEST['double_optin_check'] = '0';
				if($_REQUEST['get_subs_name_check']!='1')
					$_REQUEST['get_subs_name_check'] = '0';
				if($_REQUEST['campaign_expiry_check']!='1')
					$_REQUEST['campaign_expiry_check'] = '0';
				if($_REQUEST['followup_msg_check']!='1')
					$_REQUEST['followup_msg_check'] = '0';
				if($_REQUEST['campaign_beacon_check']!=1){
					$_REQUEST['campaign_beacon_check']=0;
				}
                
                if($_REQUEST['device_id']!=1){
					$_REQUEST['device_id']=0;
				}
                
                
				$reply_email = DBin($_REQUEST['reply_email']);
				$email_updated = DBin($_REQUEST['email_updated']);

				$sql = sprintf("insert into campaigns
									(
										title,
										keyword,
										phone_number,
										type,
										welcome_sms,
										already_member_msg,
										media,
										user_id,
										double_optin,
										get_email,
										reply_email,
										email_updated,
										start_date,
										end_date,
										expire_message,
										attach_mobile_device,
										device_id,
										double_optin_check,
										get_subs_name_check,
										msg_to_get_subscriber_name,

										name_received_confirmation_msg,
										campaign_expiry_check,
										double_optin_confirm_message,
										followup_msg_check,
										share_with_subaccounts,
										campaign_beacon_check,
										custom_url
									)
								values
									(
										'%s',
										'%s',
										'%s',
										'1',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s',
										'%s'
									)",
										mysqli_real_escape_string($link,DBin($title)),
										mysqli_real_escape_string($link,DBin($keyword)),
										mysqli_real_escape_string($link,DBin($phoneNumber)),
										mysqli_real_escape_string($link,DBin($welcomeSms)),
										mysqli_real_escape_string($link,DBin($alreadyMemberSms)),
										mysqli_real_escape_string($link,DBin($fileName)),
										mysqli_real_escape_string($link,DBin($_SESSION['user_id'])),
										mysqli_real_escape_string($link,DBin($doubleOptin)),
										mysqli_real_escape_string($link,DBin($get_email)),
										mysqli_real_escape_string($link,DBin($reply_email)),
										mysqli_real_escape_string($link,DBin($email_updated)),
										mysqli_real_escape_string($link,DBin($_REQUEST['start_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['end_date'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['expire_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['attach_mobile_device'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['device_id'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['get_subs_name_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['msg_to_get_subscriber_name'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['name_received_confirmation_msg'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_expiry_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['double_optin_confirm_message'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['followup_msg_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['share_with_subaccounts'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['campaign_beacon_check'])),
										mysqli_real_escape_string($link,DBin($_REQUEST['custom_url']))

					);
				$res = mysqli_query($link,$sql) or die(mysqli_error($link));
				if($res){
					if($_REQUEST['campaign_beacon_check']=="1" && $_REQUEST['beacon']!="")
					{
						$sql_as = sprintf("select estimote_app_id,estimote_app_token from application_settings where 
										user_id='%s'",
										mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
							);
						$res_as = mysqli_query($link,$sql_as);
						$row_as = mysqli_fetch_assoc($res_as);
						$AppID = $row_as['estimote_app_id'];
						$AppToken = $row_as['estimote_app_token'];

						$identifier = DBin($_REQUEST['beacon']);

						if($_REQUEST['beacon_url_type']=="1"){

							$sql_pages = sprintf("select * from pages where id='%s'",
										mysqli_real_escape_string($link,DBin($_REQUEST['coupon']))
								);
							$res_pages = mysqli_query($link,$sql_pages);
							$row_pages = mysqli_fetch_assoc($res_pages);
							$eddystone_url = $row_pages['short_url'];
						}else{
							$eddystone_url = DBin($_REQUEST['custom_url']);
						}

						$url = "https://$AppID:$AppToken@cloud.estimote.com/v2/devices/$identifier";
						$data = '{
								   "settings": {
									 "advertisers": {
									   "eddystone_url": [{
										 "index": 1,
										 "name": "Eddystone URL",
										 "enabled": true,
										 "interval": 300,
										 "power": "-4",
										 "url" : "'.$eddystone_url.'"        
									   }]
									 }
								   }
								}';

						$res = curl_process22($url,$data);

					}

					$campaignID = mysqli_insert_id($link);
					$mediaCount = 0;
					$failedMediaCount = 0;
					$followUpCount = 0;
					for($i=0;$i<count($_REQUEST['delay_day']);$i++){
						if((trim($_REQUEST['delay_day'][$i])!='') && (trim($_REQUEST['delay_message'][$i])!='')){
							$fileName = '';
							if($_FILES['delay_media']['name'][$i]!=''){
								$ext = getExtension($_FILES['delay_media']['name'][$i]);
								$extns = array('jpg','jpeg','png','bmp','gif');
								if(!in_array($ext,$extns)){
									$failedMediaCount++;
								}else{
									$fileName = uniqid().'_'.$_FILES['delay_media']['name'][$i];
									$tmpName  = $_FILES['delay_media']['tmp_name'][$i];
									move_uploaded_file($tmpName,'uploads/'.$fileName);
									$fileName = getServerUrl().'/uploads/'.$fileName;
									$mediaCount++;
								}
							}

							$sqlFollow = sprintf("insert into follow_up_msgs
											(group_id,delay_day,delay_time,message,media,user_id)values
											(
													'%s',
													'%s',
													'%s',
													'%s',
													'%s',
													'%s'
											)",
													mysqli_real_escape_string($link,DBin($campaignID)),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_day'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_time'][$i])),
													mysqli_real_escape_string($link,DBin($_REQUEST['delay_message'][$i])),
													mysqli_real_escape_string($link,DBin($fileName)),
													mysqli_real_escape_string($link,DBin($_SESSION['user_id']))
								);

							$resFollow = mysqli_query($link,$sqlFollow);
							if($resFollow){
								$followUpCount++;
							}
						}
					}
					$_SESSION['message_status'] = 1;
					$_SESSION['message'] = 'Success! Campaign saved successfully with '.DBin($followUpCount).' follow up messages';
				}else{
					$_SESSION['message_status'] = 0;
					$_SESSION['message'] = 'Error! while saving campaign';
				}
			}else{
				$_SESSION['message_status'] = 0;
				$_SESSION['message'] = 'Error! '.DBout($_REQUEST['keyword']).' is already used or maybe reserve keyword, try another.';
			}

			header('location: view_campaigns.php');
		}
			break;


		case "login":{
			$userName = DBin($_REQUEST['username']);
			$password = $_REQUEST['password'];
			$sql = sprintf("SELECT * FROM users WHERE email='%s'",mysqli_real_escape_string($link,$userName));
			$res = mysqli_query($link,$sql);
			if(mysqli_num_rows($res)==0){
				$_SESSION['message'] = 'Error! Invalid login email, try again.';
				$_SESSION['message_status']=0;
				header("location: ".$_SERVER['HTTP_REFERER']);
				die();
			}
			else{
				$row = mysqli_fetch_assoc($res);
				if(password_verify($password, $row['password'])){
					$appSettings   = getAppSettings($row['id']);
					$adminSettings = getAppSettings('',true);
					if(trim($appSettings['time_zone'])!=''){
						date_default_timezone_set($appSettings['time_zone']);
					}
					$now = date('Y-m-d H:i');
					if(trim($appSettings['settings_date'])!=''){
						if($now >= $appSettings['settings_date']){
							$url  = 'http://apps.ranksol.com/nm_license/check_code.php';
							$data = array(
								'purchaseCode' => $appSettings['product_purchase_code'],
								'server_url' => getServerUrl()
							);
							$envatoRes = json_decode(post_curl_mqs($url,$data),true);
							if($envatoRes['error']=='yes'){
								$sql1 = sprintf("update application_settings set product_purchase_code_status='invalid' where user_id='%s'",
											mysqli_real_escape_string($link,DBin($row['id']))
									);
								mysqli_query($link,$sql1);
							}else{
								$sql2 = sprintf("update application_settings set settings_date='%s' where user_id='%s'",
										mysqli_real_escape_string($link,DBin($now)),
										mysqli_real_escape_string($link,DBin($row['id']))
									);
								mysqli_query($link,$sql2);
							}
						}
					}
					else{
						$sql3 = sprintf("update application_settings set product_purchase_code_status='invalid', 
										settings_date='%s'
										 where user_id='%s'",
										mysqli_real_escape_string($link,DBin(date('Y-m-d H:i',strtotime("-24 hours")))),
										mysqli_real_escape_string($link,DBin($row['id']))
							);
						mysqli_query($link,$sql3);
					}
					if($row['status']=='1'){
						if($row['type']=='1'){
							$_SESSION['sms_credits'] = '10000';
							$_SESSION['used_sms_credits'] = '0';
							$_SESSION['pkg_end_date'] = date('Y-m-d',strtotime("+1 month"));
							$_SESSION['pkg_status'] = '1';
						}else{
							$userPkgInfo = getAssingnedPackageInfo($row['id']);
							$_SESSION['sms_credits'] = $userPkgInfo['sms_credits'];
							$_SESSION['used_sms_credits'] = $userPkgInfo['used_sms_credits'];
							$_SESSION['pkg_end_date'] = $userPkgInfo['end_date'];
							$_SESSION['pkg_status'] = $userPkgInfo['status'];
						}

						$_SESSION['time_zone']  = $appSettings['time_zone'];
						$_SESSION['sms_gateway']= $adminSettings['sms_gateway'];
						$_SESSION['first_name'] = $row['first_name'];
						$_SESSION['last_name']  = $row['last_name'];
						$_SESSION['user_id']    = $row['id'];
						$_SESSION['user_type']  = $row['type'];
						$_SESSION['business_name']  = $row['business_name'];
						setcookie("nm_user_id", $_SESSION['user_id'], time()+3600);
						$user_settings = getAppSettings($row['id']);
						if($user_settings){
							$_SESSION['no_settings'] = '1';
						}
						$selg = sprintf("select user_id,sms_gateway from application_settings where user_type='2'");
						$exeg = mysqli_query($link,$selg);
						if(mysqli_num_rows($exeg)){
							while($rowg = mysqli_fetch_assoc($exeg)){
								$subUserID = $rowg['user_id'];
								$smsGateway= $rowg['sms_gateway'];
								$upg = sprintf("update user_package_assignment
												set
													sms_gateway='%s'
												where
													user_id='%s'",
											mysqli_real_escape_string($link,DBin($smsGateway)),
											mysqli_real_escape_string($link,DBin($subUserID))
									);
								mysqli_query($link,$upg);
							}
						}
						header("Location: dashboard.php");
					}
					else if($row['status']=='2'){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Your account is blocked, contact to admin';
						header("Location: ".$_SERVER['HTTP_REFERER']);
					}
					else if($row['status']=='3'){
						$_SESSION['message_status'] = 0;
						$_SESSION['message'] = 'Your account is deleted, contact to admin';
						header("Location: ".$_SERVER['HTTP_REFERER']);
					}
				}else{
					$_SESSION['message'] = 'Error! Invalid password, try again.';
					$_SESSION['message_status']=0;
					header("location: ".$_SERVER['HTTP_REFERER']);
					die();
				}
			}
		}
		break;

		case "logout":{
			unset($_SESSION['first_name']);
			unset($_SESSION['last_name']);
			unset($_SESSION['user_id']);
			unset($_SESSION['user_type']);
			$_SESSION['message'] = 'Success! You are successfully logged out.';
			$_SESSION['message_status'] = 1;
			header("location:index.php");
		}
		break;
	}
?>