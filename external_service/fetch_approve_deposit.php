<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$arrayGrp = array();
$templateMessage = $func->getTemplateSystem('ApproveDeposit',1);
$fetchApvDept = $conoracle->prepare("SELECT dpa.apv_docno,dpa.remark,aml.app_score,amu.full_name
													FROM dpdeptapprove dpa LEFT JOIN amsecapvlevel aml ON dpa.APV_LEVEL = aml.apvlevel_id
													LEFT JOIN amsecusers amu ON TRIM(dpa.user_id) = amu.user_name
													WHERE dpa.apv_status = 8 and dpa.sync_notify_flag = '0' and dpa.entry_date BETWEEN (SYSDATE - 2) and SYSDATE");
$fetchApvDept->execute();
while($rowApv = $fetchApvDept->fetch(PDO::FETCH_ASSOC)){
	$arrMember = array();
	$fetchUsernameCanApv = $conoracle->prepare("SELECT amu.member_no FROM amsecusers amu LEFT JOIN amsecapvlevel aml ON amu.APVLEVEL_ID = aml.APVLEVEL_ID
															WHERE amu.user_status = 1 and aml.APP_SCORE >= :apv_score");
	$fetchUsernameCanApv->execute([':apv_score' => $rowApv["APP_SCORE"]]);
	while($rowUsername = $fetchUsernameCanApv->fetch(PDO::FETCH_ASSOC)){
		$arrMember[] = $rowUsername["MEMBER_NO"];
	}
	$arrToken = $func->getFCMToken('person',$arrMember);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["REQ_NAME"] = $rowApv["FULL_NAME"];
		$dataMerge["REQ_DESC"] = $rowApv["REMARK"];
		$dataMerge["APV_DOCNO"] = $rowApv["APV_DOCNO"];
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
		$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		$arrMessage["PATH_IMAGE"] = null;
		$arrPayloadNotify["PAYLOAD"] = $arrMessage;
		$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
		if($func->insertHistory($arrPayloadNotify,'2')){
			if($lib->sendNotify($arrPayloadNotify,"person")){
				$updateSyncFlag = $conoracle->prepare("UPDATE dpdeptapprove SET sync_notify_flag = '1' WHERE apv_docno = :apv_docno");
				$updateSyncFlag->execute([
					':apv_docno' => $rowApv["APV_DOCNO"]
				]);
			}
		}
	}
}
?>