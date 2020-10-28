<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','apv_docno'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		$fetchScoreSelf = $conoracle->prepare("SELECT aml.APP_SCORE,amu.USER_NAME FROM amsecusers amu LEFT JOIN amsecapvlevel aml ON amu.APVLEVEL_ID = aml.APVLEVEL_ID
															WHERE amu.member_no = :member_no and amu.user_status = 1");
		$fetchScoreSelf->execute([':member_no' => $member_no]);
		$rowScore = $fetchScoreSelf->fetch(PDO::FETCH_ASSOC);
		$fetchListApvDept = $conoracle->prepare("SELECT dpa.apv_docno,dpa.remark,dpa.dept_amt,aml.app_score,dpa.entry_time,dpa.approve_date,dpa.deptaccount_no,amu.full_name
													FROM dpdeptapprove dpa LEFT JOIN amsecapvlevel aml ON dpa.APV_LEVEL = aml.apvlevel_id
													LEFT JOIN amsecusers amu ON TRIM(dpa.user_id) = amu.user_name
													WHERE dpa.apv_docno = :apv_docno");
		$fetchListApvDept->execute([':apv_docno' => $dataComing["apv_docno"]]);
		$rowListApv = $fetchListApvDept->fetch(PDO::FETCH_ASSOC);
		$getUseScoreInApv = $conoracle->prepare("SELECT APV_DOCNO FROM dpdeptapprovedet WHERE apv_docno = :apv_docno and TRIM(apv_id) = :username");
		$getUseScoreInApv->execute([
			':apv_docno' => $rowListApv["APV_DOCNO"],
			':username' => $rowScore["USER_NAME"]
		]);
		$rowUserScoreInApv = $getUseScoreInApv->fetch(PDO::FETCH_ASSOC);
		if(isset($rowUserScoreInApv["APV_DOCNO"]) && $rowUserScoreInApv["APV_DOCNO"] != ""){
			$arrayResult["IS_APV"] = TRUE;
		}else{
			$arrayResult["IS_APV"] = FALSE;
		}
		$arrayResult["APV_DOCNO"] = $rowListApv["APV_DOCNO"];
		$arrayResult["APV_SCORE"] = $rowListApv["APP_SCORE"];
		$arrayResult["APV_DESC"] = $rowListApv["REMARK"];
		$arrayResult["DEPT_AMT"] = number_format($rowListApv["DEPT_AMT"],2);
		$arrayResult["REQ_NAME"] = $rowListApv["FULL_NAME"];
		$arrayResult["DEPTACCOUNT_NO"] = $lib->formataccount($rowListApv["DEPTACCOUNT_NO"],'-');
		$arrayResult["REQ_DATE"] = $lib->convertdate($rowListApv["ENTRY_TIME"],'d m Y',true);
		$arrayResult["APV_DATE"] = isset($rowListApv["APPROVE_DATE"]) && $rowListApv["APPROVE_DATE"] != "" ? $lib->convertdate($rowListApv["APPROVE_DATE"],'d m Y') : null;
		$arrayResult['USER_SCORE'] = $rowScore["APP_SCORE"];
		$arrayResult['USER_ID'] =  $rowScore["USER_NAME"];
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