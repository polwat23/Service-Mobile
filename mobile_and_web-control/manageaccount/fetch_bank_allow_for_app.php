<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$arrayBankGrp = array();
		$getBankAllow = $conoracle->prepare("SELECT bank_code,bank_name,bank_short_name,bank_short_ename,bank_logo_path
											FROM csbankdisplay");
		$getBankAllow->execute();
		while($rowAllow = $getBankAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayBank = array();
			$arrayBank["IS_BIND"] = FALSE;
			$checkRegis = $conoracle->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank,bank_account_name,bank_account_name_en FROM gcbindaccount 
											WHERE bank_code = :bank_code and member_no = :member_no and bindaccount_status = '1'");
			$checkRegis->execute([
				':bank_code' => $rowAllow["BANK_CODE"],
				':member_no' => $payload["member_no"]
			]);
			$rowRegis = $checkRegis->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRegis["DEPTACCOUNT_NO_COOP"])){			
				$arrayBank["IS_BIND"] = TRUE;
				$arrayBank["COOP_ACCOUNT_NO"] = $rowRegis["DEPTACCOUNT_NO_COOP"];
				$arrayBank["BANK_ACCOUNT_NO"] = $rowRegis["DEPTACCOUNT_NO_BANK"];
				if($lang_locale == 'th'){
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["BANK_ACCOUNT_NAME"];
				}else{
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["BANK_ACCOUNT_NAME_EN"];
				}
			}
			$arrayBank["BANK_CODE"] = $rowAllow["BANK_CODE"];
			$arrayBank["BANK_NAME"] = $rowAllow["BANK_NAME"];
			$arrayBank["BANK_SHORT_NAME"] = $rowAllow["BANK_SHORT_NAME"];
			$arrayBank["BANK_SHORT_ENAME"] = $rowAllow["BANK_SHORT_ENAME"];
			$arrayBank["BANK_LOGO_PATH"] = $config["URL_SERVICE"].$rowAllow["BANK_LOGO_PATH"];
			$arrPic = explode('.',$rowAllow["BANK_LOGO_PATH"]);
			$arrayBank["BANK_LOGO_PATH_WEBP"] = $config["URL_SERVICE"].$arrPic[0].'.webp';
			$arrayBankGrp[] = $arrayBank;
		}
		$arrayResult['BANK_LIST'] = $arrayBankGrp;
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
	echo json_encode($arrayResult);
	exit();
}
?>