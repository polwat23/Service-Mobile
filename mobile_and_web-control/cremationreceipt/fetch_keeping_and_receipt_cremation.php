<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipCremation')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrKeepingGrp = array();
		$arrReceiptGrp = array();
		$arrSpecialGrp = array();
		
		$limit_cremation = $func->getConstant("limit_receipt_cremation");
		$getReceiptData = $conoracle->prepare("SELECT * FROM (SELECT kptempreceive.kpslip_no as RECEIPT_NO,wcrecievemonth.CARCASS_AMT,wcrecievemonth.RECV_PERIOD, wcrecievemonth.MEMBER_NO
									FROM wcrecievemonth  left join kptempreceive on  kptempreceive.recv_period = wcrecievemonth.recv_period and kptempreceive.member_no = wcrecievemonth.member_no
									WHERE TRIM(wcrecievemonth.MEMBER_NO) = :member_no AND wcrecievemonth.STATUS_POST  <> -9 ORDER BY wcrecievemonth.RECV_PERIOD DESC) WHERE rownum <= :limit_cremation");
		$getReceiptData->execute([
			':member_no' => $member_no,
			':limit_cremation' => $limit_cremation
		]);
		while($rowReceipt = $getReceiptData->fetch(PDO::FETCH_ASSOC)){
			$getAmtReceiptDiePerson = $conoracle->prepare("SELECT count(CARCASS_AMT) as DIE_AMT FROM wcrecievemonthdetail WHERE member_no  = :member_no and RECV_PERIOD = :recv_period");
			$getAmtReceiptDiePerson->execute([':recv_period' => $rowReceipt["RECV_PERIOD"],
											  ':member_no' => $rowReceipt["MEMBER_NO"]
										]);
			$rowReceiptdie = $getAmtReceiptDiePerson->fetch(PDO::FETCH_ASSOC);
			$arrReceipt["RECEIPT_NO"] = $rowReceipt["RECEIPT_NO"];
			$arrReceipt["RECV_PEIORD_DESC"] = $lib->convertperiodkp(TRIM($rowReceipt["RECV_PERIOD"]));
			$arrReceipt["RECV_PEIORD"] = $rowReceipt["RECV_PERIOD"];
			$arrReceipt["CREMATION_AMT"] = number_format($rowReceipt["CARCASS_AMT"],2);
			$arrReceipt["DIE_AMT"] = $rowReceiptdie["DIE_AMT"].' ราย';
			$arrReceiptGrp[] = $arrReceipt;
		}
		
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
