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
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
					if($createImage){
						$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
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
				$arrMessage = array();
				$arrMember = array();
				$arrGroupAllSuccess = array();
				$arrGroupAllFailed = array();
				$getFCMToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
													WHERE gul.receive_notify_news = '1' and gul.member_no IN('".implode("','",$destination)."')
													and gul.is_login = '1' and gtk.fcm_token IS NOT NULL and gtk.at_is_revoke = '0' and gul.channel = 'mobile_app'");
				$getFCMToken->execute();
				if($getFCMToken->rowCount() > 0){
					while($rowFcmToken = $getFCMToken->fetch()){
						if(isset($rowFcmToken["fcm_token"])){
							if(!in_array($rowFcmToken["member_no"],$arrMember)){
								$arrMember[] = $rowFcmToken["member_no"];
							}
						}
					}
					foreach($arrMember as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = $dataComing["message"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}
					$arrDiff = array_diff($destination,array_column($arrGroupAllSuccess, 'DESTINATION'));
					foreach($arrDiff as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = $dataComing["message"];
						$arrGroupAllFailed[] = $arrGroupSuccess;
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					foreach($destination as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = $dataComing["message"];
						$arrGroupAllFailed[] = $arrGroupSuccess;
					}
					$arrayResult['SUCCESS'] = [];
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>