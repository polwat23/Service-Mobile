<?php
require_once('../autoloadConnection.php');

$fetchDataSTM = $conoracle->prepare("SELECT lut.loanitemtype_desc,lcn.loancontract_no,lcn.entry_date,lcm.member_no,
									lcn.principal_payment,lcn.interest_payment,lcn.principal_balance
									from lncontstatement lcn LEFT JOIN lncontmaster lcm ON lcn.loancontract_no = lcm.loancontract_no
									LEFT JOIN lnucfloanitemtype lut ON lcn.loanitemtype_code = lut.loanitemtype_code
									WHERE lcn.sync_flag = '0' ");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch()){
	$arrToken = $func->getFCMToken('person',array($rowSTM["MEMBER_NO"]));
	$templateMessage = $func->getTemplatSystem('DepositInfo',1);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["LOANCONTRACT_NO"] = $rowSTM["LOANCONTRACT_NO"];
		$dataMerge["PRINCIPAL_PAYMENT"] = number_format($rowSTM["PRINCIPAL_PAYMENT"],2);
		$dataMerge["INTEREST_PAYMENT"] = number_format($rowSTM["INTEREST_PAYMENT"],2);
		$dataMerge["PRINCIPAL_BALANCE"] = number_format($rowSTM["PRINCIPAL_BALANCE"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["LOANITEMTYPE_DESC"];
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