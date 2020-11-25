<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','channel'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$updatAvatar = $conmysql->prepare("UPDATE gcmemberaccount SET path_avatar = null,upload_from_channel = :channel,upload_date = NOW()
												WHERE member_no = :member_no");
		if($updatAvatar->execute([
			':channel' => $dataComing["channel"],
			':member_no' => $payload["member_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1008",
				":error_desc" => "อัพโหลดรูปโปรไฟล์ไม่ได้ Path => ".$path_avatar."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "อัพโหลดรูปโปรไฟล์ไม่ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updatAvatar->queryString."\n"."Param => ". json_encode([
				':channel' => $dataComing["channel"],
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1008";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>