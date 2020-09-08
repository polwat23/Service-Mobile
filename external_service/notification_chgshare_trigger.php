<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('ChangeShare');
$fetchDataChgShr = $conoracle->prepare("SELECT PAYADJUST_DOCNO, OLD_PERIODVALUE,NEW_PERIODVALUE,REMARK,MEMBER_NO
														FROM shpaymentadjust WHERE shrpayadj_status = 1 and sync_notify_flag = '0' and 
														approve_date IS NOT NULL and TRUNC(approve_date) BETWEEN TRUNC(sysdate - 1) and TRUNC(sysdate)");
$fetchDataChgShr->execute();
while($rowChgShr = $fetchDataChgShr->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowChgShr["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["OLD_VALUESHARE"] = number_format($rowChgShr["OLD_PERIODVALUE"],2);
			$dataMerge["NEW_VALUESHARE"] = number_format($rowChgShr["NEW_PERIODVALUE"],2);
			$dataMerge["REMARK"] = $rowChgShr["REMARK"] ?? "";
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
				$updateSyncFlag = $conoracle->prepare("UPDATE shpaymentadjust SET sync_notify_flag = '1' WHERE PAYADJUST_DOCNO = :PAYADJUST_DOCNO");
				$updateSyncFlag->execute([
					':PAYADJUST_DOCNO' => $rowChgShr["PAYADJUST_DOCNO"]
				]);
			}
		}
	}
}
?>