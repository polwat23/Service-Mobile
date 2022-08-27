<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['menu_component', 'SUM_AMT', 'list_payment','sigma_key'], $dataComing)) {
    if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'PayShareLoan')) {
        $member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
        $getBankDisplay = $conmysql->prepare("SELECT cs.link_deposit_coopdirect,cs.bank_short_ename,gc.bank_code,gc.account_payfee,
												cs.fee_deposit,cs.bank_short_ename,gc.deptaccount_no_bank
												FROM gcbindaccount gc LEFT JOIN csbankdisplay cs ON gc.bank_code = cs.bank_code
												WHERE gc.sigma_key = :sigma_key and gc.bindaccount_status = '1'");
        $getBankDisplay->execute([':sigma_key' => $dataComing["sigma_key"]]);
        $rowBankDisplay = $getBankDisplay->fetch(PDO::FETCH_ASSOC);
        $vccAccID = null;
        if ($rowBankDisplay["bank_code"] == '025') {
            $vccAccID = $func->getConstant('map_account_id_bay');
        } else if ($rowBankDisplay["bank_code"] == '006') {
            $vccAccID = $func->getConstant('map_account_id_ktb');
        }
        $from_account_no = $rowBankDisplay["deptaccount_no_bank"];
        $itemtypeWithdraw = 'WFS';
        $ref_no = time() . $lib->randomText('all', 3);
        $dateOper = date('c');
        $dateOperC = date('Y-m-d H:i:s', strtotime($dateOper));

        $arraySuccess = array();
        $conoracle->beginTransaction();
        $conmysql->beginTransaction();
        $listIndex = 0;
        $i = 1;

        $arrSlipDPno = $cal_dep->generateDocNo('ONLINETX',$lib);
		$deptslip_no = $arrSlipDPno["SLIP_NO"];
		$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$getlastseq_no = $cal_dep->getLastSeqNo($from_account_no);
		$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETX'");
		$updateDocuControl->execute([':lastdocument_no' => $lastdocument_no]);
		$arrSlipnoPayin = $cal_dep->generateDocNo('ONLINETXLON',$lib);
		$arrSlipDocNoPayin = $cal_dep->generateDocNo('ONLINETXRECEIPT',$lib);
		$payinslip_no = $arrSlipnoPayin["SLIP_NO"];
		$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
		$lastdocument_noPayin = $arrSlipnoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$lastdocument_noDocPayin = $arrSlipDocNoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$updateDocuControlPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXLON'");
		$updateDocuControlPayin->execute([':lastdocument_no' => $lastdocument_noPayin]);
		$updateDocuControlDocPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXRECEIPT'");
		$updateDocuControlDocPayin->execute([':lastdocument_no' => $lastdocument_noDocPayin]);
		$getBalanceAccFee = $conoracle->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$getBalanceAccFee->execute([':deptaccount_no' => $rowBankDisplay["account_payfee"]]);
		$rowBalFee = $getBalanceAccFee->fetch(PDO::FETCH_ASSOC);
		$dataAccFee = $cal_dep->getConstantAcc($rowBankDisplay["account_payfee"]);
		$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowBankDisplay["account_payfee"]);
		$vccamtPenalty = $func->getConstant("map_account_id_ktb");
		if($rowBalFee["PRNCBAL"] - $rowBankDisplay["fee_deposit"] < $dataAccFee["MINPRNCBAL"]){
			$conoracle->rollback();
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS0100";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');

		}
		if($rowBankDisplay["fee_deposit"] > 0){
			$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
			$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
			$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControlFee = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
			$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
			file_put_contents(__DIR__.'Msgresponse.txt', json_encode($deptslip_noFee,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
		
			$penaltyWtd = $cal_dep->insertFeeTransaction($conoracle,$rowBankDisplay["account_payfee"],$vccamtPenalty,'FEE',
			$dataComing["SUM_AMT"],$rowBankDisplay["fee_deposit"],$dateOperC,$config,$deptslip_no,$lib,$getlastseqFeeAcc["MAX_SEQ_NO"],$dataAccFee,true,$payinslip_no,$deptslip_noFee);
			if($penaltyWtd["RESULT"]){
			}else{
				$conoracle->rollback();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $dataComing["SUM_AMT"],
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtd["ACTION"]
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		
		$payslip = $cal_loan->paySlip($conoracle,$dataComing["SUM_AMT"],$config,$payinslipdoc_no,$dateOperC,
		$vccAccID,null,$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$itemtypeWithdraw,$conmysql);
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
                    $from_account_no,
                    $payinslip_no,
                    'SHR',
                    '01',
                    'ซื้อหุ้นเพิ่ม',
                    '1',
                    'SPX',
                    $getShareData["SHARE_AMT"],
                    null
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
                    $payinslipdoc_no,
                    $dateOperC,
                    $vccAccID,
                    null,
                    $log,
                    $lib,
                    $payload,
                    $from_account_no,
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
                $newArrSuccess["DEPTACCOUNT_NO"] = $from_account_no;
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
                if ($interest["INT_PAYMENT"] > 0) {
                    $conoracle->rollback();
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
                    $i,$penaltyWtd["DEPTSLIP_NO"]
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
                    $vccAccID,
                    null,
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
                $newArrSuccess["PENALTY_AMT"] = $listPayment["fee_amt"];
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

        if (count($arraySuccess) > 0) {
            $arrSendData = array();
            $arrVerifyToken['exp'] = time() + 300;
            $arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
            $arrVerifyToken["coop_key"] = $config["COOP_KEY"];
            $arrVerifyToken['amt_transfer'] = $dataComing["SUM_AMT"];
            $arrVerifyToken['operate_date'] = $dateOperC;
            $arrVerifyToken['ref_trans'] = $ref_no;
            $arrVerifyToken['coop_account_no'] = null;
            if ($rowBankDisplay["bank_code"] == '025') {
                $arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
                $arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
            }
            $verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
            $arrSendData["verify_token"] = $verify_token;
            $arrSendData["app_id"] = $config["APP_ID"];
            // Deposit Inside --------------------------------------
            $responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"] . $rowBankDisplay["link_deposit_coopdirect"], $arrSendData);
            if (!$responseAPI["RESULT"]) {
                $conoracle->rollback();
                $conmysql->rollback();
                $filename = basename(__FILE__, '.php');
                $arrayResult['RESPONSE_CODE'] = "WS0027";
                $arrayStruc = [
                    ':member_no' => $payload["member_no"],
                    ':id_userlogin' => $payload["id_userlogin"],
                    ':operate_date' => $dateOperC,
                    ':sigma_key' => $dataComing["sigma_key"],
                    ':amt_transfer' => $dataComing["SUM_AMT"],
                    ':response_code' => $arrayResult['RESPONSE_CODE'],
                    ':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
                ];
                $log->writeLog('deposittrans', $arrayStruc);
                $message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ " . $responseAPI["RESPONSE_MESSAGE"] . "\n" . json_encode($arrVerifyToken);
                $lib->sendLineNotify($message_error);
                $func->MaintenanceMenu($dataComing["menu_component"]);
                $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
                $arrayResult['RESULT'] = FALSE;
                require_once('../../include/exit_footer.php');
            }
            $arrResponse = json_decode($responseAPI);
            if ($arrResponse->RESULT) {
                $transaction_no = $arrResponse->TRANSACTION_NO;
                $etn_ref = $arrResponse->EXTERNAL_REF;
                foreach ($arraySuccess as $value) {
                    if ($value["PAY_TYPE"] == "share") {
                        $insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
                                                                        ,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
                                                                        coop_slip_no,id_userlogin,ref_no_source,etn_refno,bank_code)
                                                                        VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
                                                                        :amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no,:etn_refno,:bank_code)");
                        $insertTransactionLog->execute([
                            ':ref_no' => $value["REF_NO"],
                            ':slip_type' => $value["ITEM_TYPE_WITHDRAW"],
                            ':from_account' => $value["FROM_ACCOUNT_NO"],
                            ':destination' => $value["TO_ACCOUNT_NO"],
                            ':amount' => $value["AMOUNT"],
                            ':penalty_amt' => $value["PENALTY_AMT"],
                            ':amount_receive' => $value["AMOUNT_RECEIVE"],
                            ':operate_date' => $dateOperC,
                            ':member_no' => $payload["member_no"],
                            ':id_userlogin' => $payload["id_userlogin"],
                            ':slip_no' => $transaction_no,
                            ':etn_refno' => $etn_ref,
							':bank_code' => $rowBankDisplay["bank_code"]
                        ]);
                    } else {
                        $insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,
                            destination,transfer_mode
                            ,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
                            etn_refno,id_userlogin,ref_no_source,bank_code)
                            VALUES(:ref_no,:slip_type,:from_account,'3',:destination,'9',:amount,:fee_amt,
                            :amount_receive,'-1',:operate_date,'1',:member_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
                        $insertTransactionLog->execute([
                            ':ref_no' => $value["REF_NO"],
                            ':slip_type' => $value["ITEM_TYPE_WITHDRAW"],
                            ':from_account' => $value["FROM_ACCOUNT_NO"],
                            ':destination' => $value["TO_ACCOUNT_NO"],
                            ':amount' => $value["AMOUNT"],
                            ':fee_amt' => $value["PENALTY_AMT"],
                            ':amount_receive' => $value["AMOUNT_RECEIVE"],
                            ':operate_date' => $dateOperC,
                            ':member_no' => $payload["member_no"],
                            ':etn_refno' => $etn_ref,
                            ':id_userlogin' => $payload["id_userlogin"],
                            ':ref_no_source' => $transaction_no,
                            ':bank_code' => $rowBankDisplay["bank_code"],
                        ]);
                    }
                }

                $arrToken = $func->getFCMToken('person', $payload["member_no"]);
                $templateMessage = $func->getTemplateSystem($dataComing["menu_component"], 1);
                $dataMerge = array();
                $dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($from_account_no, $func->getConstant('hidden_dep'));
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
            }else{
				$conoracle->rollback();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0038";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOperC,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $dataComing["SUM_AMT"],
					':response_code' => $arrResponse->RESPONSE_CODE,
					':response_message' => $arrResponse->RESPONSE_MESSAGE
				];
				$log->writeLog('deposittrans',$arrayStruc);
				if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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
