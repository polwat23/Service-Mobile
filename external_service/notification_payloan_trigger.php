<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT loan_itemtype_code FROM smsconstantloan WHERE allow_smsconstantloan = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["loan_itemtype_code"]."'";
}
$templateMessage = $func->getTemplateSystem('LoanPayout',1);
$fetchDataSTM = $conmssql->prepare("SELECT LN.LOANTYPE_DESC,SO.PAYOUTSLIP_NO,SO.MEMBER_NO,SO.PAYOUT_AMT,
																	SO.PAYOUTNET_AMT,SO.PAYOUTCLR_AMT,CONVERT(VARCHAR,SO.SLIP_DATE,20) as SLIP_DATE,SO.LOANCONTRACT_NO
																	FROM slslippayout so
																	LEFT JOIN lnloantype ln ON so.shrlontype_code = ln.loantype_code
																	WHERE so.slip_status = '1' and so.sliptype_code = 'LWD' 
																	and CONVERT(varchar,so.slip_date,112) = CONVERT(varchar,GETDATE(),112)");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["LOANTYPE_DESC"] = $rowSTM["LOANTYPE_DESC"];
			$dataMerge["CONTRACT_NO"] = $rowSTM["LOANCONTRACT_NO"];
			$dataMerge["PAYOUT_AMT"] = number_format($rowSTM["PAYOUT_AMT"],2);
			$dataMerge["PAYOUT_AMT_PRIN"] = number_format($rowSTM["PAYOUTNET_AMT"],2);
			$dataMerge["PAYOUT_CLR"] = number_format($rowSTM["PAYOUTCLR_AMT"],2);
			$dataMerge["DATETIME"] = isset($rowSTM["SLIP_DATE"]) && $rowSTM["SLIP_DATE"] != '' ? 
			$lib->convertdate($rowSTM["SLIP_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE slslippayout SET sync_notify_flag = '1' WHERE PAYOUTSLIP_NO = :payoutslip_no");
				$updateSyncFlag->execute([
					':payoutslip_no' => $rowSTM["payoutslip_no"]
				]);
			}
		}
	}
	foreach($arrToken["LIST_SEND_HW"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["LOANTYPE_DESC"] = $rowSTM["LOANTYPE_DESC"];
			$dataMerge["CONTRACT_NO"] = $rowSTM["LOANCONTRACT_NO"];
			$dataMerge["PAYOUT_AMT"] = number_format($rowSTM["PAYOUT_AMT"],2);
			$dataMerge["PAYOUT_AMT_PRIN"] = number_format($rowSTM["PAYOUTNET_AMT"],2);
			$dataMerge["PAYOUT_CLR"] = number_format($rowSTM["PAYOUTCLR_AMT"],2);
			$dataMerge["DATETIME"] = isset($rowSTM["SLIP_DATE"]) && $rowSTM["SLIP_DATE"] != '' ? 
			$lib->convertdate($rowSTM["SLIP_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE slslippayout SET sync_notify_flag = '1' WHERE PAYOUTSLIP_NO = :payoutslip_no");
				$updateSyncFlag->execute([
					':payoutslip_no' => $rowSTM["payoutslip_no"]
				]);
			}
		}
	}
}
?>