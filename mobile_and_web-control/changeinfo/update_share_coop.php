<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['unique_id','share'],$dataComing)) {
	if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'SettingMemberInfo')) {
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
	
		
		$UpdateShare = $conoracle->prepare("UPDATE mbreqappl SET coopstk_amt = :coopstk_amt , share_member =:share_member, biz_year =:biz_year WHERE member_no = :member_no");
		if($UpdateShare->execute([':member_no' => $member_no,
								  ':coopstk_amt' => $dataComing["coopstk_amt"],
								  ':share_member' => $dataComing["share"],
								  ':biz_year' => $dataComing["biz_year"]
			])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult["RESULT"] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	} else {
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
} else {
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ " . "\n" . json_encode($dataComing),
		":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
	];
	$log->writeLog('errorusage', $logStruc);
	$message_error = "ไฟล์ " . $filename . " ส่ง Argument มาไม่ครบมาแค่ " . "\n" . json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
