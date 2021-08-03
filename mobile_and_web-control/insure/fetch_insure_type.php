<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureInfo = $conmssql->prepare("SELECT IGM.PERIODPAY_AMT AS INSURE_AMT,IGM.INSCOST_BLANCE AS PROTECT_AMT,IST.INSCOMPANY_NAME AS COMPANY_NAME,
												IGM.STARTSAFE_DATE as PROTECTSTART_DATE,IGM.ENDSAFE_DATE as PROTECTEND_DATE
												FROM INSGROUPMASTER IGM LEFT JOIN INSURENCETYPE IST ON IGM.INSTYPE_CODE = IST.INSTYPE_CODE
												WHERE IGM.MEMBER_NO = :member_no");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["INSURE_NO"] = $rowInsure["COMPANY_NAME"];
			$arrayInsure["PREMIUM_AMT"] = number_format($rowInsure["INSURE_AMT"],2);
			$arrayInsure["PROTECT_AMT"] = number_format($rowInsure["PROTECT_AMT"],2);
			$arrayInsure["STARTSAFE_DATE"] = $lib->convertdate($rowInsure["PROTECTSTART_DATE"],'D m Y');
			$arrayInsure["ENDSAFE_DATE"] = $lib->convertdate($rowInsure["PROTECTEND_DATE"],'D m Y');
			$arrayInsure["INSURE_TYPE"] = $rowInsure["COMPANY_NAME"];
			$arrayInsure["COMPANY_NAME"] = $rowInsure["COMPANY_NAME"];
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