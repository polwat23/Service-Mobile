<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','apv_docno'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$updateCompleteApv = $conmysql->prepare("UPDATE gcapvdept SET status = 1 WHERE apv_docno = :apv_docno");
		if($updateCompleteApv->execute([
			':apv_docno' => $dataComing["apv_docno"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1034",
				":error_desc" => "อนุมัติรายการเงินฝากไม่ได้เพราะ Update ลงตาราง dpdeptapprove ไม่ได้"."\n"."Query => ".$updateCompleteApv->queryString."\n"."Param => ". json_encode([
					':apv_docno' => $dataComing["apv_docno"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "อนุมัติรายการเงินฝากไม่ได้เพราะ Update ลง dpdeptapprove ไม่ได้"."\n"."Query => ".$updateCompleteApv->queryString."\n"."Param => ". json_encode([
				':apv_docno' => $dataComing["apv_docno"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1034";
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
