<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$getBalStatus = $conmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and balance_date = :confirmdate");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':confirmdate' => date('Y-m-d',strtotime($dataComing["balance_date"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["confirm_status"]) && $rowBalStatus["confirm_status"] != ""){
			$arrayResult['RESPONSE_CODE'] = "WS0097";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$getMemberName = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_ENAME
												FROM mbmembmaster mb
												LEFT JOIN mbucfprename mp ON mb.PRENAME_CODE = mp.PRENAME_CODE
												WHERE mb.member_no = :member_no");
		$getMemberName->execute([':member_no' => $member_no]);
		$rowname = $getMemberName->fetch(PDO::FETCH_ASSOC);
		$FlagComfirm = $conmysql->prepare("INSERT INTO confirm_balance(member_no,fullname,balance_date,confirm_status,reason,comfirmdate,ip)
											VALUES(:member_no,:name,:balance_date,:confirm_flag,:remark,NOW(),:ip)");
		if($FlagComfirm->execute([
			':member_no' => $member_no,
			':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_ENAME"],
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
			':confirm_flag' => $dataComing["confirm_flag"],
			':remark' => 'MobileApp / '.$dataComing["remark"],
			':ip' => $dataComing["ip_address"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1038",
				":error_desc" => "Update ลงตาราง  cmconfirmmaster ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $member_no,
					':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_ENAME"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':confirm_flag' => $dataComing["confirm_flag"],
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':ip' => $dataComing["ip_address"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
				':member_no' => $member_no,
				':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_ENAME"],
				':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				':confirm_flag' => $dataComing["confirm_flag"],
				':remark' => 'MobileApp / '.$dataComing["remark"],
				':ip' => $dataComing["ip_address"]
			]);
			//$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1038";
			$arrayResult['BUG'] =$member_no;
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