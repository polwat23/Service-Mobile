<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT dept_itemtype_code FROM smsconstantdept WHERE allow_smsconstantdept = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["dept_itemtype_code"]."'";
}
$formatDept = $func->getConstant('hidden_dep');
$templateMessage = $func->getTemplateSystem('DepositInfo',1);
$fetchDataSTM = $conmssql->prepare("SELECT dsm.PRNCBAL,dsm.DEPTACCOUNT_NO,dit.DEPTITEMTYPE_DESC,dsm.DEPTITEM_AMT as AMOUNT,dm.MEMBER_NO,dsm.OPERATE_DATE,dsm.SEQ_NO
									FROM dpdeptstatement dsm LEFT JOIN dpucfdeptitemtype dit ON dsm.deptitemtype_code = dit.deptitemtype_code
									LEFT JOIN dpdeptmaster dm ON dsm.deptaccount_no = dm.deptaccount_no and dsm.coop_id = dm.coop_id
									WHERE dsm.operate_date BETWEEN (GETDATE() - 2) and GETDATE() and (dsm.sync_notify_flag IS NULL OR dsm.sync_notify_flag = '0') and dsm.deptitemtype_code IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$formatDept);
			$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
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
			if($lib->sendNotify($arrPayloadNotify,"person")){
				$func->insertHistory($arrPayloadNotify,'2');
				$updateSyncFlag = $conmssql->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
}
?>