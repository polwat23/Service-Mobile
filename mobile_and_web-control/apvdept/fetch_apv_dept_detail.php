<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','apv_docno'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		$fetchListApvDept = $conmysql->prepare("SELECT APV_DOCNO,AMOUNT,DEPTACCOUNT_NO,FULL_NAME,OPERATE_DATE,UPDATE_DATE
												FROM gcapvdept
												WHERE apv_docno = :apv_docno");
		$fetchListApvDept->execute([':apv_docno' => $dataComing["apv_docno"]]);
		$rowListApv = $fetchListApvDept->fetch(PDO::FETCH_ASSOC);
		$arrayResult["IS_APV"] = FALSE;
		$arrayResult["APV_DOCNO"] = $rowListApv["APV_DOCNO"];
		$arrayResult["APV_DESC"] = "ทดสอบอนุมัติถอนเงินฝาก";
		$arrayResult["DEPT_AMT"] = number_format($rowListApv["AMOUNT"],2);
		$arrayResult["REQ_NAME"] = $rowListApv["FULL_NAME"];
		$arrayResult["DEPTACCOUNT_NO"] = $lib->formataccount($rowListApv["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
		$arrayResult["REQ_DATE"] = $lib->convertdate($rowListApv["OPERATE_DATE"],'d m Y',true);
		$arrayResult["APV_DATE"] = isset($rowListApv["UPDATE_DATE"]) && $rowListApv["UPDATE_DATE"] != "" ? $lib->convertdate($rowListApv["UPDATE_DATE"],'d m Y') : null;
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
