<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AllowanceInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupInfo = array();
		$getInfo = $conoracle->prepare("select 
											operate_year,
											bf_prncbal,
											item_amt,
											full_prncbal,
											die_prncbal,
											prncbal,
											update_date
										from wcstatement
										where TRIM(deptaccount_no) = :member_no
											and show_flag = 1
											order by operate_year desc");
		$getInfo->execute([':member_no' => $member_no]);
		while($rowDataOra = $getInfo->fetch(PDO::FETCH_ASSOC)){
			$arrInfo = array();
			$arrInfo["YEAR"] = "ปี ". $rowDataOra["OPERATE_YEAR"];
			$arrInfo["BF_PRNCBAL"] = number_format($rowDataOra["BF_PRNCBAL"] ?? "0", 2);
			$arrInfo["ITEM_AMT"] = number_format($rowDataOra["ITEM_AMT"] ?? "0", 2);
			$arrInfo["FULL_PRNCBAL"] = number_format($rowDataOra["FULL_PRNCBAL"] ?? "0", 2);
			$arrInfo["DIE_PRNCBAL"] = number_format($rowDataOra["DIE_PRNCBAL"] ?? "0", 2);
			$arrInfo["PRNCBAL"] = number_format($rowDataOra["PRNCBAL"] ?? "0", 2);
			$arrInfo["UPDATE_DATE"] = $lib->convertdate($rowDataOra["UPDATE_DATE"],'d m Y');
			$arrGroupInfo[] = $arrInfo;
		}
		$arrayResult['LIST'] = $arrGroupInfo;
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