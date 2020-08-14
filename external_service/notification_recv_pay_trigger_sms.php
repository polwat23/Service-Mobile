<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);


$templateMessage = $func->getTemplateSystem('ReceivePayment',1);
if(isset($templateMessage)){
	$queryTarget = $conoracle->prepare("SELECT slslippayin.payinslip_no,slslippayin.slip_date,slslippayin.member_no,slslippayindet.slipitem_desc,
										TRIM(TO_CHAR(slslippayindet.item_payamt, '999,999,999,999.99')) as item_payamt 
										FROM slslippayin LEFT JOIN slslippayindet ON slslippayin.payinslip_no = slslippayindet.payinslip_no
										WHERE slslippayin.sync_notify_flag = '0' and slslippayin.slip_date BETWEEN (SYSDATE - 2) and SYSDATE
										and slslippayin.member_no = '00017583'");
	$queryTarget->execute();
	while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
		$arrTarget = array();
		$arrTarget["SLIPITEM_DESC"] = $rowTarget["SLIPITEM_DESC"];
		$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["SLIP_DATE"],'D m Y');
		$arrTarget["ITEM_PAYAMT"] = $rowTarget["ITEM_PAYAMT"];
		$arrMessage = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$arrTarget);
		$arrayTel = $func->getSMSPerson('person',array($rowTarget["MEMBER_NO"]));
		if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
			$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
			$arraySendSMS = $lib->sendSMS($arrayDest);
			if($arraySendSMS["RESULT"]){
				$arrayTelLog = array();
				$arrayComing["TEL"] = $arrayTel[0]["TEL"];
				$arrayComing["MEMBER_NO"] = $rowTarget["MEMBER_NO"];
				$arrayTelLog[] = $arrayComing;
				$func->logSMSWasSent(null,$arrMessage["BODY"],$arrayTelLog,'system');
				$updateFlag = $conoracle->prepare("UPDATE slslippayin SET sync_notify_flag = '1' WHERE payinslip_no = :payinslip_no");
				$updateFlag->execute([':payinslip_no' => $rowTarget["PAYINSLIP_NO"]]);
			}
		}
	}
}

?>