<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','penalty_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$itemtypeWithdraw = 'WIM';
		$itemtypeDepositDest = 'DIM';
		$ref_no = time().$lib->randomText('all',3);
		$dateOper = date('c');
		// Start-Withdraw
		$constFromAcc = $cal_dep->getConstantAcc($from_account_no);
		$constToAcc = $cal_dep->getConstantAcc($to_account_no);
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		$destvcid = $cal_dep->getVcMapID($constToAcc["DEPTTYPE_CODE"]);
		$checkSeqAmtSrc = $cal_dep->getSequestAmt($from_account_no,$itemtypeWithdraw);
		$checkSeqAmtDest = $cal_dep->getSequestAmt($to_account_no,$itemtypeDepositDest);
		if($checkSeqAmtSrc["CAN_WITHDRAW"] && $checkSeqAmtDest["CAN_DEPOSIT"]){
			if($constFromAcc["MINPRNCBAL"] > $constFromAcc["PRNCBAL"] - ($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"] + $dataComing["amt_transfer"])){
				$arrayResult['RESPONSE_CODE'] = "WS0091";
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}',number_format($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0092";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$rowMaxSeqNo = $cal_dep->getLastSeqNo($from_account_no);
		$arrSlipno = $cal_dep->generateDocNo('DPSLIPNO',$lib);
		$deptslip_no = $arrSlipno["DEPTSLIP_NO"];
		$lastStmSrcNo = $rowMaxSeqNo["MAX_SEQ_NO"] + 1;
		$rowDepPay = $cal_dep->getConstPayType($itemtypeWithdraw);
		if($dataComing["penalty_amt"] > 0){
			$lastdocument_no = $arrSlipno["QUERY"]["LAST_DOCUMENTNO"] + 3;
		}else{
			$lastdocument_no = $arrSlipno["QUERY"]["LAST_DOCUMENTNO"] + 2;
		}
		$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
		if($updateDocuControl->execute([':lastdocument_no' => $lastdocument_no])){
			$conoracle->beginTransaction();
			$arrExecute = [
				':deptslip_no' => $deptslip_no,
				':coop_id' => $config["COOP_ID"],
				':deptaccount_no' => $from_account_no,
				':depttype_code' => $constFromAcc["DEPTTYPE_CODE"],
				':deptgrp_code' => $constFromAcc["DEPTGROUP_CODE"],
				':itemtype_code' => $itemtypeWithdraw,
				':slip_amt' => $dataComing["amt_transfer"],
				':cash_type' => $rowDepPay["MONEYTYPE_SUPPORT"],
				':prncbal' => $constFromAcc["PRNCBAL"],
				':withdrawable_amt' => $constFromAcc["WITHDRAWABLE_AMT"],
				':checkpend_amt' => $constFromAcc["CHECKPEND_AMT"],
				':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
				':laststmno' => $lastStmSrcNo,
				':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
				':acc_id' => $destvcid["ACCOUNT_ID"],
				':penalty_amt' => $dataComing["penalty_amt"]
			];
			if($dataComing["penalty_amt"] > 0){
				$insertDpSlipSQL = "INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
									deptcoop_id,DEPTGROUP_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
									PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
									DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,CLOSEDAY_STATUS,OTHER_AMT,
									NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
									POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,SHOWFOR_DEPT,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
									TELLER_FLAG,OPERATE_TIME) 
									VALUES(:deptslip_no,:coop_id,:deptaccount_no,:depttype_code,:coop_id,:deptgrp_code,TRUNC(sysdate),:itemtype_code,
									:slip_amt,:cash_type,:prncbal,:withdrawable_amt,:checkpend_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:laststmno,:itemtype_code,
									TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),1,0,:penalty_amt,0,0,:acc_id,2,0,0,:slip_amt,0,0,0,1,1,1,0,1,1,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'))";
			}else{
				$insertDpSlipSQL = "INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
									deptcoop_id,DEPTGROUP_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
									PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
									DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,CLOSEDAY_STATUS,OTHER_AMT,
									NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
									POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,SHOWFOR_DEPT,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
									TELLER_FLAG,OPERATE_TIME) 
									VALUES(:deptslip_no,:coop_id,:deptaccount_no,:depttype_code,:coop_id,:deptgrp_code,TRUNC(sysdate),:itemtype_code,
									:slip_amt,:cash_type,:prncbal,:withdrawable_amt,:checkpend_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:laststmno,:itemtype_code,
									TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),1,0,:penalty_amt,0,0,:acc_id,1,0,0,:slip_amt,0,0,0,1,1,1,0,1,1,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'))";
			}
			$insertDpSlip = $conoracle->prepare($insertDpSlipSQL);
			if($insertDpSlip->execute($arrExecute)){
				$slipWithdraw = $deptslip_no;
				$arrExecuteStm = [
					':coop_id' => $config["COOP_ID"],
					':from_account_no' => $from_account_no,
					':seq_no' => $lastStmSrcNo,
					':itemtype_code' => $itemtypeWithdraw,
					':slip_amt' => $dataComing["amt_transfer"],
					':balance_forward' => $constFromAcc["PRNCBAL"],
					':after_trans_amt' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"],
					':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
					':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
					':cash_type' => $rowDepPay["MONEYTYPE_SUPPORT"],
					':deptslip_no' => $deptslip_no
				];
				$insertStatement = $conoracle->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
														CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
														VALUES(:coop_id,:from_account_no,:seq_no,:itemtype_code,TRUNC(sysdate),:slip_amt,:balance_forward,:after_trans_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
														TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),:cash_type,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:deptslip_no,'1')");
				if($insertStatement->execute($arrExecuteStm)){
					// Start-Penalty-Cal
					if($dataComing["penalty_amt"] > 0){
						$rowMapAccFee = $cal_dep->getVcMapID('00');
						$deptslip_noPenalty = $lib->mb_str_pad($deptslip_no + 1,$arrSlipno["QUERY"]["DOCUMENT_LENGTH"],'0');
						$lastStmSrcNo += 1;
						$arrExecutePenalty = [
							':deptslip_no' => $deptslip_noPenalty,
							':coop_id' => $config["COOP_ID"],
							':deptaccount_no' => $from_account_no,
							':depttype_code' => $constFromAcc["DEPTTYPE_CODE"],
							':deptgrp_code' => $constFromAcc["DEPTGROUP_CODE"],
							':itemtype_code' => 'FEE',
							':slip_amt' => $dataComing["penalty_amt"],
							':cash_type' => $rowDepPay["MONEYTYPE_SUPPORT"],
							':prncbal' => $constFromAcc["PRNCBAL"],
							':withdrawable_amt' => $constFromAcc["WITHDRAWABLE_AMT"],
							':checkpend_amt' => $constFromAcc["CHECKPEND_AMT"],
							':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
							':laststmno' => $lastStmSrcNo,
							':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
							':acc_id' => $rowMapAccFee["ACCOUNT_ID"],
							':refer_deptslip_no' => $deptslip_no
						];
						$insertDpSlipPenalty = $conoracle->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
															deptcoop_id,DEPTGROUP_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
															PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
															DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,CLOSEDAY_STATUS,
															NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,REFER_APP,
															POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,SHOWFOR_DEPT,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
															TELLER_FLAG,OPERATE_TIME) 
															VALUES(:deptslip_no,:coop_id,:deptaccount_no,:depttype_code,:coop_id,:deptgrp_code,TRUNC(sysdate),:itemtype_code,
															:slip_amt,:cash_type,:prncbal,:withdrawable_amt,:checkpend_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:laststmno,:itemtype_code,
															TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),1,0,0,0,:acc_id,2,:refer_deptslip_no,0,0,:slip_amt,'DEP',0,0,0,1,1,1,0,1,1,
															TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'))");
						if($insertDpSlipPenalty->execute($arrExecute)){
							$arrExecuteStmPenalty = [
								':coop_id' => $config["COOP_ID"],
								':from_account_no' => $from_account_no,
								':seq_no' => $lastStmSrcNo,
								':itemtype_code' => 'FEE',
								':slip_amt' => $dataComing["penalty_amt"],
								':balance_forward' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"],
								':after_trans_amt' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
								':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
								':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
								':cash_type' => $rowDepPay["MONEYTYPE_SUPPORT"],
								':deptslip_no' => $deptslip_noPenalty
							];
							$insertStatementPenalty = $conoracle->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
																	CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
																	VALUES(:coop_id,:from_account_no,:seq_no,:itemtype_code,TRUNC(sysdate),:slip_amt,:balance_forward,:after_trans_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
																	TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),:cash_type,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:deptslip_no,'1')");
							if($insertStatementPenalty->execute($arrExecuteStmPenalty)){
								$deptslip_no += 1;
							}else{
								$conoracle->rollback();
								$arrayResult["RESPONSE_CODE"] = 'WS0064';
								if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
										':operate_date' => $dateOper,
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':penalty_amt' => $dataComing["penalty_amt"],
										':type_request' => '2',
										':transfer_flag' => '2',
										':destination' => $to_account_no,
										':response_code' => $arrayResult['RESPONSE_CODE'],
										':response_message' => 'insert ลงตาราง DPDEPTSTATEMENT กรณีมีค่าปรับ ไม่ได้'.$insertStatementPenalty->queryString.json_encode($arrExecuteStmPenalty)
									];
								}else{
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':penalty_amt' => $dataComing["penalty_amt"],
										':type_request' => '2',
										':transfer_flag' => '1',
										':destination' => $to_account_no,
										':response_code' => $arrayResult['RESPONSE_CODE'],
										':response_message' => 'insert ลงตาราง DPDEPTSTATEMENT กรณีมีค่าปรับ ไม่ได้'.$insertStatementPenalty->queryString.json_encode($arrExecuteStmPenalty)
									];
								}
								$log->writeLog('transferinside',$arrayStruc);
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
						}else{
							$conoracle->rollback();
							$arrayResult["RESPONSE_CODE"] = 'WS0064';
							if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':operate_date' => $dateOper,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '2',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'insert ลงตาราง DPDEPTSLIP กรณีมีค่าปรับ ไม่ได้'.$insertDpSlipPenalty->queryString.json_encode($arrExecutePenalty)
								];
							}else{
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '1',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'insert ลงตาราง DPDEPTSLIP กรณีมีค่าปรับ ไม่ได้'.$insertDpSlipPenalty->queryString.json_encode($arrExecutePenalty)
								];
							}
							$log->writeLog('transferinside',$arrayStruc);
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}
					$arrUpdateMaster = [
						':withdraw_after_pay' => $constFromAcc["WITHDRAWABLE_AMT"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
						':prncbal_after_pay' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
						':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
						':seq_no' => $lastStmSrcNo,
						':from_account_no' => $from_account_no
					];
					$updateDeptMaster = $conoracle->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = :withdraw_after_pay,prncbal = :prncbal_after_pay,
															lastmovement_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
															lastaccess_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),laststmseq_no = :seq_no
															WHERE deptaccount_no = :from_account_no");
					if($updateDeptMaster->execute($arrUpdateMaster)){
						// Start-Deposit
						if($constToAcc["LIMITDEPT_FLAG"] == '1' && $dataComing["amt_transfer"] >= $constToAcc["LIMITDEPT_AMT"]){
							$conoracle->rollback();
							$arrayResult["RESPONSE_CODE"] = 'WS0093';
							if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':operate_date' => $dateOper,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '2',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ยอดทำรายการมากกว่ายอดทำรายการสูงสุดต่อครั้ง '.$constToAcc["LIMITDEPT_AMT"]
								];
							}else{
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '1',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ยอดทำรายการมากกว่ายอดทำรายการสูงสุดต่อครั้ง '.$constToAcc["LIMITDEPT_AMT"]
								];
							}
							$log->writeLog('transferinside',$arrayStruc);
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
						if($constToAcc["MAXBALANCE_FLAG"] == '1' && $dataComing["amt_transfer"] + $constToAcc["PRNCBAL"] > $constToAcc["MAXBALANCE"]){
							$conoracle->rollback();
							$arrayResult["RESPONSE_CODE"] = 'WS0093';
							if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':operate_date' => $dateOper,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '2',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ยอดคงเหลือหลังทำรายการฝากฝากกว่ายอดที่สหกรณ์กำหนด '.$constToAcc["MAXBALANCE"]
								];
							}else{
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '1',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ยอดคงเหลือหลังทำรายการฝากฝากกว่ายอดที่สหกรณ์กำหนด '.$constToAcc["MAXBALANCE"]
								];
							}
							$log->writeLog('transferinside',$arrayStruc);
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
						if($dataComing["amt_transfer"] < $constToAcc["MINDEPT_AMT"]){
							$conoracle->rollback();
							$arrayResult["RESPONSE_CODE"] = 'WS0056';
							if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':operate_date' => $dateOper,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '2',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ทำรายการต่ำกว่ายอดฝากที่กำหนด ยอดขั้นต่ำคือ '.$constToAcc["MINDEPT_AMT"]
								];
							}else{
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '1',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ทำรายการต่ำกว่ายอดฝากที่กำหนด ยอดขั้นต่ำคือ '.$constToAcc["MINDEPT_AMT"]
								];
							}
							$log->writeLog('transferinside',$arrayStruc);
							$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($constToAcc["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
						$rowDepPayDest = $cal_dep->getConstPayType($itemtypeDepositDest);
						$rowMaxSeqNoDest = $cal_dep->getLastSeqNo($to_account_no);
						$lastStmDestNo = $rowMaxSeqNoDest["MAX_SEQ_NO"] + 1;
						$deptslip_no = $lib->mb_str_pad($deptslip_no + 1,$arrSlipno["QUERY"]["DOCUMENT_LENGTH"],'0');
						$arrExecuteDest = [
							':deptslip_no' => $deptslip_no,
							':coop_id' => $config["COOP_ID"],
							':deptaccount_no' => $to_account_no,
							':depttype_code' => $constToAcc["DEPTTYPE_CODE"],
							':deptgrp_code' => $constToAcc["DEPTGROUP_CODE"],
							':itemtype_code' => $itemtypeDepositDest,
							':slip_amt' => $dataComing["amt_transfer"],
							':cash_type' => $rowDepPayDest["MONEYTYPE_SUPPORT"],
							':prncbal' => $constToAcc["PRNCBAL"],
							':withdrawable_amt' => $constToAcc["WITHDRAWABLE_AMT"],
							':checkpend_amt' => $constToAcc["CHECKPEND_AMT"],
							':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
							':laststmno' => $lastStmDestNo,
							':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constToAcc["LASTCALINT_DATE"])),
							':acc_id' => $srcvcid["ACCOUNT_ID"]
						];
						$insertDpSlipDest = $conoracle->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
															deptcoop_id,DEPTGROUP_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
															PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
															DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,CLOSEDAY_STATUS,
															NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
															POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,SHOWFOR_DEPT,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
															TELLER_FLAG,OPERATE_TIME) 
															VALUES(:deptslip_no,:coop_id,:deptaccount_no,:depttype_code,:coop_id,:deptgrp_code,TRUNC(sysdate),:itemtype_code,
															:slip_amt,:cash_type,:prncbal,:withdrawable_amt,:checkpend_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:laststmno,:itemtype_code,
															TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),1,0,0,0,:acc_id,1,0,0,
															:slip_amt,0,0,0,1,1,1,0,1,1,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'))");
						if($insertDpSlipDest->execute($arrExecuteDest)){
							$arrExecuteStmDest = [
								':coop_id' => $config["COOP_ID"],
								':to_account_no' => $to_account_no,
								':seq_no' => $lastStmDestNo,
								':itemtype_code' => $itemtypeDepositDest,
								':slip_amt' => $dataComing["amt_transfer"],
								':balance_forward' => $constToAcc["PRNCBAL"],
								':after_trans_amt' => $constToAcc["PRNCBAL"] + $dataComing["amt_transfer"],
								':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
								':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constToAcc["LASTCALINT_DATE"])),
								':cash_type' => $rowDepPayDest["MONEYTYPE_SUPPORT"],
								':deptslip_no' => $deptslip_no
							];
							$insertStatementDest = $conoracle->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
																	CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
																	VALUES(:coop_id,:to_account_no,:seq_no,:itemtype_code,TRUNC(sysdate),:slip_amt,:balance_forward,:after_trans_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
																	TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),:cash_type,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:deptslip_no,'1')");
							if($insertStatementDest->execute($arrExecuteStmDest)){
								$arrUpdateMasterDest = [
									':withdraw_after_pay' => $constToAcc["WITHDRAWABLE_AMT"] + $dataComing["amt_transfer"],
									':prncbal_after_pay' => $constToAcc["PRNCBAL"] + $dataComing["amt_transfer"],
									':entry_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':seq_no' => $lastStmDestNo,
									':to_account_no' => $to_account_no
								];
								$updateDeptMasterDest = $conoracle->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = :withdraw_after_pay,prncbal = :prncbal_after_pay,
																		lastmovement_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
																		lastaccess_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),laststmseq_no = :seq_no
																		WHERE deptaccount_no = :to_account_no");
								if($updateDeptMasterDest->execute($arrUpdateMasterDest)){
									$conoracle->commit();
									$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
																		VALUES(:remark,:deptaccount_no,:seq_no)");
									$insertRemark->execute([
										':remark' => $dataComing["remark"],
										':deptaccount_no' => $from_account_no,
										':seq_no' => $lastStmSrcNo
									]);
									$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																				,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																				coop_slip_no,id_userlogin,ref_no_source)
																				VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
																				:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
									$insertTransactionLog->execute([
										':ref_no' => $ref_no,
										':slip_type' => $itemtypeWithdraw,
										':from_account' => $from_account_no,
										':destination' => $to_account_no,
										':amount' => $dataComing["amt_transfer"],
										':penalty_amt' => $dataComing["penalty_amt"],
										':amount_receive' => $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
										':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
										':member_no' => $payload["member_no"],
										':slip_no' => $slipWithdraw,
										':id_userlogin' => $payload["id_userlogin"]
									]);
									$arrToken = $func->getFCMToken('person',$payload["member_no"]);
									$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
									$dataMerge = array();
									$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
									$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
									$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
											$arrPayloadNotify["SEND_BY"] = 'system';
											if($func->insertHistory($arrPayloadNotify,'2')){
												$lib->sendNotify($arrPayloadNotify,"person");
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
											$arrPayloadNotify["SEND_BY"] = 'system';
											if($func->insertHistory($arrPayloadNotify,'2')){
												$lib->sendNotifyHW($arrPayloadNotify,"person");
											}
										}
									}
									$arrayResult['TRANSACTION_NO'] = $ref_no;
									$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
									$arrayResult['RESULT'] = TRUE;
									require_once('../../include/exit_footer.php');
								}else{
									$conoracle->rollback();
									$arrayResult["RESPONSE_CODE"] = 'WS0064';
									if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
										$arrayStruc = [
											':member_no' => $payload["member_no"],
											':id_userlogin' => $payload["id_userlogin"],
											':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
											':operate_date' => $dateOper,
											':deptaccount_no' => $from_account_no,
											':amt_transfer' => $dataComing["amt_transfer"],
											':penalty_amt' => $dataComing["penalty_amt"],
											':type_request' => '2',
											':transfer_flag' => '2',
											':destination' => $to_account_no,
											':response_code' => $arrayResult['RESPONSE_CODE'],
											':response_message' => 'update for deposit ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMasterDest->queryString.json_encode($arrUpdateMasterDest)
										];
									}else{
										$arrayStruc = [
											':member_no' => $payload["member_no"],
											':id_userlogin' => $payload["id_userlogin"],
											':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
											':deptaccount_no' => $from_account_no,
											':amt_transfer' => $dataComing["amt_transfer"],
											':penalty_amt' => $dataComing["penalty_amt"],
											':type_request' => '2',
											':transfer_flag' => '1',
											':destination' => $to_account_no,
											':response_code' => $arrayResult['RESPONSE_CODE'],
											':response_message' => 'update for deposit ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMasterDest->queryString.json_encode($arrUpdateMasterDest)
										];
									}
									$log->writeLog('transferinside',$arrayStruc);
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
									
								}
							}else{
								$conoracle->rollback();
								$arrayResult["RESPONSE_CODE"] = 'WS0064';
								if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
										':operate_date' => $dateOper,
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':penalty_amt' => $dataComing["penalty_amt"],
										':type_request' => '2',
										':transfer_flag' => '2',
										':destination' => $to_account_no,
										':response_code' => $arrayResult['RESPONSE_CODE'],
										':response_message' => 'insert for deposit ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatementDest->queryString.json_encode($arrExecuteStmDest)
									];
								}else{
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':penalty_amt' => $dataComing["penalty_amt"],
										':type_request' => '2',
										':transfer_flag' => '1',
										':destination' => $to_account_no,
										':response_code' => $arrayResult['RESPONSE_CODE'],
										':response_message' => 'insert for deposit ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatementDest->queryString.json_encode($arrExecuteStmDest)
									];
								}
								$log->writeLog('transferinside',$arrayStruc);
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
						}else{
							$conoracle->rollback();
							$arrayResult["RESPONSE_CODE"] = 'WS0064';
							if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':operate_date' => $dateOper,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '2',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'insert for deposit ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlipDest->queryString.json_encode($arrExecuteDest)
								];
							}else{
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':penalty_amt' => $dataComing["penalty_amt"],
									':type_request' => '2',
									':transfer_flag' => '1',
									':destination' => $to_account_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'insert for deposit ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlipDest->queryString.json_encode($arrExecuteDest)
								];
							}
							$log->writeLog('transferinside',$arrayStruc);
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}else{
						$conoracle->rollback();
						$arrayResult["RESPONSE_CODE"] = 'WS0064';
						if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
								':operate_date' => $dateOper,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"],
								':type_request' => '2',
								':transfer_flag' => '2',
								':destination' => $to_account_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => 'update ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMaster->queryString.json_encode($arrUpdateMaster)
							];
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"],
								':type_request' => '2',
								':transfer_flag' => '1',
								':destination' => $to_account_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => 'update ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMaster->queryString.json_encode($arrUpdateMaster)
							];
						}
						$log->writeLog('transferinside',$arrayStruc);
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conoracle->rollback();
					$arrayResult["RESPONSE_CODE"] = 'WS0064';
					if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
							':operate_date' => $dateOper,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '2',
							':destination' => $to_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'insert ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatement->queryString.json_encode($arrExecuteStm)
						];
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '1',
							':destination' => $to_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'insert ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatement->queryString.json_encode($arrExecuteStm)
						];
					}
					$log->writeLog('transferinside',$arrayStruc);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conoracle->rollback();
				$arrayResult["RESPONSE_CODE"] = 'WS0064';
				if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
						':operate_date' => $dateOper,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'insert ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlip->queryString.json_encode($arrExecute)
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'insert ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlip->queryString.json_encode($arrExecute)
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '2',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'update for running number ลงตาราง cmdocumentcontrol ไม่ได้'.$updateDocuControl->queryString.json_encode([':lastdocument_no' => $lastdocument_no])
				];
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => date('Y-m-d H:i:s',strtotime($dateOper)),
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '1',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'update for running number ลงตาราง cmdocumentcontrol ไม่ได้'.$updateDocuControl->queryString.json_encode([':lastdocument_no' => $lastdocument_no])
				];
			}
			$log->writeLog('transferinside',$arrayStruc);
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