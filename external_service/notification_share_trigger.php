<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT share_itemtype_code FROM smsconstantshare WHERE allow_smsconstantshare = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["share_itemtype_code"]."'";
}
$templateMessage = $func->getTemplateSystem('ShareInfo',1);
$fetchDataSTM = $conoracle->prepare("SELECT SHS.SEQ_NO,SHS.OPERATE_DATE,SHS.MEMBER_NO,(SHS.SHARE_AMOUNT * 10) AS AMOUNT,
												(SHS.SHARESTK_AMT * 10) AS SHARE_BALANCE,SHI.SHRITEMTYPE_DESC
												FROM SHSHARESTATEMENT SHS LEFT JOIN SHUCFSHRITEMTYPE SHI ON SHS.SHRITEMTYPE_CODE = SHI.SHRITEMTYPE_CODE
												WHERE SHS.OPERATE_DATE BETWEEN (SYSDATE - 2) and SYSDATE AND SHS.SYNC_NOTIFY_FLAG = '0' AND SHS.SHRITEMTYPE_CODE IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
			$dataMerge["SHARE_BALANCE"] = number_format($rowSTM["SHARE_BALANCE"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["SHRITEMTYPE_DESC"];
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
				$updateSyncFlag = $conoracle->prepare("UPDATE shsharestatement SET sync_notify_flag = '1' WHERE member_no = :member_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
	foreach($arrToken["LIST_SEND_HW"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
			$dataMerge["SHARE_BALANCE"] = number_format($rowSTM["SHARE_BALANCE"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["SHRITEMTYPE_DESC"];
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
				$updateSyncFlag = $conoracle->prepare("UPDATE shsharestatement SET sync_notify_flag = '1' WHERE member_no = :member_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
}
?>