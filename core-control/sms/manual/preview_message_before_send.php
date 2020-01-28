<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
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
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			if($dataComing["type_send"] == "person"){
				$destination = array();
				foreach($dataComing["destination"] as $target){
					$destination[] = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
				}
				$arrMessage = array();
				$arrMember = array();
				$arrToken = $func->getFCMToken('many',$destination);
				if(sizeof($arrToken["MEMBER_NO"]) > 0){
					foreach($arrToken["MEMBER_NO"] as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = isset($dataComing["message_importData"][$member]) ? $dataComing["message_importData"][$member] : $dataComing["message_emoji_"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}
					$arrDiff = array_diff($destination,array_column($arrGroupAllSuccess, 'DESTINATION'));
					foreach($arrDiff as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = isset($dataComing["message_importData"][$member]) ? $dataComing["message_importData"][$member] : $dataComing["message_emoji_"];
						$arrGroupAllFailed[] = $arrGroupSuccess;
					}
					$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					foreach($destination as $member){
						$arrGroupSuccess["DESTINATION"] = $member;
						$arrGroupSuccess["MESSAGE"] = isset($dataComing["message_importData"][$member]) ? $dataComing["message_importData"][$member] : $dataComing["message_emoji_"];
						$arrGroupAllFailed[] = $arrGroupSuccess;
					}
					$arrayResult['SUCCESS'] = [];
					$arrayResult['FAILED'] = $arrGroupAllFailed;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}
			}else{
				$arrToken = $func->getFCMToken('all');
				foreach($arrToken["MEMBER_NO"] as $member){
					$arrGroupSuccess["DESTINATION"] = $member;
					$arrGroupSuccess["MESSAGE"] = $dataComing["message_emoji_"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}
		}else if($dataComing["channel_send"] == "sms"){
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			if($dataComing["type_send"] == "person"){
				$destination = array();
				foreach($dataComing["destination"] as $target){
					$destination[] = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
				}
				$arrayResult['RESPONSE'] = $destination;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayTel = $func->getSMSPerson('all');
				foreach($arrayTel as $key => $dataTel){
					$arrGroupSuccess["DESTINATION"] = $dataTel["MEMBER_NO"][$key];
					$arrGroupSuccess["TEL"] = $lib->formatphone($dataTel["TEL"][$key],'-');
					$arrGroupSuccess["MESSAGE"] = $dataComing["message_emoji_"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
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