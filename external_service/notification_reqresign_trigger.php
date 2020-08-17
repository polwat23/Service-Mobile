<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('ResignMember');
$fetchReqResignList = $conoracle->prepare("select mrr.resignreq_docno,mrr.member_no,muc.resigncause_desc from mbreqresign mrr 
												LEFT JOIN mbucfresigncause muc ON mrr.resigncause_code = muc.resigncause_code
												where mrr.resignreq_status = 8 and TRUNC(mrr.resignreq_date) BETWEEN TRUNC(sysdate-40) and TRUNC(sysdate) and mrr.sync_notify_flag = '0'");
$fetchReqResignList->execute();
while($rowReqResign = $fetchReqResignList->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowReqResign["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["RESIGN_DESC"] = $rowReqResign["RESIGNCAUSE_DESC"];
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
		$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		$arrMessage["PATH_IMAGE"] = null;
		$arrPayloadNotify["PAYLOAD"] = $arrMessage;
		$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
		if($lib->sendNotify($arrPayloadNotify,"person")){
			$func->insertHistory($arrPayloadNotify,'2');
			$updateSyncFlag = $conoracle->prepare("UPDATE mbreqresign SET sync_notify_flag = '1' WHERE RESIGNREQ_DOCNO = :RESIGNREQ_DOCNO");
			$updateSyncFlag->execute([
				':RESIGNREQ_DOCNO' => $rowReqResign["RESIGNREQ_DOCNO"]
			]);
		}
	}
}
?>