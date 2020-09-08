<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('AssistReceive');
$fetchDataAss = $conoracle->prepare("select asp.PAYOUTSLIP_NO,asp.PAYOUT_AMT,asp.MEMBER_NO,asp.MONEYTYPE_CODE,cmt.MONEYTYPE_DESC,
															asp.DEPACCOUNT_NO,ast.ASSISTTYPE_DESC
															from asnslippayout asp LEFT JOIN asnucfassisttype ast ON asp.assisttype_code = ast.assisttype_code and ast.coop_id = '001001'
															LEFT JOIN cmucfmoneytype cmt ON asp.MONEYTYPE_CODE = cmt.MONEYTYPE_CODE
															where asp.capital_year = (EXTRACT(year FROM sysdate) +543) and asp.slip_status = '1' and asp.sync_notify_flag = '0' and 
															TRUNC(asp.operate_date) BETWEEN TRUNC(sysdate - 1) and TRUNC(sysdate)");
$fetchDataAss->execute();
while($rowAss = $fetchDataAss->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowAss["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["ASSISTTYPE_DESC"] = $rowAss["ASSISTTYPE_DESC"];
			$dataMerge["MONEYTYPE"] = $rowAss["MONEYTYPE_DESC"];
			$dataMerge["DEPTACCOUNT_NO"] = $rowAss["MONEYTYPE_CODE"] == 'TRN' ?  $lib->formataccount_hidden($lib->formataccount($rowAss["DEPACCOUNT_NO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep')) : "";
			$dataMerge["PAYOUT_AMT"] = number_format($rowAss["PAYOUT_AMT"],2);
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
				$updateSyncFlag = $conoracle->prepare("UPDATE asnslippayout SET sync_notify_flag = '1' WHERE PAYOUTSLIP_NO = :PAYOUTSLIP_NO");
				$updateSyncFlag->execute([
					':PAYOUTSLIP_NO' => $rowAss["PAYOUTSLIP_NO"]
				]);
			}
		}
	}
}
?>