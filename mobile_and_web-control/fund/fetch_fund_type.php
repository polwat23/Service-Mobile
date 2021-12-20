<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrayGroupFund = array();
		$getFundInfo = $conmssql->prepare("SELECT mp.PRENAME_DESC,wcm.DEPTACCOUNT_NAME,wcm.DEPTACCOUNT_SNAME,wmt.WCMEMBER_DESC,wcm.DEPTACCOUNT_NO,wcm.DEPTOPEN_DATE,wcm.WFTYPE_CODE
											FROM WCDEPTMASTER wcm LEFT JOIN mbucfprename mp ON wcm.prename_code = mp.prename_code
											LEFT JOIN WCMEMBERTYPE wmt ON wcm.wftype_code = wmt.wftype_code and wcm.wc_id = wmt.wc_id
											WHERE wcm.member_no = :member_no and wcm.deptclose_status = '0'");
		$getFundInfo->execute([':member_no' => $member_no]);
		while($rowFund = $getFundInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayFund = array();
			$arrayFund["NAME"] = $rowFund["PRENAME_DESC"].$rowFund["DEPTACCOUNT_NAME"].' '.$rowFund["DEPTACCOUNT_SNAME"];
			$arrayFund["FUND_TYPE"] = $rowFund["WCMEMBER_DESC"];
			$arrayFund["FUND_ACCOUNT"] = $rowFund["DEPTACCOUNT_NO"];
			$arrayFund["FUND_OPEN"] = $lib->convertdate($rowFund["DEPTOPEN_DATE"],'d m Y');
			if($rowFund["WFTYPE_CODE"] == '01'){
				$arrayFund["FUND_PROTECT"] = '500,000';
			}else if($rowFund["WFTYPE_CODE"] == '03'){
				$arrayFund["FUND_PROTECT"] = '100,000';
			}else if($rowFund["WFTYPE_CODE"] == '02'){
				$arrayFund["FUND_PROTECT"] = '100,000';
			}
			$getReceive = $conmssql->prepare("SELECT TRANSFEREE_NAME,TRANSFEREE_RELATION
												FROM WCCODEPOSIT WHERE deptaccount_no = :deptaccount_no ORDER BY seq_no");
			$getReceive->execute([':deptaccount_no' => $rowFund["DEPTACCOUNT_NO"]]);
			while($rowReceive = $getReceive->fetch(PDO::FETCH_ASSOC)){
				$arrReceive = array();
				$arrReceive["RECEIVE_NAME"] = $rowReceive["TRANSFEREE_NAME"];
				$arrReceive["RECEIVE_RELATION"] = $rowReceive["TRANSFEREE_RELATION"];
				$arrayFund["RECEIVE"][] = $arrReceive;
			}
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