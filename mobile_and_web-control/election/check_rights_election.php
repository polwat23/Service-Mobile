<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkElection = $conoracle->prepare("SELECT NVL(POST_NO,'-99') as POST_NO FROM MBMEMBELECTION WHERE ELECTION_YEAR = EXTRACT(YEAR FROM SYSDATE) + 543 AND MEMBER_NO = :member_no");
		$checkElection->execute([':member_no' => $member_no]);
		$rowElection = $checkElection->fetch(PDO::FETCH_ASSOC);
		if($rowElection["POST_NO"] == '-99' || $payload["member_no"] == 'etnmode2'){
			$arrOption = array();
			$arrOption[0]["LABEL"] = "สรรหาบนระบบออนไลน์";
			$arrOption[0]["VALUE"] = "3";
			$arrayResult['OPTION_WISH'] = $arrOption;
			$arrayResult['HEADER_ELECTION'] = "แจ้งความประสงค์ในการสรรหา";
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['REMARK'] = "เมื่อท่านสมาชิกลงทะเบียนการสรรหาทางอิเล็กทรอนิกส์ (E-VOTE) แล้ว ไม่สามารถเปลี่ยนเป็นวิธีการอื่นได้";
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0000";
			$arrayResult['RESPONSE_MESSAGE'] = $configError["ELECTION"][0]["ELECTION_NOTFOUND"][0][$lang_locale];
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