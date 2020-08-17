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


$templateMessage = $func->getTemplateSystem('PayOutAssistEtc',1);
if(isset($templateMessage)){
	$queryTarget = $conoracle->prepare("SELECT slo.assistslip_no,slo.member_no,slo.payoutnet_amt,slu.assisttype_desc,slo.slip_date
										FROM assslippayout slo LEFT JOIN assucfassisttype slu ON slo.assisttype_code = slu.assisttype_code
										WHERE slo.slip_date BETWEEN (SYSDATE - 2) and SYSDATE and slo.sync_notify_flag = '0' and slo.member_no = '00017583'");
	$queryTarget->execute();
	while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
		$arrTarget = array();
		$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["SLIP_DATE"],'D m Y');
		$arrTarget["PAYOUTNET_AMT"] = number_format($rowTarget["PAYOUTNET_AMT"],2);
		$arrTarget["ASSISTTYPE_DESC"] = $rowTarget["ASSISTTYPE_DESC"];
		$arrMessage = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$arrTarget);
		$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"]);
		if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
			$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
			$arraySendSMS = $lib->sendSMS($arrayDest);
			if($arraySendSMS["RESULT"]){
				$arrayTelLog = array();
				$arrayComing["TEL"] = $arrayTel[0]["TEL"];
				$arrayComing["MEMBER_NO"] = $rowTarget["MEMBER_NO"];
				$arrayTelLog[] = $arrayComing;
				$func->logSMSWasSent(null,$arrMessage["BODY"],$arrayTelLog,'system');
				$updateFlag = $conoracle->prepare("UPDATE assslippayout SET sync_notify_flag = '1' WHERE assistslip_no = :assistslip_no");
				$updateFlag->execute([':assistslip_no' => $rowTarget["ASSISTSLIP_NO"]]);
			}
		}
	}
}

?>