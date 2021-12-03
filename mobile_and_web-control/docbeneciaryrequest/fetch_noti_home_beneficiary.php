<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentBeneciaryRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getRegDocument = $conmysql->prepare("SELECT REQDOC_NO, MEMBER_NO, DOCUMENTTYPE_CODE, FORM_VALUE, DOCUMENT_URL, REQ_STATUS, REQUEST_DATE, UPDATE_DATE 
										FROM GCREQDOCONLINE WHERE REQ_STATUS = '1' AND DOCUMENTTYPE_CODE = 'RRGT' AND MEMBER_NO = :member_no");
		$getRegDocument->execute([
			':member_no' => $payload["member_no"],
		]);
		$rowRegDocument = $getRegDocument->fetch(PDO::FETCH_ASSOC);
		
		if(isset($rowRegDocument["REQDOC_NO"])){
			$arrayResult['IS_NOTIFY_CBNF'] = false;
		}else{
			$getReqDocument = $conmysql->prepare("SELECT REQDOC_NO, MEMBER_NO, DOCUMENTTYPE_CODE, FORM_VALUE, DOCUMENT_URL, REQ_STATUS, REQUEST_DATE, UPDATE_DATE 
											FROM GCREQDOCONLINE WHERE REQ_STATUS NOT IN('9','-9') AND DOCUMENTTYPE_CODE = 'CBNF' AND MEMBER_NO = :member_no");
			$getReqDocument->execute([
				':member_no' => $payload["member_no"],
			]);
			$rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC);
			
			if(isset($rowReqDocument["REQDOC_NO"])){
				$arrayResult['IS_NOTIFY_CBNF'] = false;
			}else{
				$arrayResult['IS_NOTIFY_CBNF'] = true;
				$arrayResult['REMARK'] = 'ท่านยังไม่ได้กรอกข้อมูลผู้รับผลประโยชน์  กรุณากรอกข้อมูลที่เมนูใบคำขอผู้รับผลประโยชน์';
			}
		}
		
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