<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_list','balance_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$insertConfirm = $conmysql->prepare("INSERT INTO gcconfirmbalancelist(member_no,confirmdep_list,confirmlon_list,confirmshr_list,balance_date)
											VALUES(:member_no,:confirmdep_list,:confirmlon_list,:confirmshr_list,:balance_date)");
		if($insertConfirm->execute([
			':member_no' => $payload["member_no"],
			':confirmdep_list' => json_encode($dataComing["confirm_list"]["DEP"]),
			':confirmlon_list' => json_encode($dataComing["confirm_list"]["LON"]),
			':confirmshr_list' => json_encode($dataComing["confirm_list"]["SHR"]),
			':balance_date' => $dataComing["balance_date"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1038",
				":error_desc" => "INSERT ลงตาราง  gcconfirmbalancelist ไม่ได้ "."\n".$insertConfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $payload["member_no"],
					':confirmdep_list' => json_encode($dataComing["confirm_list"]["DEP"]),
					':confirmlon_list' => json_encode($dataComing["confirm_list"]["LON"]),
					':confirmshr_list' => json_encode($dataComing["confirm_list"]["SHR"]),
					':balance_date' => $dataComing["balance_date"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." INSERT ลงตาราง  gcconfirmbalancelist ไม่ได้"."\n".$insertConfirm->queryString."\n"."data => ".json_encode([
				':member_no' => $payload["member_no"],
				':confirmdep_list' => json_encode($dataComing["confirm_list"]["DEP"]),
				':confirmlon_list' => json_encode($dataComing["confirm_list"]["LON"]),
				':confirmshr_list' => json_encode($dataComing["confirm_list"]["SHR"]),
				':balance_date' => $dataComing["balance_date"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1038";
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