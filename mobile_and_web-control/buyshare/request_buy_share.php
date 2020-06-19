<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$from_account_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			$dateOperC = date('c');
			$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$arrGroup = array();
			$arrGroupDep = array();
			$arrGroup["coop_id"] = $config["COOP_ID"];
			$arrGroup["slip_amt"] = $dataComing["amt_transfer"];
			$arrGroup["entry_id"] = "mobile_app";
			$arrGroup["member_no"] = $member_no;
			$arrGroup["slip_date"] = $dateOperC;
			$ref_no = time().$lib->randomText('all',3);
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_shrsave" => $arrGroup,
					"dept_inf_serv" => $arrGroupDep
				];
				$resultWS = $clientWS->__call("of_saveslip_share_mobile", array($argumentWS));
				$responseSh = $resultWS->of_saveslip_share_mobileResult;
				
				if($responseSh->msg_output == '0000'){
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																,amount,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																id_userlogin,ref_no_source)
																VALUES(:ref_no,'WTX',:from_account,'2',:member_no,'3',:amount,:amount,'-1',:operate_date,'1',:member_no,:id_userlogin,:ref_no_source)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $responseSh->deptaccount_no,
						':amount' => $dataComing["amt_transfer"],
						':operate_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $responseSh->payinslip_no
					]);
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																,amount,amount_receive,trans_flag,operate_date,result_transaction,member_no,id_userlogin)
																VALUES(:ref_no,'WTX',:from_account,'2',:member_no,'3',:amount,:amount,'-1',:operate_date,'-9',:member_no,:id_userlogin)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $from_account_no,
						':amount' => $dataComing["amt_transfer"],
						':operate_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"]
					]);
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':status_flag' => '0',
						':destination' => $member_no,
						':response_code' => "WS0065",
						':response_message' => $responseSh->msg_output
					];
					$log->writeLog('buyshare',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0065';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch(SoapFault $e){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,amount_receive,trans_flag,operate_date,result_transaction,member_no,id_userlogin)
															VALUES(:ref_no,'WTX',:from_account,'2',:member_no,'3',:amount,:amount,'-1',:operate_date,'-9',:member_no,:id_userlogin)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $from_account_no,
					':amount' => $dataComing["amt_transfer"],
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"]
				]);
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':status_flag' => '0',
					':destination' => $member_no,
					':response_code' => "WS0065",
					':response_message' => $e
				];
				$log->writeLog('buyshare',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0065';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0065",
				":error_desc" => "ไมสามารถต่อไปยัง Service ซื้อหุ้นได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service ซื้อหุ้นได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e)."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0065';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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