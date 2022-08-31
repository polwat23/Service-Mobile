<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','fund_account'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		
		$arrayGroupFund = array();
		$getReceiptFund = $conoracle->prepare("SELECT
			KEEP_YEAR,
			FUNDSLIP_NO,
			FUNDSLIP_DATE,
			DEPTSLIP_AMT,
			FUNDTYPE_CODE
			FROM WCFUNDSLIP
			WHERE ITEM_STATUS = 1
			AND DEPTITEMTYPE_CODE = 'WFD'
			AND TRIM(DEPTACCOUNT_NO) = :member_no
			AND TRIM(FUNDACCOUNT_NO) = :fund_account
			ORDER BY KEEP_YEAR DESC");
		$getReceiptFund->execute([
			':fund_account' => $dataComing['fund_account'],
			':member_no' => $member_no
		]);
		while($rowReceipt = $getReceiptFund->fetch(PDO::FETCH_ASSOC)){
			$arrayReceipt = array();		
			$arrayReceipt["DEPTSLIP_DATE"] = $lib->convertdate($rowReceipt["FUNDSLIP_DATE"],'d m Y');
			$arrayReceipt["DEPTSLIP_NO"] = $rowReceipt["FUNDSLIP_NO"];
			$arrayReceipt["TITLE"] = 'ประจำปี '.$rowReceipt["KEEP_YEAR"];
			$arrayReceipt["RECEIPT_NO"] = $rowReceipt["FUNDSLIP_NO"];
			$arrayReceipt["DEPTSLIP_AMT"] = number_format($rowReceipt["DEPTSLIP_AMT"],2);
			$arrayGroupFund[] = $arrayReceipt;
		}
		
		$getRegister = $conoracle->prepare("
			SELECT  
				WS.DEPTSLIP_AMT,
				WS.FUNDSLIP_NO,   
				WS.FUNDSLIP_DATE

			FROM 
				WCFUNDSLIP WS
				JOIN WCREQFUND WQ ON (WQ.FUNDREQUEST_DOCNO = WS.FUNDACCOUNT_NO AND WQ.FUNDTYPE_CODE = WS.FUNDTYPE_CODE)
					 
			WHERE
				WS.FUNDTYPE_CODE = :fundtype_code
				AND WQ.APPROVE_STATUS = 1
				AND TRIM(WQ.DEPTACCOUNT_NO) = :member_no
		");
		$getRegister->execute([
			':fundtype_code' => $dataComing['fundtype_code'],
			':member_no' => $member_no
		]);
		while($rowRegister = $getRegister->fetch(PDO::FETCH_ASSOC)){
			$arrayRegister = array();		
			$arrayRegister["DEPTSLIP_DATE"] = $lib->convertdate($rowRegister["FUNDSLIP_DATE"],'d m Y');
			$arrayRegister["DEPTSLIP_NO"] = "register";
			$arrayRegister["TITLE"] = "สมัครใหม่";
			$arrayRegister["RECEIPT_NO"] = $rowRegister["FUNDSLIP_NO"];
			$arrayRegister["DEPTSLIP_AMT"] = number_format($rowRegister["DEPTSLIP_AMT"],2);
			$arrayGroupFund[] = $arrayRegister;
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