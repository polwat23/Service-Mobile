<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
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
					$destination[] = strtolower($lib->mb_str_pad($target));
				}
				$arrToken = $func->getFCMToken('person',$destination);
				foreach($arrToken["LIST_SEND"] as $dest){
					if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
						if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupSuccess["MESSAGE"] = isset($dataComing["message_importData"][$dest["MEMBER_NO"]]) ? 
							$dataComing["message_importData"][$dest["MEMBER_NO"]].'^'.$dataComing["topic_emoji_"] : ($dataComing["message_emoji_"] ?? "-").'^'.$dataComing["topic_emoji_"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = isset($dataComing["message_importData"][$dest["MEMBER_NO"]]) ? 
							$dataComing["message_importData"][$dest["MEMBER_NO"]].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร' : $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["MESSAGE"] = isset($dataComing["message_importData"][$dest["MEMBER_NO"]]) ? 
						$dataComing["message_importData"][$dest["MEMBER_NO"]].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้' : $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
				foreach($arrDiff as $memb_diff){
					$arrGroupCheckSend["DESTINATION"] = $memb_diff;
					$arrGroupCheckSend["MESSAGE"] = isset($dataComing["message_importData"][$memb_diff]) ? 
					$dataComing["message_importData"][$memb_diff].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้' : $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrToken = $func->getFCMToken('all');
				foreach($arrToken["LIST_SEND"] as $dest){
					if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
						if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupSuccess["MESSAGE"] = $dataComing["message_emoji_"].'^'.$dataComing["topic_emoji_"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
							$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["MESSAGE"] = $dataComing["message_emoji_"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
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
				$arrDestGRP = array();
				foreach($dataComing["destination"] as $target){
					$destination_temp = array();
					if(mb_strlen($target) <= 8){
						$destination[] = strtolower($lib->mb_str_pad($target));
					}else if(mb_strlen($target) == 10){
						$destination_temp["MEMBER_NO"] = null;
						$destination_temp["TEL"] = $target;
						$arrDestGRP[] = $destination_temp;
					}
				}
				$arrayTel = $func->getSMSPerson('person',$destination);
				if(isset($arrDestGRP)){
					$arrayMerge = array_merge($arrayTel,$arrDestGRP);
				}else{
					$arrayMerge = $arrayTel;
				}
				foreach($arrayMerge as $dest){
					if(isset($dest["TEL"]) && $dest["TEL"] != ""){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
						$arrGroupSuccess["MESSAGE"] = isset($dataComing["message_importData"][($dest["MEMBER_NO"] ?? $dest["TEL"])]) ? 
						$dataComing["message_importData"][($dest["MEMBER_NO"] ?? $dest["TEL"])] : ($dataComing["message_emoji_"] ?? "-");
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
						$arrGroupCheckSend["MESSAGE"] = isset($dataComing["message_importData"][($dest["MEMBER_NO"] ?? $dest["TEL"])]) ? 
						$dataComing["message_importData"][($dest["MEMBER_NO"] ?? $dest["TEL"])] : ($dataComing["message_emoji_"] ?? "-");
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				foreach($dataComing["destination"] as $target){
					if(mb_strlen($target) <= 8){
						$target = strtolower($lib->mb_str_pad($target));
					}else if(mb_strlen($target) == 10){
						$target = $lib->formatphone($target,'-');
					}
					if(array_search($target, array_column($arrGroupAllSuccess, 'DESTINATION')) === false && 
					array_search($target, array_column($arrGroupAllSuccess, 'TEL')) === false && array_search($target, array_column($arrGroupAllFailed, 'DESTINATION')) === false){
						$arrGroupCheckSend["DESTINATION"] = $target;
						$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
				$arrayResult['FAILED'] = $arrGroupAllFailed;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayTel = $func->getSMSPerson('all');
				foreach($arrayTel as $dest){
					if(isset($dest["TEL"]) && $dest["TEL"] != ""){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
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