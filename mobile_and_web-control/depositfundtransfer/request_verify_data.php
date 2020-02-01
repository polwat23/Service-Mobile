<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		if($payload["member_no"] == "dev@mode" || $payload["member_no"] == "salemode"){
			$member_no = $configAS[$payload["member_no"]];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchMemberName = $conoracle->prepare("SELECT MEMB_NAME,MEMB_SURNAME FROM MBMEMBMASTER WHERE member_no = :member_no");
		$fetchMemberName->execute([
			':member_no' => $member_no
		]);
		$rowMember = $fetchMemberName->fetch();
		$account_name_th = $rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
		$arrayResult['FEE_AMT'] = 0;
		$arrayResult['ACCOUNT_NAME'] = $account_name_th;
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