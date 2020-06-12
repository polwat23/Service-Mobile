<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureInfo = $conoracle->prepare("SELECT ist.INSURETYPE_DESC,mp.PRENAME_DESC,ism.INSMEM_NAME,ism.INSMEM_SURNAME,
												ism.INSSTART_DATE,ism.PREMIUM_PAYAMT,ism.INSURANCE_NO,inc.COMPANY_NAME,ism.PREMIUM_AMT,ism.BOUNTY_AMT
												FROM insinsuremaster ism LEFT JOIN insinsuretype ist ON ism.insuretype_code = ist.insuretype_code
												LEFT JOIN mbucfprename mp ON ism.PRENAME_CODE = mp.PRENAME_CODE
												LEFT JOIN insucfcompany inc ON ist.COMPANY_CODE = inc.COMPANY_CODE
												WHERE ism.insurance_status = '1' and ism.member_no = :member_no");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["PAYMENT"] = number_format($rowInsure["PREMIUM_PAYAMT"],2);
			$arrayInsure["PREMIUM_AMT"] = number_format($rowInsure["PREMIUM_AMT"],2);
			$arrayInsure["BOUNTY_AMT"] = number_format($rowInsure["BOUNTY_AMT"],2);
			$arrayInsure["INSURE_DATE"] = $lib->convertdate($rowInsure["INSSTART_DATE"],'D m Y');
			$arrayInsure["INSURE_TYPE"] = $rowInsure["INSURETYPE_DESC"];
			$arrayInsure["INSURE_NO"] = $rowInsure["INSURANCE_NO"];
			$arrayInsure["COMPANY_NAME"] = $rowInsure["COMPANY_NAME"];
			$arrayInsure["FULL_NAME"] = $rowInsure["PRENAME_DESC"].$rowInsure["INSMEM_NAME"].' '.$rowInsure["INSMEM_SURNAME"];
			$arrGroupAllIns[] = $arrayInsure;
		}
		$arrayResult['INSURE'] = $arrGroupAllIns;
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