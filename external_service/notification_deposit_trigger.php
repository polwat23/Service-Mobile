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
$formatDep = $func->getConstant('hidden_dep');
$templateMessage = $func->getTemplateSystem('DepositInfo',1);
$fetchDataSTM = $conoracle->prepare("SELECT dsm.PRNCBAL,dsm.DEPTACCOUNT_NO,dit.DEPTITEMTYPE_DESC_TH AS DEPTITEMTYPE_DESC,dsm.DEPTITEM_AMT as AMOUNT,
									dm.MEMBER_NO,dsm.OPERATE_DATE,dsm.SEQ_NO,dsm.ENTRY_TIME
									FROM dpdeptstatement dsm LEFT JOIN dpucfdeptitemtype dit ON dsm.deptitemtype_code = dit.deptitemtype_code
									LEFT JOIN dpdeptmaster dm ON dsm.deptaccount_no = dm.deptaccount_no and dsm.branch_id = dm.branch_id
									WHERE dsm.operate_date BETWEEN (SYSDATE - 2) and SYSDATE and dsm.sync_notify_flag = '0' and dsm.deptitemtype_code IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$getData = $conoracle->prepare("SELECT * FROM dpdeptstatement WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
			$getData->execute([
				':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
				':seq_no' => $rowSTM["SEQ_NO"]
			]);
			$rowData = $getData->fetch(PDO::FETCH_ASSOC);
			$insertLog = $conmysql->prepare("INSERT INTO logtriggernotifystatement(log_data,member_no)
											VALUES(:log_data,:member_no)");
			$insertLog->execute([
				':log_data' => json_encode($rowData),
				':member_no' => $rowSTM["MEMBER_NO"]
			]);
			$dataMerge = array();
			$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$formatDep);
			$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
			$dataMerge["BALANCE"] = number_format($rowSTM["PRNCBAL"],2);
			$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
			$dataMerge["DATETIME"] = isset($rowSTM["ENTRY_TIME"]) && $rowSTM["ENTRY_TIME"] != '' ?
			$lib->convertdate($rowSTM["ENTRY_TIME"],'D m Y',true) : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
			$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
			$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
			$arrMessage["BODY"] = $message_endpoint["BODY"];
			$arrMessage["PATH_IMAGE"] = null;
			$arrPayloadNotify["PAYLOAD"] = $arrMessage;
			$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
			$arrPayloadNotify["SEND_BY"] = 'system';
			$arrPayloadNotify["TYPE_NOTIFY"] = "2";
			if($lib->sendNotify($arrPayloadNotify,"person")){
				$func->insertHistory($arrPayloadNotify,'2');
				$updateSyncFlag = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}
		}
	}
}
?>