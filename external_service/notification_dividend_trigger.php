<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('ReceiveDividend',1);
$fetchDataDiv = $conmssql->prepare("SELECT yrs.DIV_YEAR,yrs.PAYOUT_PAYMENT AS PAYOUT_AMT,yrs.SLIP_DATE,yrd.EXPENSE_ACCID as BANK_ACCOUNT,
									CM.BANK_DESC AS BANK,yucf.methpaytype_desc as TYPE_DESC,yrs.MEMBER_NO
									FROM yrslippayout yrs LEFT JOIN yrslippayoutdet yrd ON yrs.payoutslip_no = yrd.payoutslip_no
									LEFT JOIN yrucfmethpay yucf ON yrd.methpaytype_code = yucf.methpaytype_code
									LEFT JOIN CMUCFMONEYTYPE CUCF ON yrd.MONEYTYPE_CODE = CUCF.MONEYTYPE_CODE
									LEFT JOIN CMUCFBANK CM ON yrd.EXPENSE_BANK = CM.BANK_CODE
									WHERE yrs.SLIP_DATE BETWEEN (GETDATE() - 5) and GETDATE() and (yrs.sync_notify_flag IS NULL OR yrs.sync_notify_flag = '0')
									and yrd.methpaytype_code IN('CBT','DEP','CSH')");
$fetchDataDiv->execute();
while($rowSTM = $fetchDataDiv->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["PAYOUT_AMT"] = number_format($rowSTM["PAYOUT_AMT"],2);
			$dataMerge["DIV_YEAR"] = $rowSTM["DIV_YEAR"];
			$dataMerge["RECEIVE_TEXT"] = $rowSTM["TYPE_DESC"].' '.$rowSTM["BANK_DESC"].' '.$lib->formataccount($rowSTM["EXPENSE_ACCID"],$func->getConstant('dep_format'));
			$dataMerge["PAY_DATE"] = $lib->convertdate($rowSTM["SLIP_DATE"],'D m Y');
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
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
				$updateSyncFlag = $conmssql->prepare("UPDATE yrslippayout SET sync_notify_flag = '1' WHERE member_no = :member_no and div_year = :div_year");
				$updateSyncFlag->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':div_year' => $rowSTM["DIV_YEAR"]
				]);
			}
		}
	}
	foreach($arrToken["LIST_SEND_HW"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["PAYOUT_AMT"] = number_format($rowSTM["PAYOUT_AMT"],2);
			$dataMerge["DIV_YEAR"] = $rowSTM["DIV_YEAR"];
			$dataMerge["RECEIVE_TEXT"] = $rowSTM["TYPE_DESC"].' '.$rowSTM["BANK_DESC"].' '.$lib->formataccount($rowSTM["EXPENSE_ACCID"],$func->getConstant('dep_format'));
			$dataMerge["PAY_DATE"] = $lib->convertdate($rowSTM["SLIP_DATE"],'D m Y');
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
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
				$updateSyncFlag = $conmssql->prepare("UPDATE yrslippayout SET sync_notify_flag = '1' WHERE member_no = :member_no and div_year = :div_year");
				$updateSyncFlag->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':div_year' => $rowSTM["DIV_YEAR"]
				]);
			}
		}
	}
}
?>