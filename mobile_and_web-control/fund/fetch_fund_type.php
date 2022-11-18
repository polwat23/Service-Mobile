<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		
		$arrayGroupFund = array();
		$getFundInfo = $conoracle->prepare("SELECT 
			WD.WFACCOUNT_NAME AS NAME,
			WF.FUNDACCOUNT_NO AS FUNDACCOUNT_NO,
			WF.FUNDOPEN_DATE AS FUNDOPEN_DATE,
			WF.FUNDTYPE_CODE as FUNDTYPE_CODE
			FROM WCFUNDMASTER WF
			LEFT JOIN WCDEPTMASTER WD ON (WD.DEPTACCOUNT_NO = WF.DEPTACCOUNT_NO) 
			WHERE 
			WF.FUNDTYPE_CODE IN ('001', '002')
			AND trim(WF.DEPTACCOUNT_NO) = :member_no");
		$getFundInfo->execute([':member_no' => $member_no]);
		while($rowFund = $getFundInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayFund = array();
			$arrayFund["NAME"] = $rowFund["NAME"];
			$arrayFund["FUNDTYPE_CODE"] = $rowFund["FUNDTYPE_CODE"];
			if ($rowFund["FUNDTYPE_CODE"] == '001') {
				$arrayFund["FUND_TYPE"] = 'กองทุนล้านที่ 2';
			} else {
				$arrayFund["FUND_TYPE"] = 'กองทุนล้านที่ 3';
			}
			$arrayFund["FUND_ACCOUNT"] = trim($rowFund["FUNDACCOUNT_NO"]);
			$arrayFund["FUND_OPEN"] = $lib->convertdate($rowFund["FUNDOPEN_DATE"],'d m Y');
			$arrayGroupFund[] = $arrayFund;
		}
		$arrayResult['FUND_INFO'] = $arrayGroupFund;
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