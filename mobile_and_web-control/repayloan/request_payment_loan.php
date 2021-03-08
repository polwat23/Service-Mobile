<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','contract_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$from_account_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$itemtypeWithdraw = 'WFS';
		$ref_no = time().$lib->randomText('all',3);
		$dateOper = date('c');
		$dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
		$dataCont = $cal_loan->getContstantLoanContract($dataComing["contract_no"]);
		$int_return = 0;
		$prinPay = 0;
		$interest = 0;
		$int_returnFull = 0;
		$withdrawStatus = FALSE;
		if($dataComing["amt_transfer"] > $dataCont["INTEREST_ARREAR"]){
			$intarrear = $dataCont["INTEREST_ARREAR"];
		}else{
			$intarrear = $dataComing["amt_transfer"];
		}
		$interest = $cal_loan->calculateInterest($dataComing["contract_no"],$dataComing["amt_transfer"]);
		$interestFull = $interest;
		if($interest > 0){
			if($dataComing["amt_transfer"] < $interest){
				$interest = $dataComing["amt_transfer"];
			}else{
				$prinPay = $dataComing["amt_transfer"] - $interest;
			}
			if($prinPay < 0){
				$prinPay = 0;
			}
		}else{
			$prinPay = $dataComing["amt_transfer"];
		}
		if($dataCont["CHECK_KEEPING"] == '0'){
			if($dataCont["SPACE_KEEPING"] != 0){
				$int_return = $cal_loan->calculateIntReturn($dataComing["contract_no"],$dataComing["amt_transfer"],$interest);
				$int_returnFull = $int_return;
			}
		}
		if($int_return >= $interest){
			$int_return = $int_return - $interest;
			$interest = 0;
		}else{
			$interest = $interest - $int_return;
			$int_return = 0;
		}
		$constFromAcc = $cal_dep->getConstantAcc($from_account_no);
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		$checkSeqAmtSrc = $cal_dep->getSequestAmt($from_account_no,$itemtypeWithdraw);
		if($checkSeqAmtSrc["CAN_WITHDRAW"]){
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
		$arrSlipnoLN = $cal_dep->generateDocNo('SLSLIPPAYIN',$lib);
		$arrSlipDocNo = $cal_dep->generateDocNo('SLRECEIPTNO',$lib);
		$interest_accum = $cal_loan->calculateIntAccum($member_no);
		$deptslip_no = $arrSlipno["SLIP_NO"];
		$lastStmSrcNo = $rowMaxSeqNo["MAX_SEQ_NO"] + 1;
		$rowDepPay = $cal_dep->getConstPayType($itemtypeWithdraw);
		if($dataComing["penalty_amt"] > 0){
			$lastdocument_no = $arrSlipno["QUERY"]["LAST_DOCUMENTNO"] + 2;
		}else{
			$lastdocument_no = $arrSlipno["QUERY"]["LAST_DOCUMENTNO"] + 1;
		}
		$lnslip_no = $arrSlipnoLN["SLIP_NO"];
		$lastdocument_noLN = $arrSlipnoLN["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$lastdocument_noDocLN = $arrSlipDocNo["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$destvcid = $cal_dep->getVcMapID($dataCont["LOANTYPE_CODE"],'LON');
		$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
		if($updateDocuControl->execute([':lastdocument_no' => $lastdocument_no])){
			$updateDocuControlLN = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLSLIPPAYIN'");
			if($updateDocuControlLN->execute([':lastdocument_no' => $lastdocument_noLN])){
				$updateDocuControlDocLN = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLRECEIPTNO'");
				if($updateDocuControlDocLN->execute([':lastdocument_no' => $lastdocument_noDocLN])){
					//Start-Withdraw
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
						':entry_date' => $dateOperC,
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
							':entry_date' => $dateOperC,
							':lastcalint_date' => date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
							':cash_type' => $rowDepPay["MONEYTYPE_SUPPORT"],
							':deptslip_no' => $deptslip_no
						];
						$insertStatement = $conoracle->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
																CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
																VALUES(:coop_id,:from_account_no,:seq_no,:itemtype_code,TRUNC(sysdate),:slip_amt,:balance_forward,:after_trans_amt,'MOBILE',TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
																TO_DATE(:lastcalint_date,'yyyy/mm/dd hh24:mi:ss'),TRUNC(sysdate),:cash_type,TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),:deptslip_no,'1')");
						if($insertStatement->execute($arrExecuteStm)){
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
									':entry_date' => $dateOperC,
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
								if($insertDpSlipPenalty->execute($arrExecutePenalty)){
									$arrExecuteStmPenalty = [
										':coop_id' => $config["COOP_ID"],
										':from_account_no' => $from_account_no,
										':seq_no' => $lastStmSrcNo,
										':itemtype_code' => 'FEE',
										':slip_amt' => $dataComing["penalty_amt"],
										':balance_forward' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"],
										':after_trans_amt' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
										':entry_date' => $dateOperC,
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
										$arrayStruc = [
											':member_no' => $payload["member_no"],
											':id_userlogin' => $payload["id_userlogin"],
											':operate_date' => $dateOperC,
											':deptaccount_no' => $from_account_no,
											':amt_transfer' => $dataComing["amt_transfer"],
											':status_flag' => '0',
											':destination' => $dataComing["contract_no"],
											':response_code' => "WS0066",
											':response_message' => 'Insert DPDEPTSTATEMENT ค่าปรับ ไม่ได้'.$insertStatementPenalty->queryString."\n".json_encode($arrExecuteStmPenalty)
										];
										$log->writeLog('repayloan',$arrayStruc);
										$arrayResult["RESPONSE_CODE"] = 'WS0066';
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
									}
								}else{
									$conoracle->rollback();
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => $dateOperC,
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':status_flag' => '0',
										':destination' => $dataComing["contract_no"],
										':response_code' => "WS0066",
										':response_message' => 'Insert DPDEPTSLIP ค่าปรับ ไม่ได้'.$insertDpSlipPenalty->queryString."\n".json_encode($arrExecute)
									];
									$log->writeLog('repayloan',$arrayStruc);
									$arrayResult["RESPONSE_CODE"] = 'WS0066';
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							}
							$arrUpdateMaster = [
								':withdraw_after_pay' => $constFromAcc["WITHDRAWABLE_AMT"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
								':prncbal_after_pay' => $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
								':entry_date' => $dateOperC,
								':seq_no' => $lastStmSrcNo,
								':from_account_no' => $from_account_no
							];
							$updateDeptMaster = $conoracle->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = :withdraw_after_pay,prncbal = :prncbal_after_pay,
																	lastmovement_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),
																	lastaccess_date = TO_DATE(:entry_date,'yyyy/mm/dd hh24:mi:ss'),laststmseq_no = :seq_no
																	WHERE deptaccount_no = :from_account_no");
							if($updateDeptMaster->execute($arrUpdateMaster)){
								$withdrawStatus = TRUE;
							}else{
								$conoracle->rollback();
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOperC,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':status_flag' => '0',
									':destination' => $dataComing["contract_no"],
									':response_code' => "WS0066",
									':response_message' => 'UPDATE DPDEPTMASTER ไม่ได้'.$updateDeptMaster->queryString."\n".json_encode($arrUpdateMaster)
								];
								$log->writeLog('repayloan',$arrayStruc);
								$arrayResult["RESPONSE_CODE"] = 'WS0066';
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}else{
							$conoracle->rollback();
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOperC,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':status_flag' => '0',
								':destination' => $dataComing["contract_no"],
								':response_code' => "WS0066",
								':response_message' => 'Insert DPDEPTSTATEMENT ไม่ได้'.$insertStatement->queryString."\n".json_encode($arrExecuteStm)
							];
							$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$conoracle->rollback();
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOperC,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':status_flag' => '0',
							':destination' => $dataComing["contract_no"],
							':response_code' => "WS0066",
							':response_message' => 'Insert DPDEPTSLIP ไม่ได้'.$insertDpSlip->queryString."\n".json_encode($arrExecute)
						];
						$log->writeLog('repayloan',$arrayStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0066';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
					//Start-PayLoan
					if($withdrawStatus === TRUE){
						$getShareinfo = $conoracle->prepare("SELECT SHARESTK_AMT FROM SHSHAREMASTER WHERE member_no = :member_no");
						$getShareinfo->execute([':member_no' => $member_no]);
						$rowShare = $getShareinfo->fetch(PDO::FETCH_ASSOC);
						$getMemberInfo = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
						$getMemberInfo->execute([':member_no' => $member_no]);
						$rowMember = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
						$arrExecuteSlSlip = [
							':coop_id' => $config["COOP_ID"],
							':payinslip_no' => $lnslip_no,
							':member_no' => $member_no,
							':document_no' => $arrSlipDocNo["SLIP_NO"],
							':sliptype_code' => 'PX',
							':operate_date' => $dateOperC,
							':sharevalue' => $rowShare["SHARESTK_AMT"] * 10,
							':intaccum_amt' => $interest_accum,
							':moneytype_code' => 'TRN',
							':tofrom_accid' => $srcvcid["ACCOUNT_ID"],
							':slipdep' => $slipWithdraw,
							':slip_amt' => $dataComing["amt_transfer"],
							':membgroup_code' => $rowMember["MEMBGROUP_CODE"]
						];
						$insertPayinSlip = $conoracle->prepare("INSERT INTO slslippayin(COOP_ID,PAYINSLIP_NO,MEMCOOP_ID,MEMBER_NO,DOCUMENT_NO,SLIPTYPE_CODE,
																SLIP_DATE,OPERATE_DATE,SHARESTKBF_VALUE,SHARESTK_VALUE,INTACCUM_AMT,MONEYTYPE_CODE,ACCID_FLAG,
																TOFROM_ACCID,REF_SYSTEM,REF_SLIPNO,SLIP_AMT,
																MEMBGROUP_CODE,ENTRY_ID,ENTRY_DATE)
																VALUES(:coop_id,:payinslip_no,:coop_id,:member_no,:document_no,:sliptype_code,
																TRUNC(TO_DATE(:operate_date,'yyyy/mm/dd  hh24:mi:ss')),
																TRUNC(TO_DATE(:operate_date,'yyyy/mm/dd  hh24:mi:ss')),
																:sharevalue,:sharevalue,:intaccum_amt,:moneytype_code,1,:tofrom_accid,'DEP',:slipdep,:slip_amt,:membgroup_code,'MOBILE',TRUNC(SYSDATE))");
						if($insertPayinSlip->execute($arrExecuteSlSlip)){
							$updateInterestAccum = $conoracle->prepare("UPDATE mbmembmaster SET ACCUM_INTEREST = :int_accum WHERE member_no = :member_no");
							if($updateInterestAccum->execute([
								':int_accum' => $interest_accum + $interest,
								':member_no' => $member_no
							])){
								$lastperiod = $dataCont["LAST_PERIODPAY"] + 1;
								$executeSlDet = [
									':coop_id' => $config["COOP_ID"], 
									':payinslip_no' => $lnslip_no,
									':slipitemtype' => 'LON',
									':loantype_code' => $dataCont["LOANTYPE_CODE"],
									':loancontract_no' => $dataComing["contract_no"],
									':lastperiod' => $lastperiod,
									':prin_pay' => $prinPay,
									':int_pay' => $interest,
									':int_arrear' => $intarrear,
									':itempay_amt' => $dataComing["amt_transfer"],
									':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
									':principal' => $dataCont["PRINCIPAL_BALANCE"],
									':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
									':int_return' => $int_return,
									':stm_itemtype' => 'LPX',
									':bfperiod' => $dataCont["LAST_PERIODPAY"],
									':bfintarr' => $dataCont["INTEREST_ARREAR"],
									':lastprocess_date' => date('Y-m-d H:i:s',strtotime($dataCont["LASTPROCESS_DATE"])),
									':period_payment' => $dataCont["PERIOD_PAYMENT"],
									':payspec_method' => $dataCont["PAYSPEC_METHOD"],
									':rkeep_principal' => $dataCont["RKEEP_PRINCIPAL"],
									':rkeep_interest' => $dataCont["RKEEP_INTEREST"],
									':nkeep_interest' => $dataCont["NKEEP_INTEREST"]
								];
								$insertSLSlipDet = $conoracle->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
																		SHRLONTYPE_CODE,CONCOOP_ID,LOANCONTRACT_NO,SLIPITEM_DESC,PERIOD,PRINCIPAL_PAYAMT,INTEREST_PAYAMT,
																		INTARREAR_PAYAMT,ITEM_PAYAMT,ITEM_BALANCE,PRNCALINT_AMT,CALINT_FROM,CALINT_TO,INTEREST_RETURN,STM_ITEMTYPE,
																		BFPERIOD,BFINTARR_AMT,BFLASTCALINT_DATE,BFLASTPROC_DATE,BFPERIOD_PAYMENT,BFSHRCONT_BALAMT,BFCOUNTPAY_FLAG,
																		BFPAYSPEC_METHOD,RKEEP_PRINCIPAL,RKEEP_INTEREST,NKEEP_INTEREST,BFINTRETURN_FLAG)
																		VALUES(:coop_id,:payinslip_no,:slipitemtype,1,1,:loantype_code,:coop_id,:loancontract_no,'ชำระพิเศษ',
																		:lastperiod,:prin_pay,:int_pay,:int_arrear,:itempay_amt,:prin_bal,:principal,
																		TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),TRUNC(SYSDATE),:int_return,
																		:stm_itemtype,:bfperiod,
																		:bfintarr,TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),
																		TRUNC(TO_DATE(:lastprocess_date,'yyyy/mm/dd  hh24:mi:ss')),
																		:period_payment,:principal,1,:payspec_method,:rkeep_principal,:rkeep_interest,:nkeep_interest,0)");
								if($insertSLSlipDet->execute($executeSlDet)){
									$intArr = $interestFull - $dataComing["amt_transfer"] - $int_returnFull;
									if($intArr < 0){
										$intArr = 0;
									}
									$executeLnSTM = [
										':coop_id' => $config["COOP_ID"],
										':loancontract_no' => $dataComing["contract_no"],
										':lastseq_no' => $dataCont["LAST_STM_NO"] + 1,
										':stm_itemtype' => 'LPX',
										':document_no' => $arrSlipDocNo["SLIP_NO"],
										':lastperiod' => $lastperiod,
										':prin_pay' => $prinPay,
										':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
										':int_pay' => $interest,
										':principal' => $dataCont["PRINCIPAL_BALANCE"],
										':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
										':bfintarr' => $dataCont["INTEREST_ARREAR"],
										':int_arr' => $intArr,
										':int_return' => $int_return,
										':moneytype_code' => 'TRN',
										':ref_slipno' => $lnslip_no,
										':bfint_return' => $constLoanContract["INTEREST_RETURN"]
									];
									$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
																			OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
																			PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
																			INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
																			BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
																			VALUES(:coop_id,:loancontract_no,:lastseq_no,:stm_itemtype,TRUNC(SYSDATE),TRUNC(SYSDATE),
																			TRUNC(SYSDATE),:document_no,:lastperiod,:prin_pay,:int_pay,:prin_bal,:principal,
																			TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),
																			TRUNC(SYSDATE),:bfintarr,0,:int_arr,
																			:int_return,:moneytype_code,1,'MOBILE',TRUNC(SYSDATE),:coop_id,:ref_slipno,:bfint_return,TRUNC(SYSDATE),'1')");
									if($insertSTMLoan->execute($executeLnSTM)){
										$executeLnMaster = [
											':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
											':loancontract_no' => $dataComing["contract_no"],
											':lastperiod_pay' => $lastperiod,
											':int_arr' => $intArr,
											':int_accum' => $interest_accum + $interest,
											':prinpay' => $prinPay,
											':int_return' => $int_return,
											':int_pay' => $interest,
											':laststmno' => $dataCont["LAST_STM_NO"] + 1,
										];
										$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																					PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																					LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																					INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																					INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																					INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno
																					WHERE loancontract_no = :loancontract_no");
										if($updateLnContmaster->execute($executeLnMaster)){
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
																						VALUES(:ref_no,:slip_type,:from_account,:destination,'2',:amount,:penalty_amt,
																						:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
											$insertTransactionLog->execute([
												':ref_no' => $ref_no,
												':slip_type' => $itemtypeWithdraw,
												':from_account' => $from_account_no,
												':destination' => $dataComing["contract_no"],
												':amount' => $dataComing["amt_transfer"],
												':penalty_amt' => $dataComing["penalty_amt"],
												':amount_receive' => $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
												':operate_date' => $dateOperC,
												':member_no' => $payload["member_no"],
												':slip_no' => $slipWithdraw,
												':id_userlogin' => $payload["id_userlogin"]
											]);
											$arrToken = $func->getFCMToken('person',$payload["member_no"]);
											$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
											$dataMerge = array();
											$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
											$dataMerge["CONTRACT_NO"] = $dataComing["contract_no"];
											$dataMerge["AMOUNT"] = number_format($dataComing["amt_transfer"],2);
											$dataMerge["INT_PAY"] = number_format($interest,2);
											$dataMerge["PRIN_PAY"] = number_format($prinPay,2);
											$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
											$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOperC,'D m Y',true);
											$arrayResult['RESULT'] = TRUE;
											require_once('../../include/exit_footer.php');
										}else{
											$conoracle->rollback();
											$arrayStruc = [
												':member_no' => $payload["member_no"],
												':id_userlogin' => $payload["id_userlogin"],
												':operate_date' => $dateOperC,
												':deptaccount_no' => $from_account_no,
												':amt_transfer' => $dataComing["amt_transfer"],
												':status_flag' => '0',
												':destination' => $dataComing["contract_no"],
												':response_code' => "WS0066",
												':response_message' => 'UPDATE lncontmaster ไม่ได้'.$updateLnContmaster->queryString."\n".json_encode($executeLnMaster)
											];
											$log->writeLog('repayloan',$arrayStruc);
											$arrayResult["RESPONSE_CODE"] = 'WS0066';
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
										}
									}else{
										$conoracle->rollback();
										$arrayStruc = [
											':member_no' => $payload["member_no"],
											':id_userlogin' => $payload["id_userlogin"],
											':operate_date' => $dateOperC,
											':deptaccount_no' => $from_account_no,
											':amt_transfer' => $dataComing["amt_transfer"],
											':status_flag' => '0',
											':destination' => $dataComing["contract_no"],
											':response_code' => "WS0066",
											':response_message' => 'INSERT lncontstatement ไม่ได้'.$insertSTMLoan->queryString."\n".json_encode($executeLnSTM)
										];
										$log->writeLog('repayloan',$arrayStruc);
										$arrayResult["RESPONSE_CODE"] = 'WS0066';
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
									}
								}else{
									$conoracle->rollback();
									$arrayStruc = [
										':member_no' => $payload["member_no"],
										':id_userlogin' => $payload["id_userlogin"],
										':operate_date' => $dateOperC,
										':deptaccount_no' => $from_account_no,
										':amt_transfer' => $dataComing["amt_transfer"],
										':status_flag' => '0',
										':destination' => $dataComing["contract_no"],
										':response_code' => "WS0066",
										':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($executeSlDet)
									];
									$log->writeLog('repayloan',$arrayStruc);
									$arrayResult["RESPONSE_CODE"] = 'WS0066';
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							}else{
								$conoracle->rollback();
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOperC,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $dataComing["amt_transfer"],
									':status_flag' => '0',
									':destination' => $dataComing["contract_no"],
									':response_code' => "WS0066",
									':response_message' => 'UPDATE mbmembmaster ไม่ได้'.$updateInterestAccum->queryString."\n".json_encode([
										':int_accum' => $interest_accum + $interest,
										':member_no' => $member_no
									])
								];
								$log->writeLog('repayloan',$arrayStruc);
								$arrayResult["RESPONSE_CODE"] = 'WS0066';
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}else{
							$conoracle->rollback();
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOperC,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':status_flag' => '0',
								':destination' => $dataComing["contract_no"],
								':response_code' => "WS0066",
								':response_message' => 'Insert slslippayin ไม่ได้'.$insertPayinSlip->queryString."\n".json_encode($arrExecuteSlSlip)
							];
							$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$conoracle->rollback();
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOperC,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':status_flag' => '0',
							':destination' => $dataComing["contract_no"],
							':response_code' => "WS0066",
							':response_message' => 'ถอนเงินฝาก ไม่ได้'
						];
						$log->writeLog('repayloan',$arrayStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0066';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOperC,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':status_flag' => '0',
					':destination' => $dataComing["contract_no"],
					':response_code' => "WS0066",
					':response_message' => 'update for running number ลงตาราง cmdocumentcontrol ไม่ได้'.$updateDocuControlLN->queryString.json_encode([':lastdocument_no' => $lastdocument_no])
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOperC,
				':deptaccount_no' => $from_account_no,
				':amt_transfer' => $dataComing["amt_transfer"],
				':status_flag' => '0',
				':destination' => $dataComing["contract_no"],
				':response_code' => "WS0066",
				':response_message' => 'update for running number ลงตาราง cmdocumentcontrol ไม่ได้'.$updateDocuControl->queryString.json_encode([':lastdocument_no' => $lastdocument_no])
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
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