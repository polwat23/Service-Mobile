<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManageAtm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getATMCard = $conoracle->prepare("SELECT atmcard_id,atmclose,firstname,surname FROM atmmember WHERE member_no = :member_no");
		$getATMCard->execute([':member_no' => $member_no]);
		$arrCard = array();
		while($rowCard = $getATMCard->fetch(PDO::FETCH_ASSOC)){
			$listCard = array();
			$card_number = (explode("=",$rowCard["ATMCARD_ID"]))[0];
			$listCard["ATM_CARD_ID"] = $card_number;
			$listCard["FULL_ATM_CARD_ID"] = $rowCard["ATMCARD_ID"];
			$listCard["ATM_CARD_ID_FORMAT"] = $lib->formatatmcard($card_number);
			$listCard["ATM_CARD_ID_FORMAT_HIDE"] = $lib->formataccount_hidden_atm($card_number,'xxxx-hhhh-hhhh-xxxx');
			$listCard["ATM_HOLDER"] = $rowCard["FIRSTNAME"].' '.$rowCard["SURNAME"];
			$listCard["ATM_IMG"] = $config["URL_SERVICE"]."resource/utility_icon/atm/atm_card.webp";
			$listCard["IS_SUSPENDED"] = $rowCard["ATMCLOSE"] == '1' ? TRUE : FALSE;
			//$listCard["BALANCE_AMT"] = "10,000.00";
			$arrCard[] = $listCard;
		}
		$arrayResult["ATM_LIST"] = $arrCard;
		$arrayResult["RESULT"] = TRUE;
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