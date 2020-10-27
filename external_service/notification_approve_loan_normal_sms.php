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


$templateMessage = $func->getTemplateSystem('ApproveLoanNormal',1);
if(isset($templateMessage)){
	$queryTarget = $conoracle->prepare("SELECT slo.payoutslip_no,slo.loancontract_no,slo.member_no,slo.slip_date,
									TRIM(TO_CHAR(slo.payoutnet_amt, '999,999,999,999.99')) as payoutnet_amt 
									from slslippayout slo where slo.sliptype_code = 'LWD' and slo.sync_notify_flag = '0'
									and slo.slip_date BETWEEN (SYSDATE - 2) and SYSDATE and slo.member_no = '00017583'");
	$queryTarget->execute();
	while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
		$arrTarget = array();
		$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
		$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["SLIP_DATE"],'D m Y');
		$arrTarget["PAYOUTNET_AMT"] = $rowTarget["PAYOUTNET_AMT"];
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
				$updateFlag = $conoracle->prepare("UPDATE slslippayout SET sync_notify_flag = '1' WHERE payoutslip_no = :payoutslip_no");
				$updateFlag->execute([':payoutslip_no' => $rowTarget["PAYOUTSLIP_NO"]]);
			}
		}
	}
}

?>