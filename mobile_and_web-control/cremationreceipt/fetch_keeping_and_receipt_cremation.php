<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipCremation')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrKeepingGrp = array();
		$arrReceiptGrp = array();
		$arrSpecialGrp = array();
		$getKeepingData = $conoracle->prepare("SELECT RECEIPT_NO,CARCASS_AMT,RECV_PERIOD FROM WFRECIEVEMONTH WHERE TRIM(MEMBER_NO) = :member_no AND STATUS_POST = '0'
											ORDER BY RECV_PERIOD DESC");
		$getKeepingData->execute([':member_no' => $member_no]);
		while($rowKeeping = $getKeepingData->fetch(PDO::FETCH_ASSOC)){
			$getAmtDiePerson = $conoracle->prepare("SELECT COUNT(wfaccount_name) AS DIE_AMT FROM WFDEPTMASTER 
													WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
			$getAmtDiePerson->execute([':recv_period' => $rowKeeping["RECV_PERIOD"]]);
			$rowdie = $getAmtDiePerson->fetch(PDO::FETCH_ASSOC);
			$arrKeeping = array();
			$arrKeeping["RECEIVE_NO"] = $rowKeeping["RECEIPT_NO"];
			$arrKeeping["RECV_PEIORD_DESC"] = $lib->convertperiodkp(TRIM($rowKeeping["RECV_PERIOD"]));
			$arrKeeping["RECV_PEIORD"] = $rowKeeping["RECV_PERIOD"];
			$arrKeeping["DIE_AMT"] = $rowdie["DIE_AMT"].' ราย';
			$arrKeeping["CREMATION_AMT"] = number_format($rowKeeping["CARCASS_AMT"],2);
			$arrKeepingGrp[] = $arrKeeping;
		}
		$limit_cremation = $func->getConstant("limit_receipt_cremation");
		$getReceiptData = $conoracle->prepare("SELECT * FROM (SELECT RECEIPT_NO,CARCASS_AMT,RECV_PERIOD FROM WFRECIEVEMONTH 
											WHERE TRIM(MEMBER_NO) = :member_no AND STATUS_POST = '1' ORDER BY RECV_PERIOD DESC) WHERE rownum <= :limit_cremation");
		$getReceiptData->execute([
			':member_no' => $member_no,
			':limit_cremation' => $limit_cremation
		]);
		while($rowReceipt = $getReceiptData->fetch(PDO::FETCH_ASSOC)){
			$getAmtReceiptDiePerson = $conoracle->prepare("SELECT COUNT(wfaccount_name) AS DIE_AMT FROM WFDEPTMASTER 
													WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
			$getAmtReceiptDiePerson->execute([':recv_period' => $rowReceipt["RECV_PERIOD"]]);
			$rowReceiptdie = $getAmtReceiptDiePerson->fetch(PDO::FETCH_ASSOC);
			$arrReceipt["RECEIPT_NO"] = $rowReceipt["RECEIPT_NO"];
			$arrReceipt["RECV_PEIORD_DESC"] = $lib->convertperiodkp(TRIM($rowReceipt["RECV_PERIOD"]));
			$arrReceipt["RECV_PEIORD"] = $rowReceipt["RECV_PERIOD"];
			$arrReceipt["CREMATION_AMT"] = number_format($rowReceipt["CARCASS_AMT"],2);
			$arrReceipt["DIE_AMT"] = $rowReceiptdie["DIE_AMT"].' ราย';
			$arrReceiptGrp[] = $arrReceipt;
		}
		$getReceiptSpecial = $conoracle->prepare("SELECT wfd.DEPTSLIP_AMT,wfd.DEPTSLIP_NO,wfd.DEPTSLIP_DATE
												FROM wfdeptslip wfd LEFT JOIN wfdeptmaster wfm ON wfd.DEPTACCOUNT_NO = wfm.DEPTACCOUNT_NO 
												WHERE TRIM(wfm.member_no) = :member_no");
		$getReceiptSpecial->execute([':member_no' => $member_no]);
		while($rowReceiptSpecial = $getReceiptSpecial->fetch(PDO::FETCH_ASSOC)){
			$arrayReceiptSpe = array();
			$arrayReceiptSpe["RECEIPT_NO"] = TRIM($rowReceiptSpecial["DEPTSLIP_NO"]);
			$arrayReceiptSpe["IS_EXTRA"] = TRUE;
			$arrayReceiptSpe["CREMATION_AMT"] = number_format($rowReceiptSpecial["DEPTSLIP_AMT"],2);
			$arrayReceiptSpe["RECV_PEIORD_DESC"] = $lib->convertdate($rowReceiptSpecial["DEPTSLIP_DATE"],'d m Y');
			$arrSpecialGrp[] = $arrayReceiptSpe;
		}
		
		$arrayResult["RECEIPT_EXTRA"] = $arrSpecialGrp;
		$arrayResult["KEEPING"] = $arrKeepingGrp;
		$arrayResult["RECEIPT"] = $arrReceiptGrp;
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
