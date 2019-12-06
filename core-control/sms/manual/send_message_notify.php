<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message','topic','destination','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		if($dataComing["channel_send"] == "mobile"){
			$arrPayloadNotify = array();
			$arrMessage = array();
			$arrMember = array();
			$getFCMToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
												WHERE gul.receive_notify_news = '1' and gul.member_no IN('".implode("','",$dataComing["destination"])."')
												and gul.is_login = '1' and gtk.fcm_token IS NOT NULL");
			$getFCMToken->execute();
			if($getFCMToken->rowCount() > 0){
				$arrDestination = array();
				while($rowFcmToken = $getFCMToken->fetch()){
					if(isset($rowFcmToken["fcm_token"])){
						$arrDestination[] = $rowFcmToken["fcm_token"];
						$arrMember[] = $rowFcmToken["member_no"];
					}
				}
				$arrPayloadNotify["TO"] = $arrDestination;
				$arrPayloadNotify["MEMBER_NO"] = $arrMember;
				$arrMessage["SUBJECT"] = $dataComing["topic"];
				$arrMessage["BODY"] = $dataComing["message"];
				$arrMessage["PATH_IMAGE"] = $dataComing["path_image"] ?? null;
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				if($func->insertHistory($arrPayloadNotify,'1')){
					if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE_CODE'] = "1001";
						$arrayResult['RESPONSE_AWARE'] = "notify";
						$arrayResult['RESPONSE'] = "Notify Failed see log error";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "1002";
					$arrayResult['RESPONSE_AWARE'] = "insert";
					$arrayResult['RESPONSE'] = "Cannot insert history";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "4003";
				$arrayResult['RESPONSE_AWARE'] = "notfound";
				$arrayResult['RESPONSE'] = "Cannot found fcm token";
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