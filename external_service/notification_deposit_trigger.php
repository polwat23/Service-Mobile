<?php
require_once('../autoloadConnection.php');

$fetchDataSTM = $conoracle->prepare("SELECT dsm.PRNCBAL,dsm.DEPTACCOUNT_NO,dit.DEPTITEMTYPE_DESC,dsm.DEPTITEM_AMT,dm.MEMBER_NO,dsm.ENTRY_DATE
									FROM dpdeptstatement dsm LEFT JOIN dpucfdeptitemtype dit ON dsm.deptitemtype_code = dit.deptitemtype_code
									LEFT JOIN dpdeptmaster dm ON dsm.deptaccount_no = dm.deptaccount_no
									WHERE dsm.sync_flag = '0' ");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch()){
	$arrToken = $func->getFCMToken('person',array($rowSTM["MEMBER_NO"]));
	$templateMessage = $func->getTemplatSystem('DepositInfo',1);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
		$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
		$dataMerge["DATETIME"] = isset($rowSTM["ENTRY_DATE"]) && $rowSTM["ENTRY_DATE"] != '' ? 
		$lib->convertdate($rowSTM["ENTRY_DATE"],'D m Y',true) : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
		$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		$arrMessage["PATH_IMAGE"] = null;
		$arrPayloadNotify["PAYLOAD"] = $arrMessage;
		$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
		if($func->insertHistory($arrPayloadNotify,'2')){
			$lib->sendNotify($arrPayloadNotify,"person");
		}
	}
}
?>