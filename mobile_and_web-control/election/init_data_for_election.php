<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','value_election'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["value_election"] == '2'){
			$arrayResult['SKIP_INIT'] = TRUE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
				$getInitData = $conoracle->prepare("SELECT MP.PRENAME_SHORT,MB.MEMB_NAME,MB.MEMB_SURNAME,MB.CARD_PERSON,MB.SMS_MOBILEPHONE
																	FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
																	WHERE mb.member_no = :member_no");
				$getInitData->execute([':member_no' => $member_no]);
				$rowData = $getInitData->fetch(PDO::FETCH_ASSOC);
				if(isset($rowData["SMS_MOBILEPHONE"]) && $rowData["SMS_MOBILEPHONE"] != ""){
					$arrayResult['FULL_NAME'] = $rowData["PRENAME_SHORT"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrayResult['CARD_PERSON'] = $rowData["CARD_PERSON"];
					$arrayResult['TEL'] = $rowData["SMS_MOBILEPHONE"];
					$arrayResult['SKIP_INIT'] = FALSE;
					$arrayResult['IS_OTP'] = TRUE;
					$arrayResult['RESULT'] = TRUE;
					$arrayResult['HEADER_ELECTION'] = "กรุณาตรวจสอบข้อมูล";
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0017";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
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