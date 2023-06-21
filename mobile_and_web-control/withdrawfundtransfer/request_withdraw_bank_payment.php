<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.link_withdraw_coopdirect,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = time().$lib->randomText('all',3);
		$penalty_include = $func->getConstant("include_penalty");
		if($rowDataWithdraw["bank_code"] == '025'){
			$fee_amt = $dataComing["penalty_amt"];
		}else{
			$fee_amt = $dataComing["penalty_amt"] + $dataComing["fee_amt"];
		}
		$amt_transfer = $dataComing["amt_transfer"];
		$arrVerifyToken['exp'] = $time + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['ref_trans'] = $ref_no;
		$refbank_no = null;
		$etnrefbank_no = null;
		if($rowDataWithdraw["bank_code"] == '004'){
			$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
			$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
			$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
			$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
			$refbank_no = $dataComing["kbank_ref_no"];
		}else if($rowDataWithdraw["bank_code"] == '006'){
			$arrVerifyToken['tran_date'] = $dateOper;
			$arrVerifyToken['bank_account'] = $rowDataWithdraw["deptaccount_no_bank"];
			$arrVerifyToken['citizen_id'] = $rowDataWithdraw["citizen_id"];
		}else if($rowDataWithdraw["bank_code"] == '025'){
			$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
			$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
			$refbank_no = $dataComing["SOURCE_REFNO"];
			$etnrefbank_no = $dataComing["ETN_REFNO"];
		}else if($rowDataWithdraw["bank_code"] == '014'){
			$arrVerifyToken['transaction_time'] = $dataComing["TRAN_TIME"];
			$arrVerifyToken['token_id'] = $dataComing["TOKEN_ID"];
			$arrVerifyToken['tran_uniq'] = $dataComing["TRAN_UNIQ"];
		}
		$verify_token = $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		
		// Withdraw Inside --------------------------------------
		$ref_slipno = null;
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
		$constantDep = $cal_dep->getConstantAcc($coop_account_no);
		$withdrawable_amt = 0;
		if(date("Ymd",strtotime($constantDep["LASTCALWITH_DATE"])) >= date("Ymd",strtotime($constantDep["LASTACCESS_DATE"]))){
			$withdrawable_amt = $constantDep["WITHDRAWABLE_AMT"];
		}else{
			$dateOperC = date('c');
			$arrayGroupAmt = array();
			$arrayGroupAmt["account_id"] = null;
			$arrayGroupAmt["action_status"] = "9";
			$arrayGroupAmt["atm_no"] = "MOBILE";
			$arrayGroupAmt["atm_seqno"] = null;
			$arrayGroupAmt["aviable_amt"] = null;
			$arrayGroupAmt["bank_accid"] = null;
			$arrayGroupAmt["bank_cd"] = '025';
			$arrayGroupAmt["branch_cd"] = null;
			$arrayGroupAmt["coop_code"] = $config["COOP_KEY"];
			$arrayGroupAmt["coop_id"] = "065001";
			$arrayGroupAmt["deptaccount_no"] = $coop_account_no;
			$arrayGroupAmt["depttype_code"] = $constantDep["DEPTTYPE_CODE"];
			$arrayGroupAmt["entry_id"] = "MOBILE";
			$arrayGroupAmt["fee_amt"] = 0;
			$arrayGroupAmt["fee_operate_cd"] = '0';
			$arrayGroupAmt["feeinclude_status"] = '1';
			$arrayGroupAmt["item_amt"] = $dataComing["amt_transfer"];
			$arrayGroupAmt["member_no"] = $member_no;
			$arrayGroupAmt["moneytype_code"] = "CBT";
			$arrayGroupAmt["msg_output"] = null;
			$arrayGroupAmt["msg_status"] = null;
			$arrayGroupAmt["operate_date"] = $dateOperC;
			$arrayGroupAmt["oprate_cd"] = "002";
			$arrayGroupAmt["post_status"] = "1";
			$arrayGroupAmt["principal_amt"] = null;
			$arrayGroupAmt["ref_app"] = "ETC";
			$arrayGroupAmt["ref_slipno"] = null;
			$arrayGroupAmt["slipitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
			$arrayGroupAmt["stmtitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
			$arrayGroupAmt["system_cd"] = "02";
			$arrayGroupAmt["withdrawable_amt"] = null;
			$argumentWSAmt = [
				"as_wspass" => $config["WS_PASS"],
				"astr_dept_inf_serv" => $arrayGroupAmt
			];
			$resultWSAmt = $clientWS->__call("of_dept_inf_serv", array($argumentWSAmt));
			$responseSoapAmt = $resultWSAmt->of_dept_inf_servResult;
			if($responseSoapAmt->msg_status == '0000'){
				$withdrawable_amt = $responseSoapAmt->withdrawable_amt;
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0104";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':fee_amt' => $dataComing["fee_amt"],
					':deptaccount_no' => $coop_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $responseSoapAmt->msg_output
				];
				$log->writeLog('withdrawtrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		$withdrawable_amt -= floatval(number_format($dataComing["amt_transfer"],2,'.','')) + floatval(number_format($dataComing["fee_amt"] ?? 0.00,2,'.',''));
		if($withdrawable_amt < 0){
			$arrayResult['RESPONSE_CODE'] = "WS0104";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseSoapAmt->msg_output
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET withdrawable_amt = :withdrawable_amt,confirm_status = 9
												
												WHERE deptaccount_no = :deptaccount_no");
		$updateWithdrawData->execute([
			':withdrawable_amt' => $withdrawable_amt,
			':deptaccount_no' => $coop_account_no
		]);

		//$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_withdraw_coopdirect"],$arrSendData);

		//HARDCODE Start Fix  for Test by Polwat.g
		$responseAPI["RESULT"]=TRUE; 
		$responseAPI["KTB_REF"]="KTB_REF"; 
		$responseAPI["TRANSACTION_NO"]="TRANSACTION_NO"; 
		//HARDCODE End Fix  for Test by Polwat.g

		if(!$responseAPI["RESULT"]){
			$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET 
													last_error = confirm_status||' withdraw unsuccess'
													,confirm_status = -9
													WHERE deptaccount_no = :deptaccount_no");
			$updateWithdrawData->execute([
				':deptaccount_no' => $coop_account_no
			]);
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':itemtype' => $rowDataWithdraw["itemtype_wtd"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataWithdraw["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':etn_ref' => $etnrefbank_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $refbank_no,
				':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
			]);
			$arrayGroup["post_status"] = "-1";
			$arrayGroup["atm_no"] = $ref_slipno;
			$argumentWS = [
					"as_wspass" => $config["WS_PASS"],
					"astr_dept_inf_serv" => $arrayGroup
			];
			$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
			$responseSoapCancel = $resultWS->of_dept_inf_servResult;
			$arrayResult['RESPONSE_CODE'] = "WS0030";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"];
			$lib->sendLineNotify($message_error);
			$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);

		//HARDCODE Start Fix  for Test by Polwat.g
		$arrResponse->RESULT=TRUE; 
		//HARDCODE End Fix  for Test by Polwat.g

		if($arrResponse->RESULT){
			try{
				$arrayData = array();
				$arrayData["serviceName"] = 'withdraw';
				$arrHeader[] = "requestId: ".$lib->randomText('all',10);
				$dataResponse = $lib->posting_dataAPI('http://10.20.240.78:4000/callservice',$arrayData,$arrHeader);
				$fetchRecpPaytype = $conoracle->prepare("SELECT default_accid FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype_dep");
				$fetchRecpPaytype->execute([':itemtype_dep' => $rowDataWithdraw["itemtype_wtd"]]);
				$rowAccid = $fetchRecpPaytype->fetch(PDO::FETCH_ASSOC);
				$dateOperC = date('c');
				$arrayGroup = array();
				$arrayGroup["account_id"] = $rowAccid["DEFAULT_ACCID"];
				$arrayGroup["action_status"] = "1";
				$arrayGroup["atm_no"] = "MOBILE";
				$arrayGroup["atm_seqno"] = null;
				$arrayGroup["aviable_amt"] = null;
				$arrayGroup["bank_accid"] = null;
				$arrayGroup["bank_cd"] = '025';
				$arrayGroup["branch_cd"] = null;
				$arrayGroup["coop_code"] = $config["COOP_KEY"];
				$arrayGroup["coop_id"] = "065001";
				$arrayGroup["deptaccount_no"] = $coop_account_no;
				$arrayGroup["depttype_code"] = $constantDep["DEPTTYPE_CODE"];
				$arrayGroup["deptgroup_code"] = $constantDep["DEPTGROUP_CODE"];
				$arrayGroup["entry_id"] = "MOBILE";
				$arrayGroup["fee_amt"] = $dataComing["fee_amt"] ?? 0;
				$arrayGroup["fee_operate_cd"] = '0';
				$arrayGroup["feeinclude_status"] = '1';
				$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
				$arrayGroup["laststmseq_no"] = $constantDep["LASTSTMSEQ_NO"];
				$arrayGroup["membcat_code"] = $constantDep["MEMBCAT_CODE"];
				$arrayGroup["member_no"] = $member_no;
				$arrayGroup["moneytype_code"] = "CBT";
				$arrayGroup["msg_output"] = null;
				$arrayGroup["msg_status"] = null;
				$arrayGroup["operate_date"] = $dateOperC;
				$arrayGroup["oprate_cd"] = "002";
				$arrayGroup["post_status"] = "1";
				$arrayGroup["principal_amt"] = $constantDep["PRNCBAL"];
				$arrayGroup["ref_app"] = "MOBILE";
				$arrayGroup["ref_slipno"] = null;
				$arrayGroup["slipitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
				$arrayGroup["stmtitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
				$arrayGroup["system_cd"] = "02";
				$arrayGroup["withdrawable_amt"] = $withdrawable_amt;//ใน Procedure ใช้ค่า $constantDep["WITHDRAWABLE_AMT"];
				$argumentWS = [
					"as_wspass" => $config["WS_PASS"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				
				$responseDep_msg_status="9999";
				$responseDep_msg_output="insert Dept Error";
				$responseDep_ref_slipno="";

				$USE_PL_SQL_TO_POST_DEPT=true;

				if($USE_PL_SQL_TO_POST_DEPT) {
					
					//Call PL/SQL 		
					
					//file_put_contents("D:\Mobile\Log_in.log", __DIR__.'/../../config/config_connection.json');

					$json = file_get_contents(__DIR__.'/../../config/config_connection.json');
					$json_data = json_decode($json,true);

					putenv("ORACLE_SID=".$json_data["DBORACLE_SERVICE"]."");
					putenv("NLS_LANG=AMERICAN_AMERICA.TH8TISASCII");  
					putenv("NLS_DATE_FORMAT=DD-MM-YYYY");    

					$IPSERVER =$json_data["DBORACLE_HOST"];
					$SERVICEDB = $json_data["DBORACLE_SERVICE"];
					$USER = $json_data["DBORACLE_USERNAME"];
					$PASSWORD = $json_data["DBORACLE_PASSWORD"];
					$objConnect = oci_connect($USER,$PASSWORD,$IPSERVER.'/'.$SERVICEDB);                   
					if(!$objConnect){
						//echo 'can not connect Oracle ';
						$AS_PROCESS_STATUS="0:ERROR can not connect Oracle DB";
						$responseDep_msg_status="0099";
						$responseDep_msg_output=$AS_PROCESS_STATUS;
						$responseDep_ref_slipno=$AS_DEPTSLIP_NO;
					}else{

					 $sql="
						BEGIN		
						POST_DEPT_INSERT_SERV_ONLINE(
							AS_BANK_CODE=>:AS_BANK_CODE ,
							AS_MEMBER_NO=>:AS_MEMBER_NO,
							AS_DEPTACCOUNT_NO=>:AS_DEPTACCOUNT_NO ,
							AS_MEMBCAT_CODE=>:AS_MEMBCAT_CODE,
							AS_COOP_ID=>:AS_COOP_ID,
							AS_DEPTCOOP_ID=>:AS_DEPTCOOP_ID,
							AS_DEPTTYPE_CODE=>:AS_DEPTTYPE_CODE ,
							AS_DEPTGROUP_CODE=>:AS_DEPTGROUP_CODE ,
							AS_OPERATE_DATE=>:AS_OPERATE_DATE ,
							AS_ENTRY_DATE=>:AS_ENTRY_DATE,
							AS_ENTRY_ID=>:AS_ENTRY_ID ,
							AS_OPERATE_CODE=>:AS_OPERATE_CODE,
							AS_SLIPITEMTYPE_CODE=>:AS_SLIPITEMTYPE_CODE ,
							AS_SIGN_FLAG_=>:AS_SIGN_FLAG,
							AS_ITEM_AMT_=>:AS_ITEM_AMT ,
							AS_MONEYTYPE_CODE=>:AS_MONEYTYPE_CODE,
							AS_MACHINE_ID=>:AS_MACHINE_ID,
							AS_FEE_AMT_=>:AS_FEE_AMT ,
							AS_TOFROMACCID=>:AS_TOFROMACCID ,
							AS_OTH_AMT_=>:AS_OTH_AMT,
							AS_PRNCBAL_=>:AS_PRNCBAL ,
							AS_WITHDRAWABLE_AMT_=>:AS_WITHDRAWABLE_AMT,
							AS_LASTSTMSEQ_NO_=>:AS_LASTSTMSEQ_NO ,
							AS_ACTION_STATUS_=>:AS_ACTION_STATUS,
							AS_POST_STATUS_=>:AS_POST_STATUS ,
							AS_DEPTSLIP_NO=>:AS_DEPTSLIP_NO ,
							AS_DEPTSLIP_NO_FEE=>:AS_DEPTSLIP_NO_FEE ,
							AS_DEPTSLIP_NO_OTH=>:AS_DEPTSLIP_NO_OTH ,
							AS_PROCESS_STATUS=>:AS_PROCESS_STATUS
						);
						END;
					";

					$stmt = oci_parse($objConnect, $sql);

					$AS_BANK_CODE = $rowDataWithdraw["bank_code"] ?? '004';
					$AS_MEMBER_NO = $member_no;
					$AS_DEPTACCOUNT_NO = $coop_account_no;
					$AS_COOP_ID = "065001";
					$AS_DEPTCOOP_ID = "065001";
					$AS_DEPTTYPE_CODE = $constantDep["DEPTTYPE_CODE"];
					$AS_DEPTGROUP_CODE = $constantDep["DEPTGROUP_CODE"];
					$AS_OPERATE_DATE = $dateOperC;//'2023-05-13T13:21:14+00:00'
					$AS_OPERATE_DATE=str_replace("T"," ",$AS_OPERATE_DATE );
					$AS_OPERATE_DATE=str_replace("+00:00","",$AS_OPERATE_DATE );
					$AS_OPERATE_DATE=str_replace("+07:00","",$AS_OPERATE_DATE );
					//SQL ISO-8860  SELECT TO_UTC_TIMESTAMP_TZ('2013-05-05T16:34:42+00:00') FROM DUAL;
					$AS_ENTRY_ID = "MOBILE";
					$AS_SLIPITEMTYPE_CODE = $rowDataWithdraw["itemtype_wtd"];
					$AS_SIGN_FLAG = '-1';// -1=withdraw,1=deposit
					$AS_MONEYTYPE_CODE = 'CBT';
					$AS_MACHINE_ID =$refbank_no;
					$AS_TOFROMACCID = $rowAccid["DEFAULT_ACCID"];
					$AS_ITEM_AMT = $dataComing["amt_transfer"];
					$AS_FEE_AMT =$dataComing["fee_amt"] ?? 0;
					$AS_OTH_AMT =$dataComing["penalty_amt"] ?? 0;
					$AS_PRNCBAL = $constantDep["PRNCBAL"];
					//$AS_PRNCBAL = $constantDep["PRNCBAL"]-$AS_ITEM_AMT-$AS_FEE_AMT-$AS_OTH_AMT
					$AS_WITHDRAWABLE_AMT = $constantDep["WITHDRAWABLE_AMT"];
					//$AS_WITHDRAWABLE_AMT = $withdrawable_amt;
					$AS_LASTSTMSEQ_NO = $constantDep["LASTSTMSEQ_NO"];

					oci_bind_by_name($stmt,":AS_BANK_CODE",$AS_BANK_CODE,3);
					oci_bind_by_name($stmt,":AS_MEMBER_NO",$AS_MEMBER_NO,8);
					oci_bind_by_name($stmt,":AS_DEPTACCOUNT_NO",$AS_DEPTACCOUNT_NO,20);
					oci_bind_by_name($stmt,":AS_MEMBCAT_CODE",$AS_MEMBCAT_CODE,6);
					oci_bind_by_name($stmt,":AS_COOP_ID",$AS_COOP_ID,6);
					oci_bind_by_name($stmt,":AS_DEPTCOOP_ID",$AS_DEPTCOOP_ID,10);
					oci_bind_by_name($stmt,":AS_DEPTTYPE_CODE",$AS_DEPTTYPE_CODE,2);
					oci_bind_by_name($stmt,":AS_DEPTGROUP_CODE",$AS_DEPTGROUP_CODE,5);
					oci_bind_by_name($stmt,":AS_OPERATE_DATE",$AS_OPERATE_DATE,50);
					oci_bind_by_name($stmt,":AS_ENTRY_DATE",$AS_ENTRY_DATE,50);
					oci_bind_by_name($stmt,":AS_ENTRY_ID",$AS_ENTRY_ID,50);
					oci_bind_by_name($stmt,":AS_OPERATE_CODE",$AS_OPERATE_CODE,8);
					oci_bind_by_name($stmt,":AS_SLIPITEMTYPE_CODE",$AS_SLIPITEMTYPE_CODE,4);
					oci_bind_by_name($stmt,":AS_SIGN_FLAG",$AS_SIGN_FLAG,10);
					oci_bind_by_name($stmt,":AS_ITEM_AMT",$AS_ITEM_AMT,20);
					oci_bind_by_name($stmt,":AS_MONEYTYPE_CODE",$AS_MONEYTYPE_CODE,4);
					oci_bind_by_name($stmt,":AS_MACHINE_ID",$AS_MACHINE_ID,30);
					oci_bind_by_name($stmt,":AS_FEE_AMT",$AS_FEE_AMT,20);
					oci_bind_by_name($stmt,":AS_TOFROMACCID",$AS_TOFROMACCID,10);
					oci_bind_by_name($stmt,":AS_OTH_AMT",$AS_OTH_AMT,20);
					oci_bind_by_name($stmt,":AS_PRNCBAL",$AS_PRNCBAL,20);
					oci_bind_by_name($stmt,":AS_WITHDRAWABLE_AMT",$AS_WITHDRAWABLE_AMT,20);
					oci_bind_by_name($stmt,":AS_LASTSTMSEQ_NO",$AS_LASTSTMSEQ_NO,10);
					oci_bind_by_name($stmt,":AS_ACTION_STATUS",$AS_ACTION_STATUS,5);
					oci_bind_by_name($stmt,":AS_POST_STATUS",$AS_POST_STATUS,2);
					oci_bind_by_name($stmt,":AS_DEPTSLIP_NO",$AS_DEPTSLIP_NO,30);
					oci_bind_by_name($stmt,":AS_DEPTSLIP_NO_FEE",$AS_DEPTSLIP_NO_FEE,30);
					oci_bind_by_name($stmt,":AS_DEPTSLIP_NO_OTH",$AS_DEPTSLIP_NO_OTH,30);
					oci_bind_by_name($stmt,":AS_PROCESS_STATUS",$AS_PROCESS_STATUS,3000);
					
					$r = oci_execute($stmt, OCI_DEFAULT);
					
					if(!$r) {
						$e = oci_error($stmt);
						$AS_PROCESS_STATUS=print_r($e);
					}else{
						oci_commit($objConnect);
					}
					oci_free_statement($stmt);
					
					oci_close($objConnect);
					
					//echo $AS_PROCESS_STATUS.'<br/>';

					$pos = strpos($AS_PROCESS_STATUS, '1:success');

					if ($pos === false) {

						$responseDep_msg_status="0099";
						$responseDep_msg_output=$AS_PROCESS_STATUS;
						$responseDep_ref_slipno=$AS_DEPTSLIP_NO;

					}else{

						$responseDep_msg_status="0000";
						$responseDep_msg_output=$AS_PROCESS_STATUS;
						$responseDep_ref_slipno=$AS_DEPTSLIP_NO;

					}
					
					file_put_contents("D:\Mobile\Log_in.log", $AS_OPERATE_DATE.','.$AS_PROCESS_STATUS);

				  }

				}else{
					//เรียก Service เดิมของ หนุ่ม แต่ต้อง Update withdrawable_amt ก่อนเรียก  Service นี้ by Polwat
					/*
					$resultWS = $clientWS->__call("of_dept_insert_serv_online", array($argumentWS));
					$responseSoap = $resultWS->of_dept_insert_serv_onlineResult;
					//สร้างตัวแปรเก็บ ผลการบันทึกเงินฝาก
					$responseDep_msg_status=$responseSoap->msg_status;
					$responseDep_msg_output=$responseSoap->msg_output;
					$responseDep_ref_slipno=$responseSoap->ref_slipno;	
					*/
				}
					
				//--code เดิมตรวจจาก ค่า return ของ Service dept by Polwat
				//if($responseSoap->msg_status != '0000'){
				//--ใช้ตัวแปรร่วมกัน 	 by Polwat
				if($responseDep_msg_status!= '0000'){
					$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET confirm_status = -8,last_error = 'repost Withdraw ".$ref_no."'
															WHERE deptaccount_no = :deptaccount_no");
					$updateWithdrawData->execute([
						':deptaccount_no' => $coop_account_no
					]);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $dataComing["fee_amt"],
						':deptaccount_no' => $coop_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseDep_msg_output
						//-code เดิมตรวจจาก ค่า return ของ Service dept   by Polwat
						//':response_message' => $responseSoap->msg_output 
					];
					$log->writeLog('withdrawtrans',$arrayStruc);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}

				//-code เดิมตรวจจาก ค่า return ของ Service dept by Polwat
				//if($responseSoap->msg_status == '0000'){  
					if($responseDep_msg_status == '0000'){
					
						//-code เดิมตรวจจาก ค่า return ของ Service dept  by Polwat
						//$ref_slipno = $responseSoap->ref_slipno; 
						$ref_slipno = $responseDep_ref_slipno;
					if($rowDataWithdraw["bank_code"] == '004'){
						$refno_source = $dataComing["kbank_ref_no"];
						$etn_refno = $dataComing["tran_id"];
					}else if($rowDataWithdraw["bank_code"] == '006'){
						$refno_source = $arrResponse->KTB_REF;
						$etn_refno = $arrResponse->TRANSACTION_NO;
					}
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $coop_account_no,
						':seq_no' => $ref_slipno
					]);
					$arrExecute = [
						':ref_no' => $ref_no,
						':itemtype' => $rowDataWithdraw["itemtype_wtd"],
						':from_account' => $coop_account_no,
						':destination' => $rowDataWithdraw["deptaccount_no_bank"],
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $dataComing["fee_amt"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':amount_receive' => $amt_transfer,
						':oper_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':ref_no1' => $coop_account_no,
						':slip_no' => $ref_slipno,
						':etn_refno' => $etn_refno,
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $refno_source,
						':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
					];
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
																VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'1',:member_no,:ref_no1,
																:slip_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
					if($insertTransactionLog->execute($arrExecute)){
					}else{
						$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
						$lib->sendLineNotify($message_error);
					}
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
					$dataMerge["DATETIME"] = $lib->convertdate($dateOper,'D m Y',true);
					$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
					foreach($arrToken["LIST_SEND"] as $dest){
						if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
							$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
							$arrMessage["BODY"] = $message_endpoint["BODY"];
							$arrMessage["PATH_IMAGE"] = null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
							$arrPayloadNotify["SEND_BY"] = "system";
							$arrPayloadNotify["TYPE_NOTIFY"] = "2";
							if($lib->sendNotify($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
							}
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
							$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
							$arrMessage["BODY"] = $message_endpoint["BODY"];
							$arrMessage["PATH_IMAGE"] = null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
							$arrPayloadNotify["SEND_BY"] = "system";
							$arrPayloadNotify["TYPE_NOTIFY"] = "2";
							if($lib->sendNotifyHW($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
							}
						}
					}
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}catch(SoapFault $e){
				$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET confirm_status = 8,last_error = 'repost'
														WHERE deptaccount_no = :deptaccount_no");
				$updateWithdrawData->execute([
					':deptaccount_no' => $coop_account_no
				]);
				file_put_contents('request_ErrWTD.txt', json_encode($e,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':fee_amt' => $dataComing["fee_amt"],
					':deptaccount_no' => $coop_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'ถอนไปยังธนาคาร '.$rowDataWithdraw["bank_short_ename"].' '.json_encode($e,JSON_UNESCAPED_UNICODE)
				];
				$log->writeLog('withdrawtrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':itemtype' => $rowDataWithdraw["itemtype_wtd"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataWithdraw["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => null,
				':etn_ref' => $etnrefbank_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $refbank_no,
				':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
			]);
			$arrayResult['RESPONSE_CODE'] = "WS0037";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $arrResponse->RESPONSE_MESSAGE
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			if(isset($configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
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