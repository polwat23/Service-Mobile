<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_transchedule', 'scheduler_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScheduleList')){
		$updateFavAccount = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = :scheduler_status
											WHERE id_transchedule = :id_transchedule and member_no = :member_no");
		if($updateFavAccount->execute([
			':scheduler_status' => ($dataComing["scheduler_status"] ?? '0'),
			':id_transchedule' => $dataComing["id_transchedule"],
			':member_no' => $payload["member_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1029",
				":error_desc" => "แก้ไขรายการล่วงหน้าไม่ได้ ไม่สามารถ UPDATE gctransactionschedule ได้"."\n"."Query => ".$updateFavAccount->queryString."\n".json_encode([
					':scheduler_status' => ($dataComing["scheduler_status"] ?? '0'),
					':id_transchedule' => $dataComing["id_transchedule"],
					':member_no' => $payload["member_no"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "แก้ไขรายการล่วงหน้าไม่ได้ ไม่สามารถ  UPDATE gctransactionschedule ได้"."\n"."Query => ".$updateFavAccount->queryString."\n".json_encode([
				':scheduler_status' => ($dataComing["scheduler_status"] ?? '0'),
				':id_transchedule' => $dataComing["id_transchedule"],
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1029";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
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
	require_once('../../include/exit_footer.php');
	
}
?>
