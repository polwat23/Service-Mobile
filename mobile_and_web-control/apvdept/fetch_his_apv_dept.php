<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		$getUseScoreInApv = $conmysql->prepare("SELECT APV_DOCNO,AMOUNT,DEPTACCOUNT_NO,FULL_NAME,STATUS,UPDATE_DATE
												FROM gcapvdept WHERE status <> '8'");
		$getUseScoreInApv->execute();
		while($rowUserScoreInApv = $getUseScoreInApv->fetch(PDO::FETCH_ASSOC)){
			$arrayList = array();
			$arrayList["APV_DOCNO"] = $rowUserScoreInApv["APV_DOCNO"];
			$arrayList["APV_DESC"] = "ทดสอบอนุมัติถอนเงินฝาก";
			$arrayList["DEPT_AMT"] = number_format($rowUserScoreInApv["AMOUNT"],2);
			$arrayList["REQ_NAME"] = $rowUserScoreInApv["FULL_NAME"];
			$arrayList["DEPTACCOUNT_NO"] = $lib->formataccount($rowUserScoreInApv["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrayList["APV_DATE"] = isset($rowUserScoreInApv["UPDATE_DATE"]) ? $lib->convertdate($rowUserScoreInApv["UPDATE_DATE"],'d m Y') : null;
			$arrayList["IS_REJECT"] = $rowUserScoreInApv["STATUS"] == '9' ? TRUE : FALSE;
			$arrGrp[] = $arrayList;
		}
		$arrayResult['LIST_APV'] = $arrGrp;
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
