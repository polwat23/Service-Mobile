<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$fetchDataSTM = $conmssql->prepare("SELECT LOANREQUEST_DOCNO,MEMBER_NO,APPROVE_DATE,LOANTYPE_CODE
									FROM lnreqloan where approve_date BETWEEN (GETDATE() - 2) and GETDATE() 
									and loanrequest_status = '1' and (sync_notify_flag IS NULL OR sync_notify_flag = '0') ");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$templateMessage = $func->getTemplateSystem('NotkeepingReceive',2);
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["APPL_DOCNO"] = $rowSTM["LOANREQUEST_DOCNO"];
			$dataMerge["APPROVE_DATE"] = $lib->convertdate($rowSTM["APPROVE_DATE"],'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE lnreqloan SET sync_notify_flag = '1' WHERE LOANREQUEST_DOCNO = :loanrequest_docno");
				$updateSyncFlag->execute([
					':loanrequest_docno' => $rowSTM["LOANREQUEST_DOCNO"]
				]);
			}
		}
	}
	foreach($arrToken["LIST_SEND_HW"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["APPL_DOCNO"] = $rowSTM["LOANREQUEST_DOCNO"];
			$dataMerge["APPROVE_DATE"] = $lib->convertdate($rowSTM["APPROVE_DATE"],'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE lnreqloan SET sync_notify_flag = '1' WHERE LOANREQUEST_DOCNO = :loanrequest_docno");
				$updateSyncFlag->execute([
					':loanrequest_docno' => $rowSTM["LOANREQUEST_DOCNO"]
				]);
			}
		}
	}
}
?>