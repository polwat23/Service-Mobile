<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no','id_token'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ManagementBankAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,gba.bank_code
													FROM gcbindaccount gba LEFT JOIN gcconstantbankpalette gcpl ON gba.BANK_CODE = gcpl.BANK_CODE and gcpl.is_use = '1'
													LEFT JOIN gcpalettecolor gpl ON gcpl.id_palette = gpl.id_palette and gpl.is_use = '1'
													WHERE gba.member_no = :member_no and gba.id_token = :id_token and gba.bindaccount_status <> '-9'");
		$fetchAccountBeenBind->execute([
			':member_no' => $member_no,
			':id_token' => $payload["id_token"]
		]);
		$arrBindAccount = array();
		while($rowAccountBind = $fetchAccountBeenBind->fetch()){
			$arrBindAccount[] = $rowAccountBind["sigma_key"];
		}
		$arrayResult['BIND_ACCOUNT'] = $arrBindAccount;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
		exit();
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