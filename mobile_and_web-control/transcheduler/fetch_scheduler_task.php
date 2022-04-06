<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScheduleList')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayTransfer = array();
		$arrayRepayLoan = array();
		$arrayBuyShare = array();
		$arrayDeposit = array();
		$arrayWithdraw = array();
		$formatDept = $func->getConstant('dep_format');
		$getTaskSchedulerList = $conmysql->prepare("SELECT id_transchedule,transaction_type,from_account,destination,scheduler_date,transaction_date,amt_transfer,
													bank_code,scheduler_status,scheduler_type,end_date FROM gctransactionschedule 
													WHERE member_no = :member_no");
		$getTaskSchedulerList->execute([':member_no' => $payload["member_no"]]);
		while($rowTask = $getTaskSchedulerList->fetch(PDO::FETCH_ASSOC)){
			$arrayTaskList = array();
			$arrayTaskList["ID_TRANSCHEDULE"] = $rowTask["id_transchedule"];
			$arrayTaskList["SCHEDULER_TYPE"] = $rowTask["scheduler_type"];
			$arrayTaskList["SCHEDULER_STATUS"] = $rowTask["scheduler_status"];
			if($rowTask["scheduler_type"] == '2'){
				$arrayTaskList["START_DATE"] = $lib->convertdate($rowTask["scheduler_date"],'d M Y');
				$arrayTaskList["END_DATE"] = !empty($rowTask["end_date"]) ? $lib->convertdate($rowTask["end_date"],'d M Y') : NULL;
			}
			$arrayTaskList["SCHEDULER_DATE"] = $lib->convertdate($rowTask["scheduler_date"],'d M Y');
			if($rowTask["scheduler_status"] == '1' && $rowTask["scheduler_status"] == '-99'){
				$arrayTaskList["TRANSACTION_DATE"] = $lib->convertdate($rowTask["transaction_date"],'d M Y');
			}
			$arrayTaskList["AMT_TRANSFER"] = number_format($rowTask["amt_transfer"],2);
			if($rowTask["transaction_type"] == '1'){
				$arrayTaskList["FROM_ACCOUNT"] = $lib->formataccount($rowTask["from_account"],$formatDept);
				$arrayTaskList["DESTINATION"] = $lib->formataccount($rowTask["destination"],$formatDept);
				$dataDep = $cal_dep->getConstantAcc($rowTask["destination"]);
				$arrayTaskList["DESTINATION_ACCOUNT_NAME"] = $dataDep["DEPTACCOUNT_NAME"];
				$arrayTransfer[] = $arrayTaskList;
			}else if($rowTask["transaction_type"] == '2'){
				if(isset($rowTask["bank_code"])){
					$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,csb.bank_short_name,csb.bank_logo_path,gba.bank_account_name
																FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
																WHERE gba.id_bindaccount = :id_bindaccount and gba.bindaccount_status = '1'");
					$fetchAccountBeenBind->execute([
						':id_bindaccount' => $rowTask["from_account"]
					]);
					if($fetchAccountBeenBind->rowCount() > 0){
						$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
						if($rowTask["bank_code"] == '025'){
							$arrayTaskList["FROM_ACCOUNT"] = $rowFetchAcc["deptaccount_no_bank"];
						}else{
							$arrayTaskList["FROM_ACCOUNT"] = $lib->formataccount($rowFetchAcc["deptaccount_no_bank"],$formatDept);
						}
					}
				}else{
					$arrayTaskList["FROM_ACCOUNT"] = $lib->formataccount($rowTask["from_account"],$formatDept);
				}
				$arrayTaskList["DESTINATION"] = $rowTask["destination"];
				$dataLoan = $cal_loan->getContstantLoanContract($rowTask["destination"]);
				$arrayTaskList["LOANTYPE_DESC"] = $dataLoan["LOANTYPE_DESC"];
				$arrayRepayLoan[] = $arrayTaskList;
			}else if($rowTask["transaction_type"] == '3'){
				$arrayTaskList["FROM_ACCOUNT"] = $lib->formataccount($rowTask["from_account"],$formatDept);
				$arrayTaskList["DESTINATION"] = $rowTask["destination"];
				$arrayBuyShare[] = $arrayTaskList;
			}else if($rowTask["transaction_type"] == '4'){
				$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,csb.bank_short_name,csb.bank_logo_path,gba.bank_account_name
															FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
															WHERE gba.id_bindaccount = :id_bindaccount and gba.bindaccount_status = '1'");
				$fetchAccountBeenBind->execute([
					':id_bindaccount' => $rowTask["from_account"]
				]);
				if($fetchAccountBeenBind->rowCount() > 0){
					$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					$arrayTaskList["FROM_ACCOUNT"] = $rowFetchAcc["deptaccount_no_bank"];
					$arrayTaskList["DESTINATION"] = $lib->formataccount($rowTask["destination"],$formatDept);
					$arrayTaskList["BANK_CODE"] = $rowTask["bank_code"];
					$arrayTaskList["DESTINATION_ACCOUNT_NAME"] = $rowFetchAcc["bank_account_name"];
					$arrayDeposit[] = $arrayTaskList;
				}
			}else if($rowTask["transaction_type"] == '5'){
				$arrayTaskList["FROM_ACCOUNT"] = $lib->formataccount($rowTask["from_account"],$formatDept);
				$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,csb.bank_short_name,csb.bank_logo_path,gba.bank_account_name
															FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
															WHERE gba.id_bindaccount = :id_bindaccount and gba.bindaccount_status = '1'");
				$fetchAccountBeenBind->execute([
					':id_bindaccount' => $rowTask["destination"]
				]);
				if($fetchAccountBeenBind->rowCount() > 0){
					$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					$arrayTaskList["DESTINATION"] = $rowFetchAcc["deptaccount_no_bank"];
					$arrayTaskList["BANK_CODE"] = $rowTask["bank_code"];
					$arrayTaskList["DESTINATION_ACCOUNT_NAME"] = $rowFetchAcc["bank_account_name"];
					$arrayTaskList["BANK_NAME"] = $rowFetchAcc["bank_short_name"];
					$arrayTaskList["IMG"] = $config["URL_SERVICE"].$rowFetchAcc["bank_logo_path"];
					$arrayWithdraw[] = $arrayTaskList;
				}
			}
		}
		$arrayResult['TRANSFER'] = $arrayTransfer;
		$arrayResult['REPAYLOAN'] = $arrayRepayLoan;
		//$arrayResult['BUYSHARE'] = $arrayBuyShare;
		$arrayResult['DEPOSIT'] = $arrayDeposit;
		$arrayResult['WITHDRAW'] = $arrayWithdraw;
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