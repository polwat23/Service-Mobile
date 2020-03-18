<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiary = $conoracle->prepare("SELECT mg.gain_name,mg.gain_surname,mg.gain_addr,mc.gain_concern,mg.remark
												FROM mbgainmaster mg LEFT JOIN mbucfgainconcern mc ON mg.gain_relation = mc.concern_code
												WHERE mg.member_no = :member_no");
		$getBeneficiary->execute([':member_no' => $member_no]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_SHORT"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
			$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["GAIN_ADDR"]);
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
			$arrBenefit["PERCENT"] = filter_var($rowBenefit["REMARK"], FILTER_SANITIZE_NUMBER_INT);
			$arrGroupBNF[] = $arrBenefit;
		}
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>