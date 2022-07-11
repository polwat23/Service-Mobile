<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureInfo = $conoracle->prepare("SELECT INST.INSCOMPANY_NAME,INS.INSCOST_BLANCE,INS.LOANCONTRACT_NO,INS.EXPENSE_ACCID,
												INS.STARTSAFE_DATE AS START_SAFE,
												INS.ENDSAFE_DATE AS END_SAFE 
												FROM INSGROUPMASTER INS 
												LEFT JOIN INSURENCETYPE INST ON INS.INSTYPE_CODE = INST.INSTYPE_CODE 
												where INS.member_no = :member_no and INS.ins_status = 1");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["COMPANY_NAME"] = "เลขที่ : ".$rowInsure["LOANCONTRACT_NO"];
			$arrayInsure["DEPTACCOUNT_NO"] = $lib->formataccount($rowInsure["EXPENSE_ACCID"],$func->getConstant('dep_format'));
			$arrayInsure["PROTECT_AMT"] = number_format($rowInsure["INSCOST_BLANCE"],2);
			$arrayInsure["STARTSAFE_DATE"] = $lib->convertdate($rowInsure["START_SAFE"],'D m Y');
			$arrayInsure["ENDSAFE_DATE"] = $lib->convertdate($rowInsure["END_SAFE"],'D m Y');
			$arrayInsure["INSURE_TYPE"] = $rowInsure["INSCOMPANY_NAME"];
			$arrayInsure["IS_STM"] = FALSE;
			$arrGroupAllIns[] = $arrayInsure;
		}
		$arrayResult['INSURE'] = $arrGroupAllIns;
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