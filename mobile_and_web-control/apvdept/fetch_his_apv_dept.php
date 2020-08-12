<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		$getUseScoreInApv = $conoracle->prepare("SELECT dpa.apv_docno,dpa.remark,dpa.dept_amt,dpa.approve_date,dpa.deptaccount_no,amu.full_name,dpa.apv_status 
																FROM dpdeptapprovedet dad LEFT JOIN amsecusers amu ON TRIM(dad.apv_id) = amu.user_name 
																LEFT JOIN dpdeptapprove dpa ON dad.APV_DOCNO = dpa.APV_DOCNO
																WHERE amu.member_no = :member_no and dpa.apv_status <> 8 and dpa.approve_date BETWEEN (SYSDATE - 180) and SYSDATE");
		$getUseScoreInApv->execute([
			':member_no' => $member_no
		]);
		while($rowUserScoreInApv = $getUseScoreInApv->fetch(PDO::FETCH_ASSOC)){
			$arrayList = array();
			$arrayList["APV_DOCNO"] = $rowUserScoreInApv["APV_DOCNO"];
			$arrayList["APV_DESC"] = $rowUserScoreInApv["REMARK"];
			$arrayList["DEPT_AMT"] = number_format($rowUserScoreInApv["DEPT_AMT"],2);
			$arrayList["REQ_NAME"] = $rowUserScoreInApv["FULL_NAME"];
			$arrayList["DEPTACCOUNT_NO"] = $lib->formataccount($rowUserScoreInApv["DEPTACCOUNT_NO"],'-');
			$arrayList["APV_DATE"] = isset($rowUserScoreInApv["APPROVE_DATE"]) ? $lib->convertdate($rowUserScoreInApv["APPROVE_DATE"],'d m Y') : null;
			$arrayList["IS_REJECT"] = $rowUserScoreInApv["APV_STATUS"] == '1' ? FALSE : TRUE;
			$arrGrp[] = $arrayList;
		}
		$arrayResult['LIST_APV'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>