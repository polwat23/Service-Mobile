<?php
require_once('../autoload.php');
require_once('../../include/cal_deposit_test.php');
require_once('../../include/cal_loan_test.php');

use CalculateDepositTest\CalculateDepositTest;
use CalculateLoanTest\CalculateLoanTest;

$cal_dep = new CalculateDepositTest();
$cal_loan = new CalculateLoanTest();

$dbuser = "iscotest";
$dbpass = "iscotest";
$dbname = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.0.226)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = gcoop)
			)
		  )";
$conoracle = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

$getProcessWaiting = $conmysql->prepare("SELECT kpslip_no,json_deposit,member_no,deptslip_no,account_no,payload,id_process
										FROM gcprocesspaymentmonthly WHERE process_status = '0'");
$getProcessWaiting->execute();
while($rowProcess = $getProcessWaiting->fetch(PDO::FETCH_ASSOC)){
	$payloadTemp = $rowProcess["payload"];
	$dateOper = date('c');
	$penalty_amt = 0;
	$ref_no = time().$lib->randomText('all',3);
	$dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
	$getReceiveAmt = $conoracle->prepare("SELECT RECEIVE_AMT FROM kptempreceive  
										WHERE kpslip_no = :kpslip_no and keeping_status = '-99'");
	$getReceiveAmt->execute([':kpslip_no' => $rowProcess['kpslip_no']]);
	$rowReceiveAmt = $getReceiveAmt->fetch(PDO::FETCH_ASSOC);
	$arrSlipnoPayin = $cal_dep->generateDocNo('SLSLIPPAYIN',$lib);
	$arrSlipDocNoPayin = $cal_dep->generateDocNo('SLRECEIPTNO',$lib);
	$payinslip_no = $arrSlipnoPayin["SLIP_NO"];
	$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
	$lastdocument_noPayin = $arrSlipnoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
	$lastdocument_noDocPayin = $arrSlipDocNoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
	$updateDocuControlPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLSLIPPAYIN'");
	$updateDocuControlPayin->execute([':lastdocument_no' => $lastdocument_noPayin]);
	$updateDocuControlDocPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLRECEIPTNO'");
	$updateDocuControlDocPayin->execute([':lastdocument_no' => $lastdocument_noDocPayin]);
	$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
	$conoracle->beginTransaction();
	$paykeeping = $cal_loan->paySlip($conoracle,$rowReceiveAmt["RECEIVE_AMT"],$config,$payinslipdoc_no,$dateOperC,
					$srcvcid["ACCOUNT_ID"],$rowProcess["deptslip_no"],$log,$lib,$payloadTemp,$rowProcess["account_no"],$payinslip_no,$rowProcess["account_no"],$ref_no,'WTM',$conmysql);
	if($paykeeping["RESULT"]){
		$getPaymentDetail = $conoracle->prepare("SELECT 
												kut.KEEPITEMTYPE_GRP,
												NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
												KPD.SHRLONTYPE_CODE,
												CASE 
												WHEN kut.KEEPITEMTYPE_GRP = 'SHR' THEN
												kpd.MEMBER_NO
												WHEN kut.KEEPITEMTYPE_GRP = 'LON' THEN
												kpd.LOANCONTRACT_NO
												WHEN kut.KEEPITEMTYPE_GRP = 'DEP' THEN
												kpd.DESCRIPTION
												END as DESTINATION,
												kut.KEEPITEMTYPE_CODE,
												kut.KEEPITEMTYPE_DESC,
												kpd.SEQ_NO,
												ROW_NUMBER() OVER (PARTITION BY kut.KEEPITEMTYPE_GRP ORDER BY kpd.SEQ_NO) AS SLIP_SEQ_NO
												FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
												kpd.keepitemtype_code = kut.keepitemtype_code
												LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
												LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
												WHERE kpd.kpslip_no = :kpslip_no and kut.SIGN_FLAG = 1
												ORDER BY kpd.SEQ_NO ASC");
		$getPaymentDetail->execute([
			':kpslip_no' => $rowProcess['kpslip_no']
		]);
		while($rowKPDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$cancelSlipLoan = $conoracle->prepare("UPDATE kptempreceivedet SET keepitem_status = '-99' 
													WHERE kpslip_no = :kpslip_no and seq_no = :seq_no");
			if($cancelSlipLoan->execute([
				':kpslip_no' => $rowProcess['kpslip_no'],
				':seq_no' => $rowKPDetail["SEQ_NO"]
			])){
				if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'LON'){
					$dataCont = $cal_loan->getContstantLoanContract($rowKPDetail["DESTINATION"]);
					$int_return = $dataCont["INTEREST_RETURN"];
					if($rowKPDetail["ITEM_PAYMENT"] > $dataCont["INTEREST_ARREAR"]){
						$intarrear = $dataCont["INTEREST_ARREAR"];
					}else{
						$intarrear = $rowKPDetail["ITEM_PAYMENT"];
					}
					$int_returnSrc = 0;
					$int_returnFull = 0;
					$interest = $cal_loan->calculateInterest($rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"]);
					$interestFull = $interest;
					$interestPeriod = $interest - $dataCont["INTEREST_ARREAR"];
					if($interestPeriod < 0){
						$interestPeriod = 0;
					}
					if($interest > 0){
						if($rowKPDetail["ITEM_PAYMENT"] < $interest){
							$interest = $rowKPDetail["ITEM_PAYMENT"];
						}else{
							$prinPay = $rowKPDetail["ITEM_PAYMENT"] - $interest;
						}
						if($prinPay < 0){
							$prinPay = 0;
						}
					}else{
						$prinPay = $rowKPDetail["ITEM_PAYMENT"];
					}
					if($dataCont["CHECK_KEEPING"] == '0'){
						if($dataCont["SPACE_KEEPING"] != 0){
							$int_returnSrc = 0;
							$int_returnFull = $int_returnSrc;
						}
					}
					$paykeepingdet = $cal_loan->paySlipLonDet($conoracle,$dataCont,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payloadTemp,
					$rowProcess["account_no"],$payinslip_no,'LON',$rowKPDetail["SHRLONTYPE_CODE"],$rowKPDetail["DESTINATION"],$prinPay,$interest,
					$intarrear,$int_returnSrc,$interestPeriod,$rowKPDetail["SLIP_SEQ_NO"],true);
					if($paykeepingdet["RESULT"]){
						$ref_noLN = time().$lib->randomText('all',3);
						$repayloan = $cal_loan->repayLoan($conoracle,$rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"],0,$config,$payinslipdoc_no,$dateOperC,
						$srcvcid["ACCOUNT_ID"],$rowProcess["deptslip_no"],$log,$lib,$payloadTemp,$rowProcess["account_no"],$payinslip_no,$rowProcess["member_no"],$ref_noLN,null,0,true);
						if($repayloan["RESULT"]){
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}else if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'SHR'){
					$paykeepingdet = $cal_loan->paySlipDet($conoracle,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payloadTemp,
					$rowProcess["account_no"],$payinslip_no,'SHR',$rowKPDetail["SHRLONTYPE_CODE"],'ค่าหุ้นรายเดือน',$rowKPDetail["SLIP_SEQ_NO"]);
					if($paykeepingdet["RESULT"]){
						$buyshare = $cal_share->buyShare($conoracle,$rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"],0,$config,$payinslipdoc_no,$dateOperC,
						$srcvcid["ACCOUNT_ID"],$rowProcess["deptslip_no"],$log,$lib,$payloadTemp,$rowProcess["account_no"],$payinslip_no,$ref_no,true);
						if($buyshare["RESULT"]){
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $buyshare["RESPONSE_CODE"];
							if(isset($configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale])){
								$arrayResult['RESPONSE_MESSAGE'] = str_replace('${'.$buyshare["TYPE_ERR"].'}',number_format($buyshare["AMOUNT_ERR"],2),$configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale]);
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							}
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}else if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'DEP'){
					$getlastseq_noDest = $cal_dep->getLastSeqNo($rowKPDetail["DESTINATION"]);
					$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$rowKPDetail["DESTINATION"],$srcvcid["ACCOUNT_ID"],'DTM',$rowKPDetail["ITEM_PAYMENT"],0,$dateOperC,$config,
					$log,$rowProcess["account_no"],$payloadTemp,$rowProcess["json_deposit"][$rowKPDetail["SEQ_NO"]],$lib,$getlastseq_noDest["MAX_SEQ_NO"],'PayMonthlyFull',$rowProcess["deptslip_no"]);
					if($depositMoney["RESULT"]){
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $depositMoney["RESPONSE_CODE"];
						if($depositMoney["RESPONSE_CODE"] == "WS0056"){
							$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($depositMoney["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$paykeepingdet = $cal_loan->paySlipDet($conoracle,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payloadTemp,
					$rowProcess["account_no"],$payinslip_no,$rowKPDetail["KEEPITEMTYPE_CODE"],$rowKPDetail["SHRLONTYPE_CODE"],$rowKPDetail["KEEPITEMTYPE_DESC"],$rowKPDetail["SLIP_SEQ_NO"]);
					if($paykeepingdet["RESULT"]){
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$conoracle->rollback();
				$arrayStruc = [
					':member_no' => $payloadTemp["member_no"],
					':id_userlogin' => $payloadTemp["id_userlogin"],
					':operate_date' => $dateOperC,
					':deptaccount_no' => $rowProcess["account_no"],
					':amt_transfer' => $rowKPDetail["ITEM_PAYMENT"],
					':status_flag' => '0',
					':destination' => $rowKPDetail["DESTINATION"],
					':response_code' => "WS0066",
					':response_message' => 'cancel kpslip ไม่ได้'.$cancelSlipLoan->queryString.json_encode([
						':kpslip_no' => $rowProcess['kpslip_no'],
						':seq_no' => $rowKPDetail["SEQ_NO"]
					])
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
		}
		$conoracle->commit();
		$updateFlagStatus = $conmysql->prepare("UPDATE gcprocesspaymentmonthly SET process_status = '1',process_by = :process_by,process_date = NOW() WHERE id_process = :id_process");
		$updateFlagStatus->execute([
			':process_by' => $payload["process_by"],
			':id_process' => $rowProcess["id_process"]
		]);
	}else{
		$conoracle->rollback();
		$arrayResult['RESPONSE_CODE'] = $paykeeping["RESPONSE_CODE"];
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		ob_flush();
		echo json_encode($arrayResult);
		exit();
	}
}
$arrayResult['RESULT'] = TRUE;
ob_flush();
echo json_encode($arrayResult);
exit();
?>