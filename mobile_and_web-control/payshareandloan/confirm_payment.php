<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['menu_component', 'SUM_AMT', 'list_payment'], $dataComing)) {
    if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'PayShareLoan')) {
        $member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
        $dateOper = date('c');
        $dateOperC = date('Y-m-d H:i:s', strtotime($dateOper));
        $itemtypeWithdraw = 'WFS';
        $penalty_amt = $dataComing["penalty_amt"];
		$dataComing["SUM_AMT"] -= $dataComing["penalty_amt"];
		$deptaccount_no = preg_replace('/-/', '', $dataComing["deptaccount_no"]);
		$from_account_no = preg_replace('/-/', '', $dataComing["deptaccount_no"]);
		$arrInitDep = $cal_dep->initDept($deptaccount_no, $dataComing["SUM_AMT"], $itemtypeWithdraw);
		if ($arrInitDep["RESULT"]) {
			$arrSlipDPno = $cal_dep->generateDocNo('ONLINETX', $lib);
			$deptslip_no = $arrSlipDPno["SLIP_NO"];
			$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$getlastseq_no = $cal_dep->getLastSeqNo($deptaccount_no);
			$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETX'");
			$updateDocuControl->execute([':lastdocument_no' => $lastdocument_no]);
			$deptslip_noFee = null;
			if ($penalty_amt > 0) {
				$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE', $lib);
				$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
				$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
				$updateDocuControlFee = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
				$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
			}
			
			$arrSlipnoPayin = $cal_dep->generateDocNo('ONLINETXLON', $lib);
			$payinslip_no = $arrSlipnoPayin["SLIP_NO"];
			$lastdocument_noPayin = $arrSlipnoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControlPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXLON'");
			$updateDocuControlPayin->execute([':lastdocument_no' => $lastdocument_noPayin]);
			$arrSlipDocNoPayin = $cal_dep->generateDocNo('ONLINETXRECEIPT', $lib);
			$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
			$lastdocument_noDocPayin = $arrSlipDocNoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControlDocPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXRECEIPT'");
			$updateDocuControlDocPayin->execute([':lastdocument_no' => $lastdocument_noDocPayin]);
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no, $dataComing["SUM_AMT"], $dataComing["menu_component"]);
			if ($arrRightDep["RESULT"]) {
				$arraySuccess = array();
				$conoracle->beginTransaction();
				$conmysql->beginTransaction();
				$listIndex = 0;

				//WITHDRAW
				$constFromAcc = $cal_dep->getConstantAcc($deptaccount_no);
				$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
				$checkSeqAmtSrc = $cal_dep->getSequestAmt($deptaccount_no, $itemtypeWithdraw);

				if (!$checkSeqAmtSrc["CAN_WITHDRAW"]) {
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0092";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}

				if ($constFromAcc["MINPRNCBAL"] > $constFromAcc["PRNCBAL"] - ($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"] + $dataComing["SUM_AMT"])) {
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0091";
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}', number_format($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"], 2), $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}

				
				$wtdResult = $cal_dep->WithdrawMoneyInside(
					$conoracle,
					$deptaccount_no,
					null,
					$itemtypeWithdraw,
					$dataComing["SUM_AMT"] - $penalty_amt,
					$penalty_amt,
					$dateOperC,
					$config,
					$log,
					$payload,
					$deptslip_no,
					$lib,
					$getlastseq_no["MAX_SEQ_NO"],
					$constFromAcc,
					$deptslip_noFee
				);
				$i = 1;
				
				if (!$wtdResult["RESULT"]) {
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $wtdResult["RESPONSE_CODE"];
					if ($wtdResult["RESPONSE_CODE"] == 'WS0091') {
						$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}', number_format($wtdResult["SEQUEST_AMOUNT"], 2), $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					} else {
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					if ($dataComing["menu_component"] == 'TransferDepInsideCoop') {
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOperC,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $dataComing["SUM_AMT"],
							':penalty_amt' => $penalty_amt,
							':type_request' => '2',
							':transfer_flag' => '2',
							':destination' => 'share',
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => $wtdResult['ACTION']
						];
					} else {
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOperC,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $dataComing["SUM_AMT"],
							':penalty_amt' => $penalty_amt,
							':type_request' => '2',
							':transfer_flag' => '1',
							':destination' => 'share',
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => $wtdResult['ACTION']
						];
					}
					$log->writeLog('transferinside', $arrayStruc);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}

				$ref_no = time() . $lib->randomText('all', 3);

				$payslip = $cal_loan->paySlip(
					$conoracle,
					$dataComing["SUM_AMT"],
					$config,
					$payinslip_no,
					$dateOperC,
					$srcvcid["ACCOUNT_ID"],
					$wtdResult["DEPTSLIP_NO"],
					$log,
					$lib,
					$payload,
					$deptaccount_no,
					$payinslip_no,
					$member_no,
					$ref_no,
					$itemtypeWithdraw,
					$conmysql
				);

				if (!$payslip["RESULT"]) {
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $payslip["RESPONSE_CODE"];
					if ($payslip["RESPONSE_CODE"] == "WS0056") {
						$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}', number_format($payslip["MINDEPT_AMT"], 2), $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					} else {
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}

				foreach ($dataComing["list_payment"] as $listPayment) {
					if ($listPayment["payment_type"] == 'share') {
						//init data
						$getShareData = $cal_shr->getShareInfo($member_no);

						$getMembGroup = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
						$getMembGroup->execute([':member_no' => $member_no]);
						$rowMembGrp = $getMembGroup->fetch(PDO::FETCH_ASSOC);
						$grpAllow = substr($rowMembGrp["MEMBGROUP_CODE"], 0, 2);
						if ($grpAllow == "S2" || $grpAllow == "Y2") {
							$getLastBuy = $conoracle->prepare("SELECT NVL(SUM(SHARE_AMOUNT) * 50,0) as AMOUNT_PAID FROM shsharestatement WHERE member_no = :member_no 
																and TRUNC(to_char(slip_date,'YYYYMM')) = TRUNC(to_char(SYSDATE,'YYYYMM'))");
							$getLastBuy->execute([':member_no' => $member_no]);
							$rowLastBuy = $getLastBuy->fetch(PDO::FETCH_ASSOC);
							$fetchShare = $conoracle->prepare("SELECT sharestk_amt,periodshare_amt FROM shsharemaster WHERE member_no = :member_no");
							$fetchShare->execute([':member_no' => $member_no]);
							$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
							if ($rowLastBuy["AMOUNT_PAID"] + $listPayment["amt_transfer"] > $rowShare["PERIODSHARE_AMT"] * 50) {
								$conoracle->rollback();
								$arrayResult['RESPONSE_CODE'] = "BUY_SHARE_OVER_LIMIT";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}

						$paykeepingdet = $cal_loan->paySlipDet(
							$conoracle,
							$listPayment["amt_transfer"],
							$config,
							$dateOperC,
							$log,
							$payload,
							$deptaccount_no,
							$payinslip_no,
							'SHR',
							'01',
							'ซื้อหุ้นเพิ่ม',
							'1',
							'SPX',
							$getShareData["SHARE_AMT"],
							$wtdResult["DEPTSLIP_NO"]
						);

						if (!$paykeepingdet["RESULT"]) {
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}

						$buyshare = $cal_shr->buyShare(
							$conoracle,
							$member_no,
							$listPayment["amt_transfer"],
							0,
							$config,
							$payinslip_no,
							$dateOperC,
							$srcvcid["ACCOUNT_ID"],
							$wtdResult["DEPTSLIP_NO"],
							$log,
							$lib,
							$payload,
							$deptaccount_no,
							$payinslip_no,
							$ref_no
						);

						//buy share failed
						if (!$buyshare["RESULT"]) {
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $buyshare["RESPONSE_CODE"];
							if (isset($configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale])) {
								$arrayResult['RESPONSE_MESSAGE'] = str_replace('${' . $buyshare["TYPE_ERR"] . '}', number_format($buyshare["AMOUNT_ERR"], 2), $configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale]);
							} else {
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							}
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}

						//success buy share
						$listIndex++;
						$newArrSuccess["PAY_TYPE"] = $listPayment["payment_type"];
						$newArrSuccess["DEPTACCOUNT_NO"] = $deptaccount_no;
						$newArrSuccess["REF_NO"] = $ref_no;
						$newArrSuccess["ITEM_TYPE_WITHDRAW"] = $itemtypeWithdraw;
						$newArrSuccess["FROM_ACCOUNT_NO"] = $from_account_no;
						$newArrSuccess["TO_ACCOUNT_NO"] = $to_account_no;
						$newArrSuccess["AMOUNT"] = $listPayment["amt_transfer"];
						$newArrSuccess["PENALTY_AMT"] = $listPayment["fee_amt"];
						$newArrSuccess["AMOUNT_RECEIVE"] = $listPayment["amt_transfer"] - $listPayment["fee_amt"];
						$newArrSuccess["OPERATE_DATE"] = $dateOperC;
						$newArrSuccess["DEPTSLIP_NO"] = $deptslip_no;
						$newArrSuccess["TO_MEMBER_NO"] = $constToAcc["MEMBER_NO"];
						$arraySuccess[] = $newArrSuccess;
					} else if ($listPayment["payment_type"] == 'loan') {
						$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL
																FROM lncontmaster
																WHERE loancontract_no = :loancontract_no");
						$fetchLoanRepay->execute([':loancontract_no' => $listPayment["destination"]]);
						$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
						$interest = $cal_loan->calculateIntAPI($listPayment["destination"], $listPayment["amt_transfer"]);
						if($interest["INT_PAYMENT"] < 0){
							$interest["INT_PAYMENT"] = 0;
						}
						if ($interest["INT_PAYMENT"] > 0) {
							if ($listPayment["amt_transfer"] > ($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest["INT_PAYMENT"]) {
								$arrayResult['RESPONSE_CODE'] = "WS0098";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}

						$dataCont = $cal_loan->getContstantLoanContract($listPayment["destination"]);

						$int_return = $dataCont["INTEREST_RETURN"];
						$prinPay = 0;
						$interestPeriod = 0;
						$withdrawStatus = FALSE;
						$intarrear = $dataCont["INTEREST_ARREAR"];
						$interest = $cal_loan->calculateIntAPI($listPayment["destination"], $listPayment["amt_transfer"]);
						$interestPeriod = $interest["INT_PAYMENT"] - $dataCont["INTEREST_ARREAR"];
						if ($interestPeriod < 0) {
							$interestPeriod = 0;
						}
						$int_returnSrc = $interest["INT_RETURN"];
						$interestFull = $interest["INT_PAYMENT"];
						if ($interestFull > 0) {
							if ($listPayment["amt_transfer"] < $interestFull) {
								$interestFull = $listPayment["amt_transfer"];
							} else {
								$prinPay = $listPayment["amt_transfer"] - $interestFull;
							}
							if ($prinPay < 0) {
								$prinPay = 0;
							}
						} else {
							$prinPay = $listPayment["amt_transfer"];
						}

						$payslipdet = $cal_loan->paySlipLonDet(
							$conoracle,
							$dataCont,
							$listPayment["amt_transfer"],
							$config,
							$dateOperC,
							$log,
							$payload,
							$from_account_no,
							$payinslip_no,
							'LON',
							$dataCont["LOANTYPE_CODE"],
							$listPayment["destination"],
							$prinPay,
							$interestFull,
							0,
							$int_returnSrc,
							$interestPeriod,
							$i
						);

						if (!$payslipdet["RESULT"]) {
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $payslipdet["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}

						$repayloan = $cal_loan->repayLoan(
							$conoracle,
							$listPayment["destination"],
							$listPayment["amt_transfer"],
							$listPayment["fee_amt"],
							$config,
							$payinslipdoc_no,
							$dateOperC,
							$srcvcid["ACCOUNT_ID"],
							$wtdResult["DEPTSLIP_NO"],
							$log,
							$lib,
							$payload,
							$from_account_no,
							$payinslip_no,
							$member_no,
							$ref_no,
							$dataComing["app_version"],
							$interestFull,
							$int_returnSrc,
							$interest["INT_PAYMENT"]
						);
						if (!$repayloan["RESULT"]) {
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}

						//success pay loan
						$listIndex++;
						$newArrSuccess["PAY_TYPE"] = $listPayment["payment_type"];
						$newArrSuccess["DEPTACCOUNT_NO"] = $from_account_no;
						$newArrSuccess["REF_NO"] = $ref_no;
						$newArrSuccess["ITEM_TYPE_WITHDRAW"] = $itemtypeWithdraw;
						$newArrSuccess["FROM_ACCOUNT_NO"] = $from_account_no;
						$newArrSuccess["TO_ACCOUNT_NO"] = $listPayment["destination"];
						$newArrSuccess["AMOUNT"] = $listPayment["amt_transfer"];
						$newArrSuccess["PENALTY_AMT"] = $dataComing["penalty_amt"];
						$newArrSuccess["AMOUNT_RECEIVE"] = $listPayment["amt_transfer"] - $listPayment["fee_amt"];
						$newArrSuccess["OPERATE_DATE"] = $dateOperC;
						$newArrSuccess["DEPTSLIP_NO"] = $deptslip_no;
						$newArrSuccess["TO_MEMBER_NO"] = $constToAcc["MEMBER_NO"];
						$newArrSuccess["INTEREST_FULL"] = $interestFull;
						$newArrSuccess["PRIN_PAY"] = $prinPay;

						$arraySuccess[] = $newArrSuccess;

						$i++;
					} else {
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS0006";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						http_response_code(403);
						require_once('../../include/exit_footer.php');
					}
				}

				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
				VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $deptaccount_no,
					':seq_no' => $getlastseq_no["MAX_SEQ_NO"] + 1
				]);

				foreach ($arraySuccess as $value) {
					if ($value["PAY_TYPE"] == "share") {
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
						,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
						coop_slip_no,id_userlogin,ref_no_source)
						VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
						:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
						$insertTransactionLog->execute([
							':ref_no' => $value["REF_NO"],
							':slip_type' => $value["ITEM_TYPE_WITHDRAW"],
							':from_account' => $value["FROM_ACCOUNT_NO"],
							':destination' => $value["TO_ACCOUNT_NO"],
							':amount' => $value["AMOUNT"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':amount_receive' => $value["AMOUNT_RECEIVE"],
							':operate_date' => $dateOperC,
							':member_no' => $payload["member_no"],
							':slip_no' => $value["DEPTSLIP_NO"],
							':id_userlogin' => $payload["id_userlogin"]
						]);
					} else {
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	coop_slip_no,id_userlogin,ref_no_source)
																	VALUES(:ref_no,:slip_type,:from_account,:destination,'2',:amount,:penalty_amt,
																	:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
						$insertTransactionLog->execute([
							':ref_no' => $value["REF_NO"],
							':slip_type' => $value["ITEM_TYPE_WITHDRAW"],
							':from_account' => $value["FROM_ACCOUNT_NO"],
							':destination' => $value["TO_ACCOUNT_NO"],
							':amount' => $value["AMOUNT"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':amount_receive' => $value["AMOUNT_RECEIVE"],
							':operate_date' => $dateOperC,
							':member_no' => $payload["member_no"],
							':slip_no' => $value["DEPTSLIP_NO"],
							':id_userlogin' => $payload["id_userlogin"]
						]);
					}
				}

				$arrToken = $func->getFCMToken('person', $payload["member_no"]);
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"], 1);
				$dataMerge = array();
				$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($deptaccount_no, $func->getConstant('hidden_dep'));
				$dataMerge["AMT_TRANSFER"] = number_format($dataComing["SUM_AMT"], 2);
				$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'), 'D m Y', true);
				$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"], $templateMessage["BODY"], $dataMerge);
				foreach ($arrToken["LIST_SEND"] as $dest) {
					if ($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1') {
						$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
						$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
						$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
						$arrMessage["BODY"] = $message_endpoint["BODY"];
						$arrMessage["PATH_IMAGE"] = null;
						$arrPayloadNotify["PAYLOAD"] = $arrMessage;
						$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
						$arrPayloadNotify["SEND_BY"] = 'system';
						$arrPayloadNotify["TYPE_NOTIFY"] = '2';
						if ($func->insertHistory($arrPayloadNotify, '2')) {
							$lib->sendNotify($arrPayloadNotify, "person");
						}
					}
				}
				foreach ($arrToken["LIST_SEND_HW"] as $dest) {
					if ($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1') {
						$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
						$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
						$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
						$arrMessage["BODY"] = $message_endpoint["BODY"];
						$arrMessage["PATH_IMAGE"] = null;
						$arrPayloadNotify["PAYLOAD"] = $arrMessage;
						$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
						$arrPayloadNotify["SEND_BY"] = 'system';
						$arrPayloadNotify["TYPE_NOTIFY"] = '2';
						if ($func->insertHistory($arrPayloadNotify, '2')) {
							$lib->sendNotifyHW($arrPayloadNotify, "person");
						}
					}
				}

				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$conoracle->commit();
				$conmysql->commit();
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper, 'D m Y', true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			} else {
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if ($arrRightDep["RESPONSE_CODE"] == 'WS0056') {
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}', number_format($arrRightDep["MINWITD_AMT"], 2), $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				} else {
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		} else {
			$arrayResult['RESPONSE_CODE'] = $arrInitDep["RESPONSE_CODE"];
			if ($arrInitDep["RESPONSE_CODE"] == 'WS0056') {
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}', number_format($arrInitDep["MINWITD_AMT"], 2), $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			} else {
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
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
