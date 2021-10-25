<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_id = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiary = $conmssqlcoop->prepare("SELECT prefixname  as PRENAME_SHORT,firstname as GAIN_NAME,
											lastname as GAIN_SURNAME,relationship as GAIN_CONCERN,BENEFITRECIPIENTSRATIO
											FROM coBeneficiaries WHERE member_id = :member_no ");
		$getBeneficiary->execute([':member_no' => $member_id]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_SHORT"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
			if(isset($rowBenefit["GAIN_ADDR"])){
				$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["GAIN_ADDR"]);
			}
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
			if($rowBenefit["BENEFITRECIPIENTSRATIO"] > 0 ){
				$arrBenefit["PERCENT_TEXT"] = $rowBenefit["BENEFITRECIPIENTSRATIO"] * 100 .' %';
			}else{
				$arrBenefit["PERCENT_TEXT"] = "ยังไม่ได้กำหนดอัตราส่วนผู้รับผลประโยชน์";
				$arrBenefit["PERCENT_TEXT_PROPS"]["color"] = "red" ;
			}
			
			$arrGroupBNF[] = $arrBenefit;
		}
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
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