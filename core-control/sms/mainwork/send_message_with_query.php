<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message','topic','destination','type_send','channel_send','id_query'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		if($dataComing["channel_send"] == "mobile"){
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
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
					$arrayResult['SUCCESS'] = $arrSendSuccess;
					$arrayResult['FAILED'] = $arrSendFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					foreach($dataComing["destination"] as $target){
						$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
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