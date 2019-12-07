<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message','topic','type_send','channel_send','id_query'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		if($dataComing["channel_send"] == "mobile_app"){
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
				if(isset($dataComing["send_image"]) && $dataComing["send_image"] != null){
					$destination = __DIR__.'/../../../resource/image_wait_to_be_sent';
					$file_name = $lib->randomText('all',6);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_img($dataComing["send_image"],$file_name,$destination,null);
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = "Image oversize please reduce filesize";
						$arrayResult['RESULT'] = FALSE;
						http_response_code(413);
						echo json_encode($arrayResult);
						exit();
					}else{
						if($createImage){
							$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0007";
							$arrayResult['RESPONSE_MESSAGE'] = "Extension is invalid";
							$arrayResult['RESULT'] = FALSE;
							http_response_code(415);
							echo json_encode($arrayResult);
							exit();
						}
					}
				}
				$arrSendSuccess = array();
				$arrSendFailed = array();
				$rowQuery = $getQuery->fetch();
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch()){
						$arrGroupMessage = array();
						$arrDestination = array();
						$arrMemberNoDestination = array();
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$getFcmToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
															WHERE gul.receive_notify_transaction = '1' and gul.member_no = :member_no and gul.is_login = '1' and gtk.fcm_token IS NOT NULL");
						$getFcmToken->execute([':member_no' => $rowTarget[$rowQuery["target_field"]]]);
						while($rowToken = $getFcmToken->fetch()){
							$arrDestination[] = $rowToken["fcm_token"];
							$arrGroupMessage["MEMBER_NO"] = $rowToken["member_no"];
						}
						$arrMessage = $lib->mergeTemplate($dataComing["topic"],$dataComing["message"],$arrTarget);
						$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
						$arrGroupMessage["PAYLOAD"] = $arrMessage;
						$arrGroupMessage["TO"] = $arrDestination;
						$arrGroupMessage["TYPE_SEND_HISTORY"] = "manymessage";
						if(sizeof($arrGroupMessage["TO"]) > 0){
							if($func->insertHistory($arrGroupMessage,'2')){
								if($lib->sendNotify($arrGroupMessage,$dataComing["type_send"])){
									$arrSendSuccess[] = $arrGroupMessage["MEMBER_NO"];
								}else{
									$arrSendFailed[] = $arrGroupMessage["MEMBER_NO"];
								}
							}else{
								$arrSendFailed[] = $arrGroupMessage["MEMBER_NO"];
							}
						}
					}
					$arrayResult['SUCCESS'] = $arrSendSuccess;
					$arrayResult['FAILED'] = $arrSendFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$query = $rowQuery['sms_query'];
					if(stripos($query,'WHERE') === FALSE){
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." WHERE ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " WHERE ".$rowQuery["condition_target"];
						}
					}else{
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." and ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " and ".$rowQuery["condition_target"];
						}
					}
					foreach($dataComing["destination"] as $target){
						$queryTarget = $conoracle->prepare($query);
						$queryTarget->execute([':'.$rowQuery["target_field"] => $target]);
						while($rowTarget = $queryTarget->fetch()){
							$arrGroupMessage = array();
							$arrDestination = array();
							$arrMemberNoDestination = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							$getFcmToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
																WHERE gul.receive_notify_transaction = '1' and gul.member_no = :member_no and gul.is_login = '1' and gtk.fcm_token IS NOT NULL");
							$getFcmToken->execute([':member_no' => $rowTarget[$rowQuery["target_field"]]]);
							while($rowToken = $getFcmToken->fetch()){
								$arrDestination[] = $rowToken["fcm_token"];
								$arrGroupMessage["MEMBER_NO"] = $rowToken["member_no"];
							}
							$arrMessage = $lib->mergeTemplate($dataComing["topic"],$dataComing["message"],$arrTarget);
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrGroupMessage["PAYLOAD"] = $arrMessage;
							$arrGroupMessage["TO"] = $arrDestination;
							$arrGroupMessage["TYPE_SEND_HISTORY"] = "manymessage";
							if(sizeof($arrGroupMessage["TO"]) > 0){
								if($func->insertHistory($arrGroupMessage,'2')){
									if($lib->sendNotify($arrGroupMessage,'someone')){
										$arrSendSuccess[] = $arrGroupMessage["MEMBER_NO"];
									}else{
										$arrSendFailed[] = $arrGroupMessage["MEMBER_NO"];
									}
								}else{
									$arrSendFailed[] = $arrGroupMessage["MEMBER_NO"];
								}
							}
						}
					}
					$arrayResult['SUCCESS'] = $arrSendSuccess;
					$arrayResult['FAILED'] = $arrSendFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "1004";
				$arrayResult['RESPONSE_AWARE'] = "notfound";
				$arrayResult['RESPONSE'] = "Not found query";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>