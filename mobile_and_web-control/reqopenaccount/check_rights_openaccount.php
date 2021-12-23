<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'OpenAccountRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkHaveAccount = $conmssql->prepare("SELECT COUNT(DEPTACCOUNT_NO) as C_DEPT FROM dpdeptmaster WHERE member_no = :member_no");
		$checkHaveAccount->execute([':member_no' => $member_no]);
		$rowHaveAcc = $checkHaveAccount->fetch(PDO::FETCH_ASSOC);
		if($rowHaveAcc["C_DEPT"] > 0){
			$arrayResult['BANK_ACCOUNT'] = "375-1-01428-4";
			$arrayResult['BANK_ACCOUNT_NAME'] = "สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด";
			$arrayResult['BANK_NAME'] = "ธนาคารกรุงไทย จำกัด";
			$arrayResult['BANK_BRANCH'] = "สาขา แม่โจ้";
			$arrayResult['BANK_LOGO'] = $config['URL_SERVICE']."/resource/utility_icon/bank_icon/ktb.webp";
			$arrayResult['MIN_AMOUNT_OPEN'] = 100;
			$arrayResult['CAN_COPY_BANK_ACCOUNT'] = TRUE;
			$arrayResult['REQ_AMOUNT'] = TRUE;
			$arrayResult['REQ_SLIP'] = TRUE;
			$arrayResult['IS_REQ'] = TRUE;
			$arrayResult['REQ_MORE'] = FALSE;
			$getOldReq = $conmysql->prepare("SELECT reqopendoc_no,amount_open,doc_url
											FROM gcreqopenaccount WHERE member_no = :member_no and req_status = '8'");
			$getOldReq->execute([':member_no' => $member_no]);
			if($getOldReq->rowCount() > 0){
				$rowOldReq = $getOldReq->fetch(PDO::FETCH_ASSOC);
				$arrayResult['IS_REQ'] = FALSE;
				$dataTracking = array();
				$dataTracking["DOC_URL"] = $rowOldReq["doc_url"];
				$dataTracking["AMOUNT"] = number_format($rowOldReq["amount_open"],2);
				$dataTracking["DOC_NO"] = $rowOldReq["reqopendoc_no"];
				$arrayResult["DATA_TRACKING"][] = $dataTracking;
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError["OPEN_ACCOUNT"][0]["NOT_HAVE_ACC"][0][$lang_locale];
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