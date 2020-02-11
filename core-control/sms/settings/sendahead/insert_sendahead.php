<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','send_message_emoji_','send_date'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead') || 
	$func->check_permission_core($payload,'sms','sendmessageall') || 
	$func->check_permission_core($payload,'sms','sendmessageperson')){
		$platform = null;
		$pathImg = null;
		$id_smsquery = isset($dataComing["id_smsquery"]) && $dataComing["id_smsquery"] != '' ? $dataComing["id_smsquery"] : null;
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != '' ? $dataComing["id_smstemplate"] : null;
		if(isset($dataComing["send_platform"])){
			switch($dataComing["send_platform"]) {
				case "sms" :
					$platform = '1';
					break;
				case "mobile_app" :
					$platform = '2';
					break;
				default :
					$platform = '1';
			}
		}
		if(isset($dataComing["send_image"]) && $dataComing["send_image"] != null){
			$destination = __DIR__.'/../../../../resource/image_wait_to_be_sent';
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
		if(isset($dataComing["type_send"]) && $dataComing["type_send"] == "all"){
			$insertSendAhead = $conmysql->prepare("INSERT INTO smssendahead(send_topic,send_message,destination,repeat_send,send_date,create_by,
													id_smsquery,id_smstemplate,send_platform,send_image)
													VALUES(:send_topic,:send_message,'all',:is_repeat,:send_date,:username,:id_smsquery,:id_template,:send_platform,:send_image)");
			if($insertSendAhead->execute([
				':send_topic' => $dataComing["send_topic_emoji_"],
				':send_message' => $dataComing["send_message_emoji_"],
				':is_repeat' => ($dataComing["is_repeat"] ? '1' : '0'),
				':send_date' => $dataComing["send_date"],
				':username' => $payload["username"],
				':id_smsquery' => $id_smsquery,
				':id_template' => $id_template,
				':send_platform' => $platform ?? '3',
				':send_image' => $pathImg ?? null
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถตั้งเวลาการส่งข้อความล่วงหน้าได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$insertSendAhead = $conmysql->prepare("INSERT INTO smssendahead(send_topic,send_message,destination,repeat_send,send_date,create_by,
													id_smsquery,id_smstemplate,send_platform,send_image)
													VALUES(:send_topic,:send_message,:destination,:is_repeat,:send_date,:username,:id_smsquery,:id_template,:send_platform,:send_image)");
			if($insertSendAhead->execute([
				':send_topic' => $dataComing["send_topic_emoji_"],
				':send_message' => $dataComing["send_message_emoji_"],
				':destination' => isset($dataComing["destination"]) ? implode(',',$dataComing["destination"]) : 'all',
				':is_repeat' => ($dataComing["is_repeat"] ? '1' : '0'),
				':send_date' => $dataComing["send_date"],
				':username' => $payload["username"],
				':id_smsquery' => $id_smsquery,
				':id_template' => $id_template,
				':send_platform' => $platform ?? '3',
				':send_image' => $pathImg ?? null
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถตั้งเวลาการส่งข้อความล่วงหน้าได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
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