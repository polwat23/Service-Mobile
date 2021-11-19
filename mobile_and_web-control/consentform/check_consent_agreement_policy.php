<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayResult['IS_THIRDPARTY'] = TRUE;
		$arrTerm = array();
		$arrayResult['TERMS'] = $arrTerm;
		$checkOldFormTel = $conmssql->prepare("SELECT COUNT(id_editdata) as C_FORMNOTAPPROVE FROM gcmembereditdata 
											WHERE member_no = :member_no and inputgroup_type = 'tel' and is_updateoncore = '0'");
		$checkOldFormTel->execute([':member_no' => $payload["member_no"]]);
		$rowOldFormTel = $checkOldFormTel->fetch(PDO::FETCH_ASSOC);
		if($rowOldFormTel["C_FORMNOTAPPROVE"] > 0){
			$getTelMobile = $conmssql->prepare("SELECT incoming_data as phone_number FROM gcmembereditdata WHERE member_no = :member_no
												and inputgroup_type = 'tel' and is_updateoncore = '0'");
			$getTelMobile->execute([':member_no' => $payload["member_no"]]);
			$rowTelMobile = $getTelMobile->fetch(PDO::FETCH_ASSOC);
		}else{
			$getTelMobile = $conmssqlcoop->prepare("SELECT telephone as phone_number FROM COCOOPTATION WHERE member_id = :member_no");
			$getTelMobile->execute([':member_no' => $member_no]);
			$rowTelMobile = $getTelMobile->fetch(PDO::FETCH_ASSOC);
		}
		$arrayResult['PHONE'] = $rowTelMobile["phone_number"];
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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