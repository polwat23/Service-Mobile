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


$templateMessage = $func->getTemplateSystem('PayLonSpecial',1);
if(isset($templateMessage)){
	$queryTarget = $conoracle->prepare("SELECT sli.payinslip_no,sli.member_no,sld.slipitem_desc,
									TRIM(TO_CHAR(sld.item_payamt, '999,999,999,999.99')) as item_payamt,
									sli.slip_date,
									sld.loancontract_no from slslippayin sli LEFT JOIN slslippayindet sld ON sli.payinslip_no = sld.payinslip_no 
									where TRIM(sli.sliptype_code) = 'PX' and sld.slipitemtype_code = 'LON' and sli.sync_notify_flag = '0'
									and sli.slip_status = '1' and sli.slip_date BETWEEN (SYSDATE - 30) and SYSDATE and sli.member_no = '00018790'");
	$queryTarget->execute();
	while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
		$arrTarget = array();
		$arrTarget["SLIPITEM_DESC"] = $rowTarget["SLIPITEM_DESC"];
		$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["SLIP_DATE"],'D m Y');
		$arrTarget["ITEM_PAYAMT"] = $rowTarget["ITEM_PAYAMT"];
		$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
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