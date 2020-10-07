<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('AssistReceive');
$fetchDataAss = $conoracle->prepare("SELECT ast.ASSISTTYPE_DESC,asm.APPROVE_DATE,asm.APPROVE_AMT,asm.MEMBER_NO,asm.ASSIST_DOCNO
									FROM assreqmaster asm LEFT JOIN 
									assucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE and asm.coop_id = ast.coop_id 
									WHERE asm.req_status = 1 and asm.sync_notify_flag = '0' and TRUNC(asm.APPROVE_DATE) BETWEEN TRUNC(SYSDATE - 2) and TRUNC(SYSDATE)");
$fetchDataAss->execute();
while($rowAss = $fetchDataAss->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowAss["MEMBER_NO"]);
	foreach($arrToken["LIST_SEND"] as $dest){
		if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
			$dataMerge = array();
			$dataMerge["ASSISTTYPE_DESC"] = $rowAss["ASSISTTYPE_DESC"];
			$dataMerge["APPROVE_AMT"] = $rowAss["APPROVE_AMT"];
			$dataMerge["APPROVE_DATE"] = $lib->convertdate($rowAss["APPROVE_DATE"],'D m Y');
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
				$updateSyncFlag = $conoracle->prepare("UPDATE assreqmaster SET sync_notify_flag = '1' WHERE ASSIST_DOCNO = :ASSIST_DOCNO");
				$updateSyncFlag->execute([
					':ASSIST_DOCNO' => $rowAss["ASSIST_DOCNO"]
				]);
			}
		}
	}
}
?>
