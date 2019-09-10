<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["member_no"]) 
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "SQL409";
			$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}else{
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'BeneficiaryInfo')){
		if($dataComing["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_BENEFICIARY"];
		}else if($dataComing["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_BENEFICIARY"];
		}else{
			$member_no = $dataComing["member_no"];
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
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>