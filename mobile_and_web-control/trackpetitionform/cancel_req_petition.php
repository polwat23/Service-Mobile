<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','reqdoc_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PetitionFormTrack')){
		$getIsCancel = $conmysql->prepare("SELECT req_status FROM gcpetitionformreq WHERE reqdoc_no  = :reqdoc_no");
		$getIsCancel->execute([':reqdoc_no' => $dataComing["reqdoc_no"]]);
		$rowCancel = $getIsCancel->fetch(PDO::FETCH_ASSOC);
		if($rowCancel["req_status"] == '8'){
			$cancelReq = $conmysql->prepare("UPDATE gcpetitionformreq SET req_status = '9' WHERE reqdoc_no  = :reqdoc_no");
			if($cancelReq->execute([':reqdoc_no' => $dataComing["reqdoc_no"]])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1041",
					":error_desc" => "ยกเลิกใบคำขอไม่ได้เพราะไม่สามารถ Update ลง gcpetitionformreq ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqdoc_no' => $dataComing["reqdoc_no"]]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ยกเลิกใบคำขอไม่ได้เพราะไม่สามารถ Update ลง gcpetitionformreq ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqdoc_no' => $dataComing["reqdoc_no"]]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1041";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0083";
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