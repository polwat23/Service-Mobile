<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token === 'expired'){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "SQL409";
				$arrayResult['RESPONSE'] = "Invalid RefreshToken is not correct or RefreshToken was expired";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}else{
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}
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
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>