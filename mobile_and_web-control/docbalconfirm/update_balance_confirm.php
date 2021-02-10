<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conwebmysql = $con->connecttowebmysql();
		$getBalStatus = $conwebmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and confirmation = :confirmdate");
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
		$getMemberName = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,mb.MEMBGROUP_CODE,
												md.SHORT_NAME2 as DEPART_GROUP
												FROM mbmembmaster mb
												LEFT JOIN mbucfdepartment md ON mb.department_code = md.department_code
												LEFT JOIN mbucfprename mp ON mb.PRENAME_CODE = mp.PRENAME_CODE
												WHERE mb.member_no = :member_no");
		$getMemberName->execute([':member_no' => $member_no]);
		$rowname = $getMemberName->fetch(PDO::FETCH_ASSOC);
		$FlagComfirm = $conwebmysql->prepare("INSERT INTO confirm_balance(member_no,fullname,confirmation,confirm_status,reason,membgroup_code,department_code,position_code,comfirmdate,ip)
											VALUES(:member_no,:name,:balance_date,:confirm_flag,:remark,:membgroup,:depart_code,' ',NOW(),:ip)");
		if($FlagComfirm->execute([
			':member_no' => $member_no,
			':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
			':confirm_flag' => $dataComing["confirm_flag"],
			':remark' => 'MobileApp / '.$dataComing["remark"],
			':membgroup' => $rowname["MEMBGROUP_CODE"],
			':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
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
					':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':confirm_flag' => $dataComing["confirm_flag"],
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':membgroup' => $rowname["MEMBGROUP_CODE"],
					':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
					':ip' => $dataComing["ip_address"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
				':member_no' => $member_no,
				':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
				':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				':confirm_flag' => $dataComing["confirm_flag"],
				':remark' => 'MobileApp / '.$dataComing["remark"],
				':membgroup' => $rowname["MEMBGROUP_CODE"],
				':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
				':ip' => $dataComing["ip_address"]
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