<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','full_atm_card_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManageAtm')){

		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conoracle->beginTransaction();
		$updateCanCel = $conoracle->prepare("UPDATE atmmember SET atmhold = '1' WHERE member_no = :member_no and atmcard_id = :atmcard_id");
		if($updateCanCel->execute([
			':member_no' => $member_no,
			':atmcard_id' => $dataComing["full_atm_card_id"]
		])){
			$insertLog = $conoracle->prepare("INSERT INTO atmmember_log(operate_date,member_no,atmcard_id,atmitemtype_code,coop_id,entry_id)
																VALUES(SYSDATE,:member_no,:atmcard_id,'SQM','0010','MobileApp')");
			if($insertLog->execute([
				':member_no' => $member_no,
				':atmcard_id' => $dataComing["full_atm_card_id"]
			])){
				$conoracle->commit();
				$arrayResult["RESULT"] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE_CODE'] = "CANCEL_ATM";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE_CODE'] = "CANCEL_ATM";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
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