<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_BENEFICIARY"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_BENEFICIARY"];
		}else{
			$member_no = $payload["member_no"];
		}
		$getBeneficiary = $conoracle->prepare("SELECT fm.base64_img,fmt.mimetypes,fm.data_type FROM fomimagemaster fm LEFT JOIN fomucfmimetype fmt ON fm.data_type = fmt.typefile
												where fm.system_code = 'mbshr' and fm.column_name = 'member_no' 
												and fm.column_data = :member_no and fm.img_type_code = '003' and fm.seq_no = 1");
		$getBeneficiary->execute([':member_no' => $member_no]);
		$rowBenefit = $getBeneficiary->fetch();
		$DataURLBase64 = isset($rowBenefit["BASE64_IMG"]) ? "data:".$rowBenefit["MIMETYPES"].";base64,".base64_encode(stream_get_contents($rowBenefit["BASE64_IMG"])) : null;
		if((isset($DataURLBase64) && $DataURLBase64 != '') || isset($new_token)){
			$arrayResult['DATA_TYPE'] = $rowBenefit["DATA_TYPE"] ?? 'pdf';
			$arrayResult['BENEFICIARY'] = $DataURLBase64;
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