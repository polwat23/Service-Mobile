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
$templateMessage = $func->getTemplateSystem('LoanInfo',1);
$fetchDataSTM = $conmssql->prepare("SELECT LUT.LOANITEMTYPE_DESC,LCN.LOANCONTRACT_NO,LCN.OPERATE_DATE,LCM.MEMBER_NO,LCN.SEQ_NO,
									LCN.PRINCIPAL_PAYMENT,LCN.INTEREST_PAYMENT,LCN.PRINCIPAL_BALANCE
									from lncontstatement lcn LEFT JOIN lncontmaster lcm ON lcn.loancontract_no = lcm.loancontract_no
									LEFT JOIN lnucfloanitemtype lut ON lcn.loanitemtype_code = lut.loanitemtype_code
									WHERE lcn.operate_date BETWEEN (GETDATE() - 2) and GETDATE() and (lcn.sync_notify_flag IS NULL OR lcn.sync_notify_flag = '0') and lcn.loanitemtype_code IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$contract_no = $rowSTM["LOANCONTRACT_NO"];
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$dataMerge["LOANCONTRACT_NO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$dataMerge["LOANCONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$dataMerge["LOANCONTRACT_NO"] = $contract_no;
			}
			$dataMerge["PRINCIPAL_PAYMENT"] = number_format($rowSTM["PRINCIPAL_PAYMENT"],2);
			$dataMerge["INTEREST_PAYMENT"] = number_format($rowSTM["INTEREST_PAYMENT"],2);
			$dataMerge["PRINCIPAL_BALANCE"] = number_format($rowSTM["PRINCIPAL_BALANCE"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["LOANITEMTYPE_DESC"];
			$dataMerge["DATETIME"] = isset($rowSTM["OPERATE_DATE"]) && $rowSTM["OPERATE_DATE"] != '' ? 
			$lib->convertdate($rowSTM["OPERATE_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE loancontract_no = :loancontract_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':loancontract_no' => $rowSTM["LOANCONTRACT_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
	foreach($arrToken["LIST_SEND_HW"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$contract_no = $rowSTM["LOANCONTRACT_NO"];
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$dataMerge["LOANCONTRACT_NO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$dataMerge["LOANCONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$dataMerge["LOANCONTRACT_NO"] = $contract_no;
			}
			$dataMerge["PRINCIPAL_PAYMENT"] = number_format($rowSTM["PRINCIPAL_PAYMENT"],2);
			$dataMerge["INTEREST_PAYMENT"] = number_format($rowSTM["INTEREST_PAYMENT"],2);
			$dataMerge["PRINCIPAL_BALANCE"] = number_format($rowSTM["PRINCIPAL_BALANCE"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["LOANITEMTYPE_DESC"];
			$dataMerge["DATETIME"] = isset($rowSTM["OPERATE_DATE"]) && $rowSTM["OPERATE_DATE"] != '' ? 
			$lib->convertdate($rowSTM["OPERATE_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
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
				$updateSyncFlag = $conmssql->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE loancontract_no = :loancontract_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':loancontract_no' => $rowSTM["LOANCONTRACT_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
}
?>