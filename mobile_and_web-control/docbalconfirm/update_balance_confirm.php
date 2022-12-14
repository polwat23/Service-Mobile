<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	
		$getBalStatus = $conmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and balance_date = :balance_date");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["confirm_status"]) && $rowBalStatus["confirm_status"] != ""){
			$arrayResult['RESPONSE_CODE'] = "WS0097";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		
		$getBalanceDetail = $conmssql->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
												WHERE member_no = :member_no and balance_date = :balance_date and FROM_SYSTEM NOT IN('GRT')
												ORDER BY SEQ_NO ASC");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' =>  date('m/d/y',strtotime($dataComing["balance_date"]))
		]);
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$getFullname = $conmssql->prepare("SELECT mp.PRENAME_SHORT,mb.MEMB_NAME,mb.MEMB_SURNAME
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.member_no = :member_no ");
			$getFullname->execute([':member_no' => $member_no]);
			$rowName = $getFullname->fetch(PDO::FETCH_ASSOC);
			$arrBalDetail["FULL_NAME"] = $rowName["PRENAME_SHORT"].''.$rowName["MEMB_NAME"].'	'.$rowName["MEMB_SURNAME"];
			if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
				$arrBalDetail["TYPE_DESC"] = 'เงินรับฝาก';
				$arrBalDetail["DEPTACCOUNT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
				$arrBalDetail["TYPE_DESC"] = 'เงินกู้';
				$arrBalDetail["LOANCONTRACT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
				$arrBalDetail["TYPE_DESC"] = 'หุ้นปกติ';
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else{
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
				$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
			}
			$arrDetail[] = $arrBalDetail;
		}
		$conmysql->beginTransaction();
		foreach ($arrDetail as  $dataArr) {
			
			$FlagComfirm = $conmysql->prepare("INSERT INTO confirm_balance(member_no,full_name,type_desc,balance_amt ,deptaccount_no,	loancontract_no,confirm_remark, confirm_status ,balance_date, ip_address)
											VALUES(:member_no,:full_name,:type_desc,:balance_amt ,:deptaccount_no,:loancontract_no,:remark,:confirm_flag,:balance_date,:ip_address)");
			if($FlagComfirm->execute([
					':member_no' => $member_no,
					':full_name' => $dataArr["FULL_NAME"],
					':type_desc' => $dataArr["TYPE_DESC"],
					':balance_amt' => $dataArr["BALANCE_AMT"],
					':deptaccount_no' => $dataArr["DEPTACCOUNT_NO"],
					':loancontract_no' => $dataArr["LOANCONTRACT_NO"],
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':confirm_flag' => $dataComing["confirm_flag"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':ip_address' => $dataComing["ip_address"] == 'unknown' ? ($_SERVER['HTTP_X_REAL_IP'] ?? null) : ($dataComing["ip_address"] ?? null)
				])){
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1038",
					":error_desc" => "Update ลงตาราง  cmconfirmmaster ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
						':member_no' => $member_no,
						':balance_share' => $dataArr["BALANCE_SHARE"],
						':deptaccount_no' => $dataArr["DEPTACCOUNT_NO"],
						':loancontract_no' => $dataArr["LOANCONTRACT_NO"],
						':remark' => 'MobileApp / '.$dataComing["remark"],
						':confirm_flag' => $dataComing["confirm_flag"],
						':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),					
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $member_no,
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':confirm_flag' => $dataComing["confirm_flag"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1038";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		$conmysql->commit();
		$arrayResult['RESULT'] = TRUE;
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