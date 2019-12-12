<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message','topic','destination','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		if($dataComing["channel_send"] == "mobile_app"){
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
			if($dataComing["type_send"] == "person"){
				$destination = array();
				foreach($dataComing["destination"] as $target){
					$destination[] = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
				}
				$arrPayloadNotify = array();
				$arrMessage = array();
				$arrMember = array();
				$getFCMToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
													WHERE gul.receive_notify_news = '1' and gul.member_no IN('".implode("','",$destination)."')
													and gul.is_login = '1' and gtk.fcm_token IS NOT NULL and gtk.at_is_revoke = '0' and gul.channel = 'mobile_app'");
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
					$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
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