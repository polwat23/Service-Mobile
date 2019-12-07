<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','send_topic','send_message','send_date'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead')){
		$platform = null;
		$pathImg = null;
		$id_smsquery = $dataComing["id_smsquery"] != '' ? $dataComing["id_smsquery"] : null ?? null;
		if(isset($dataComing["send_platform"])){
			switch($dataComing["send_platform"]) {
				case "sms" :
					$platform = '1';
					break;
				case "mobile_app" :
					$platform = '2';
					break;
				case "all" :
					$platform = '3';
					break;
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
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = "Image oversize please reduce filesize";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(413);
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg = "resource/image_wait_to_be_sent/".$createImage["normal_path"];
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
		if(isset($dataComing["type_send"]) && $dataComing["type_send"] == "all"){
			$insertSendAhead = $conmysql->prepare("INSERT INTO smssendahead(send_topic,send_message,destination,send_date,create_by,id_smsquery,send_platform,send_image)
													VALUES(:send_topic,:send_message,'all',:send_date,:username,:id_smsquery,:send_platform,:send_image)");
			if($insertSendAhead->execute([
				':send_topic' => $dataComing["send_topic"],
				':send_message' => $dataComing["send_message"],
				':send_date' => $dataComing["send_date"],
				':username' => $payload["username"],
				':id_smsquery' => $id_smsquery,
				':send_platform' => $platform ?? '3',
				':send_image' => $pathImg ?? null
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "5005";
				$arrayResult['RESPONSE_AWARE'] = "insert";
				$arrayResult['RESPONSE'] = "Cannot insert send ahead";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$insertSendAhead = $conmysql->prepare("INSERT INTO smssendahead(send_topic,send_message,destination,send_date,create_by,id_smsquery,send_platform,send_image)
													VALUES(:send_topic,:send_message,:destination,:send_date,:username,:id_smsquery,:send_platform,:send_image)");
			if($insertSendAhead->execute([
				':send_topic' => $dataComing["send_topic"],
				':send_message' => $dataComing["send_message"],
				':destination' => isset($dataComing["destination"]) ? implode(',',$dataComing["destination"]) : 'all',
				':send_date' => $dataComing["send_date"],
				':username' => $payload["username"],
				':id_smsquery' => $id_smsquery,
				':send_platform' => $platform ?? '3',
				':send_image' => $pathImg ?? null
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "5005";
				$arrayResult['RESPONSE_AWARE'] = 'insert';
				$arrayResult['RESPONSE'] = "Cannot insert send ahead";
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