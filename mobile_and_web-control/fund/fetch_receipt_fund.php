<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','fund_account'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$limit = $func->getConstant('show_receiptfun_limit');
		$arrayGroupFund = array();
		$getReceiptFund = $conmssql->prepare("SELECT TOP ".$limit." DEPTSLIP_DATE , DEPTSLIP_NO , 
											(CASE WHEN CASH_TYPE = 'CSH'  THEN 'ชำระเงินสด'   WHEN CASH_TYPE ='TRN'  THEN 'ชำระเงินโอภายใน' 
											WHEN CASH_TYPE ='CBT' THEN 'ชำระเงินโอนธนาคาร' ELSE '' END )  AS RECEIPT,
											DEPTSLIP_AMT 
											FROM WCDEPTSLIP WHERE DEPTACCOUNT_NO = :fund_account AND item_status = 1 ");
		$getReceiptFund->execute([':fund_account' => $dataComing["fund_account"]]);
		while($rowReceipt = $getReceiptFund->fetch(PDO::FETCH_ASSOC)){
			$arrayReceipt = array();		
			$arrayReceipt["DEPTSLIP_DATE"] = $lib->convertdate($rowReceipt["DEPTSLIP_DATE"],'d m Y');
			$arrayReceipt["DEPTSLIP_NO"] = $rowReceipt["DEPTSLIP_NO"];
			$arrayReceipt["RECEIPT"] = $rowReceipt["RECEIPT"];
			$arrayReceipt["DEPTSLIP_AMT"] = $rowReceipt["DEPTSLIP_AMT"];
			$arrayGroupFund[] = $arrayReceipt;
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