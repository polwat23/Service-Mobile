<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiary = $conoracle->prepare("SELECT 
											W.NAME AS FULLNAME,
											W.CODEPT_ADDRE AS ADDR,
											NVL(W.CODEPT_TEL,'-') AS TEL,
											NVL(wc.GAIN_CONCERN,NVL(w.CODEPT_RELATION,'-')) AS GAIN_CONCERN,
											trim(W.CODEPT_ID) AS CARD_PERSON
										FROM 
											wccodeposit  W,
											wcucfgainconcern wc
										WHERE
											(w.CODEPT_RELATION = wc.CONCERN_CODE (+))
											 AND trim(W.DEPTACCOUNT_NO) = :member_no");
		$getBeneficiary->execute([':member_no' => $member_no]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["FULLNAME"];
			$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["ADDR"]);
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
			/*
			$arrBenefit["TYPE_PERCENT"] = 'text';
			$arrBenefit["PERCENT_TEXT"] = isset($rowBenefit["REMARK"]) && $rowBenefit["REMARK"] != "" ? $rowBenefit["REMARK"] : "แบ่งให้เท่า ๆ กัน";
			$arrBenefit["PERCENT"] = filter_var($rowBenefit["REMARK"], FILTER_SANITIZE_NUMBER_INT);
			*/
			$other_info = array();
			$other_info[] = array("LABEL" => "เลขบัตรประชาชน", "VALUE" => $lib->formatcitizen($rowBenefit["CARD_PERSON"]));

			$arrBenefit["OTHER_INFO"] = $other_info;
			
			$arrGroupBNF[] = $arrBenefit;
		}
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
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