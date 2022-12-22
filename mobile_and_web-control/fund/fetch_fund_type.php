<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];

		$arrayGroupFund = array();
		$getFundInfo = $conmssql->prepare("SELECT mbmembfund.funddoc_no ,  mbucffund.fundtype_code , mbucffund.fundtype_desc , mbmembfund.fundbalance_amt ,mbmembfund.fundperiod_amt 
											from mbmembfund 
											left join mbucffund on  mbucffund.coop_id = mbmembfund.coop_id  and mbucffund.fundtype_code = mbmembfund.fundtype_code
											where mbmembfund.fund_status = '1'
										
											and mbmembfund.member_no = :member_no
											order by mbucffund.fundtype_code  ,  mbmembfund.funddoc_no");
		$getFundInfo->execute([':member_no' => $member_no]);
		while($rowFund = $getFundInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayFund = array();
		//	$arrayFund["NAME"] = $rowFund["PRENAME_DESC"].$rowFund["DEPTACCOUNT_NAME"].' '.$rowFund["DEPTACCOUNT_SNAME"];
			$arrayFund["FUND_TYPE"] = $rowFund["fundtype_desc"];
			$arrayFund["FUND_ACCOUNT"] = $rowFund["funddoc_no"];
		//	$arrayFund["FUND_OPEN"] = $lib->convertdate($rowFund["DEPTOPEN_DATE"],'d m Y');
			$arrayFund["FUND_PROTECT"] = number_format($rowFund["fundbalance_amt"],2);
			$arrayFund["FUND_PERIOD"] = number_format($rowFund["fundperiod_amt"],2);
		/*	$getReceive = $conmssql->prepare("SELECT TRANSFEREE_NAME,TRANSFEREE_RELATION
												FROM WCCODEPOSIT WHERE deptaccount_no = :deptaccount_no ORDER BY seq_no");
			$getReceive->execute([':deptaccount_no' => $rowFund["DEPTACCOUNT_NO"]]);
			while($rowReceive = $getReceive->fetch(PDO::FETCH_ASSOC)){
				$arrReceive = array();
				$arrReceive["RECEIVE_NAME"] = $rowReceive["TRANSFEREE_NAME"];
				$arrReceive["RECEIVE_RELATION"] = $rowReceive["TRANSFEREE_RELATION"];
				$arrayFund["RECEIVE"][] = $arrReceive;
			}*/
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