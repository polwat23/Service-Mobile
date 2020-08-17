<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/validate_input.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$templateMessage = $func->getTemplateSystem('DepositInfo',1);
$arrToken = $func->getFCMToken('person',strtolower($lib->mb_str_pad($dataComing["member_no"])));
foreach($arrToken["LIST_SEND"] as $dest){
	$dataMerge = array();
	$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($dataComing["deptaccount_no"],$func->getConstant('hidden_dep'));
	$dataMerge["AMOUNT"] = number_format($dataComing["amount"],2);
	$dataMerge["ITEMTYPE_DESC"] = $dataComing["deptitem_desc"];
	$dataMerge["DATETIME"] = isset($dataComing["operate_date"]) && $dataComing["operate_date"] != '' ? 
	$lib->convertdate($dataComing["operate_date"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
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
		echo true;
		exit();
	}
	echo false;
	exit();
}
echo false;
exit();
?>