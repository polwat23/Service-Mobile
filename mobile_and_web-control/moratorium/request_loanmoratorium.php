<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Moratorium')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO LNREQMORATORIUM  
																	( COOP_ID,   MORATORIUM_DOCNO,   MEMBER_NO,   LOANCONTRACT_NO,   REQUEST_DATE,   
																	REQUEST_STATUS,   ENTRY_ID,   ENTRY_DATE )  
																	VALUES ('000000', (select '64'||trim(to_char(count(1), '00000000')) from lnreqmoratorium where MORATORIUM_DOCNO like '64%' and coop_id = '000000'), :member_no, :loancontract_no, trunc(sysdate),
																	1, :member_no, sysdate)");
		if($insertSchShipOnlineDoc->execute([
			':member_no' => $member_no,
			':loancontract_no' => $dataComing["loancontract_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS1040";
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