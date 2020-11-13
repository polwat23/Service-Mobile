<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		if($dataComing["bind_src_message"]["result"] == 'success'){
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '1',
				':coop_account_no' => $coop_account_no
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0039";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '-9',
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $dataComing["bind_src_message"]["reason"].' / termSeq'.$dataComing["bind_src_message"]["term_seq"],
				':coop_account_no' => $coop_account_no,
				':query_flag' => '1'
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
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