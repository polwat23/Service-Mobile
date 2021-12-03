<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiaryReg = $conmysql->prepare("SELECT reqdoc_no, form_value, document_url, update_date 
										FROM gcreqdoconline 
										WHERE documenttype_code = 'RRGT' and member_no = :member_no and req_status = '1' ORDER BY update_date desc LIMIT 1");
		$getBeneficiaryReg->execute([':member_no' => $payload["member_no"]]);
		while($rowBeneficiaryReg = $getBeneficiaryReg->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["REQDOC_NO"] = $rowBeneficiaryReg["reqdoc_no"];
			$arrBenefit["FORM_VALUE"] = $rowBeneficiaryReg["form_value"];
			$arrBenefit["DOCUMENT_URL"] = $rowBeneficiaryReg["document_url"];
			$arrBenefit["UPDATE_DATE"] = $lib->convertdate($rowBeneficiaryReg["update_date"],"D m Y",true);
			$arrGroupBNF[] = $arrBenefit;
		}
		
		if($arrGroupBNF == 0){
			$getBeneficiary = $conmysql->prepare("SELECT reqdoc_no, form_value, document_url, update_date 
											FROM gcreqdoconline 
											WHERE documenttype_code = 'CBNF' and member_no = :member_no and req_status = '1' ORDER BY update_date desc LIMIT 1");
			$getBeneficiary->execute([':member_no' => $payload["member_no"]]);
			while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
				$arrBenefit = array();
				$arrBenefit["REQDOC_NO"] = $rowBenefit["reqdoc_no"];
				$arrBenefit["FORM_VALUE"] = $rowBenefit["form_value"];
				$arrBenefit["DOCUMENT_URL"] = $rowBenefit["document_url"];
				$arrBenefit["UPDATE_DATE"] = $lib->convertdate($rowBenefit["update_date"],"D m Y",true);
				$arrGroupBNF[] = $arrBenefit;
			}
		}
		
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
		$arrGroupRemark = array();
		$arrGroupRemark["1"] = 'ตามส่วนเท่ากัน';
		$arrGroupRemark["2"] = 'ตามลำดับก่อนหลัง';
		$arrGroupRemark["3"] = '';
		$arrayResult['REMARK_OPTION'] = $arrGroupRemark;
		$arrayResult['IS_REQFORM'] = TRUE;
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