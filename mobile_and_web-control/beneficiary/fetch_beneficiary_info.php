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
		$getBeneficiary = $conoracle->prepare("select base64_img,data_type from fomimagemaster 
												where system_code = 'mbshr' and column_name = 'member_no' 
												and column_data = :member_no and img_type_code = '003' and seq_no = 1");
		$getBeneficiary->execute([':member_no' => $member_no]);
		$rowBenefit = $getBeneficiary->fetch();
		if($rowBenefit["DATA_TYPE"] == 'pdf'){
			$rowBase64 = isset($rowBenefit["BASE64_IMG"]) ? "data:application/pdf;base64,".base64_encode(stream_get_contents($rowBenefit["BASE64_IMG"])) : null;
		}else if($rowBenefit["DATA_TYPE"] == 'png' || $rowBenefit["DATA_TYPE"] == 'jpg' || $rowBenefit["DATA_TYPE"] == 'jpeg'){
			$rowBase64 = isset($rowBenefit["BASE64_IMG"]) ? "data:image/".$rowBenefit["DATA_TYPE"].";base64,".base64_encode(stream_get_contents($rowBenefit["BASE64_IMG"])) : null;
		}else{
			$rowBase64 = isset($rowBenefit["BASE64_IMG"]) ? "data:application/pdf;base64,".base64_encode(stream_get_contents($rowBenefit["BASE64_IMG"])) : null;
		}
		if((isset($rowBase64) && $rowBase64 != '') || isset($new_token)){
			$arrayResult['DATA_TYPE'] = $rowBenefit["DATA_TYPE"] ?? 'pdf';
			$arrayResult['BENEFICIARY'] = $rowBase64;
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
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>