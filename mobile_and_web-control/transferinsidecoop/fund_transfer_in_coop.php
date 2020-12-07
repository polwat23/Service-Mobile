<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','penalty_amt','trans_ref_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$ref_no = date('YmdHis').substr($from_account_no,-3);
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$amt_transfer = $dataComing["amt_transfer"] - $dataComing["penalty_amt"];
		$getMemberNo = $conmysql->prepare("SELECT member_no FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no");
		$getMemberNo->execute([':deptaccount_no' => $to_account_no]);
		$rowMember_noDest = $getMemberNo->fetch(PDO::FETCH_ASSOC);
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$member_no_dest = $configAS[$rowMember_noDest["member_no"]] ?? $rowMember_noDest["member_no"];
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["TransferRefCode"] = $dataComing["trans_ref_code"];
		$arrDataAPI["FromCoopAccountNo"] = $from_account_no;
		$arrDataAPI["ToMemberID"] = substr($member_no_dest,-6);
		$arrDataAPI["ToCoopAccountNo"] = $to_account_no;
		$arrDataAPI["TransferAmount"] = $dataComing["amt_transfer"];
		$arrDataAPI["UserRequestDate"] = $dateOperC;
		$arrDataAPI["Note"] = "Transfer inside coop from mobile";
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/TransferCOOP",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/TransferCOOP",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/TransferCOOP";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															ref_no_1,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTB',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1',:operate_date,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':operate_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $from_account_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $dataComing["trans_ref_code"]
			]);
			$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
												VALUES(:remark,:deptaccount_no,:seq_no)");
			$insertRemark->execute([
				':remark' => $dataComing["remark"] ?? null,
				':deptaccount_no' => $from_account_no,
				':seq_no' => $dataComing["trans_ref_code"]
			]);
			$arrToken = $func->getFCMToken('person',$payload["member_no"]);
			$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
			foreach($arrToken["LIST_SEND"] as $dest){
				if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
					$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
					$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
					$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
					$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
					$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
					$arrMessage["BODY"] = $message_endpoint["BODY"];
					$arrMessage["PATH_IMAGE"] = null;
					$arrPayloadNotify["PAYLOAD"] = $arrMessage;
					$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
					$arrPayloadNotify["SEND_BY"] = "system";
					if($lib->sendNotify($arrPayloadNotify,"person")){
						$func->insertHistory($arrPayloadNotify,'2');
					}
				}
			}
			$arrayResult['TRANSACTION_NO'] = $ref_no;
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,id_userlogin)
															VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:id_userlogin)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':operate_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $from_account_no,
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$arrayResult["RESPONSE_CODE"] = 'WS8001';
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '2',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrResponseAPI->responseMessage
				];
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '1',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrResponseAPI->responseMessage
				];
			}
			$log->writeLog('transferinside',$arrayStruc);
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
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
		":error_code" => "WS1016",
		":error_desc" => "รีเซ็ต Pin ไม่ได้ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไม่สามารถรีเซ็ต PIN ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateResetPin->queryString."\n"."Param => ". json_encode([
		':member_no' => $payload["member_no"]
	]);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS1016";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	require_once('../../include/exit_footer.php');
	
}
?>