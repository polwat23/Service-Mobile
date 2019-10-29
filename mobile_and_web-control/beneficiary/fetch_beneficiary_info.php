<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'BeneficiaryInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_BENEFICIARY"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_BENEFICIARY"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupBNF = array();
		$getBeneficiary = $conoracle->prepare("SELECT mp.prename_short,mg.gain_name,mg.gain_surname,mg.gain_addr,mg.gain_relation
												FROM mbgainmaster mg LEFT JOIN mbucfprename mp ON mg.prename_code = mp.prename_code
												WHERE member_no = :member_no");
		$getBeneficiary->execute([':member_no' => $member_no]);
		while($rowBenefit = $getBeneficiary->fetch()){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_SHORT"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
			$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["GAIN_ADDR"]);
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_RELATION"];
			$arrGroupBNF[] = $arrBenefit;
		}
		if(sizeof($arrGroupBNF) > 0 || isset($new_token)){
			$arrayResult['BENEFICIARY'] = $arrGroupBNF;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>