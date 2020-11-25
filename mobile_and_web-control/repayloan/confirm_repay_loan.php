<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$is_separate = $func->getConstant("separate_limit_amount_trans_online");
		$getLimitAllDay = $conoracle->prepare("SELECT total_limit FROM atmucftranslimit WHERE tran_desc = 'MOBILE_APP' and tran_status = 1");
		$getLimitAllDay->execute();
		$rowLimitAllDay = $getLimitAllDay->fetch(PDO::FETCH_ASSOC);
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($is_separate){
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP') and SUBSTR(deptitemtype_code,0,1) = 'W'");
		}else{
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP')");
		}
		$getSumAllDay->execute();
		$rowSumAllDay = $getSumAllDay->fetch(PDO::FETCH_ASSOC);
		if(($rowSumAllDay["SUM_AMT"] + $dataComing["amt_transfer"]) > $rowLimitAllDay["TOTAL_LIMIT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0043';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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