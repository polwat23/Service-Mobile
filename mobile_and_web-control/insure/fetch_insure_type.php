<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$sum_insure_amt = 0;
		$fetchinSureInfo = $conoracle->prepare("SELECT ium.INSMEMB_DESC,ism.MARRIGE_NAME,ism.INSCOST_BLANCE,ism.INSGROUP_DATE,ism.STARTSAFE_DATE,ism.ENDSAFE_DATE,
												ism.INSPEROD_PAYMENT,ist.INSCOMPANY_NAME as COMPANY_NAME,ist.INSTYPE_DESC,ilc.MEDICAL_AMT
												FROM insgroupmaster ism LEFT JOIN insurencetype ist ON ism.INSTYPE_CODE = ist.INSTYPE_CODE
												LEFT JOIN insucfmembtype ium ON ism.INSMEMB_TYPE = ium.INSMEMB_TYPE
												LEFT JOIN inslevelcost ilc ON ism.INSTYPE_CODE = ilc.INSTYPE_CODE and ism.LEVEL_CODE = ilc.LEVEL_CODE
												WHERE ism.member_no = :member_no ORDER BY ism.INSTYPE_CODE ASC");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["PAYMENT"] = number_format($rowInsure["INSPEROD_PAYMENT"],2);
			$arrayInsure["PREMIUM_AMT"] = number_format($rowInsure["INSPEROD_PAYMENT"],2);
			if(isset($rowInsure["MEDICAL_AMT"])){
				$arrayInsure["MEDICAL_AMT"] = number_format($rowInsure["MEDICAL_AMT"],2);
			}
			$arrayInsure["INSURE_DATE"] = $lib->convertdate($rowInsure["INSGROUP_DATE"],'D m Y');
			if(isset($rowInsure["ENDSAFE_DATE"])){
				$arrayInsure["STARTSAFE_DATE"] = $lib->convertdate($rowInsure["STARTSAFE_DATE"],'D m Y');
				$arrayInsure["ENDSAFE_DATE"] = $lib->convertdate($rowInsure["ENDSAFE_DATE"],'D m Y');
			}
			$arrayInsure["INSURE_TYPE"] = $rowInsure["INSTYPE_DESC"];
			$arrayInsure["COMPANY_NAME"] = $rowInsure["COMPANY_NAME"];
			$arrayInsure["FULL_NAME"] = $rowInsure["MARRIGE_NAME"];
			$arrayInsure["IS_STM"] = FALSE;
			$sum_insure_amt += $rowInsure["INSPEROD_PAYMENT"];
			$arrGroupAllIns[] = $arrayInsure;
		}
		$arrayResult['SUM_INSURE_AMT'] = number_format($sum_insure_amt,2);
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