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


$queryTarget = $conoracle->prepare("SELECT id_logsent , sms_message  , member_no,tel_mobile,send_date  ,send_by,id_smstemplate ,is_sendahead  FROM smstemplogwassent order by id_logsent ASC");
$queryTarget->execute();
$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
	$arrTarget = array();
	$arrTarget["ID_LOGSENT"] = $rowTarget["ID_LOGSENT"];
	$arrTarget["SMS_MESSAGE"] = $rowTarget["SMS_MESSAGE"];
	$arrTarget["MEMBER_NO"] = $rowTarget["MEMBER_NO"];
	$arrTarget["TEL_MOBILE"] = $rowTarget["TEL_MOBILE"];
	$arrTarget["SEND_BY"] = $rowTarget["SEND_BY"];
	$arrTarget["ID_SMSTEMPLATE"] = $rowTarget["ID_SMSTEMPLATE"];

	if(isset($rowTarget["TEL_MOBILE"]) && $rowTarget["TEL_MOBILE"] != ""){
		$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowTarget["TEL_MOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($rowTarget["SMS_MESSAGE"]);
		$arraySendSMS = $lib->sendSMS($arrayDest);
		if($arraySendSMS["RESULT"]){
			$id_logsent = $func->getMaxTable('id_logsent' , 'smslogwassent',$conoracle);
			$textcombine = "('".$id_logsent."','".$rowTarget["SMS_MESSAGE"]."','".$rowTarget["MEMBER_NO"]."','".$rowTarget["TEL_MOBILE"]."','".$rowTarget["SEND_BY"]."'".(isset($rowTarget["ID_SMSTEMPLATE"]) ? ",".$rowTarget["ID_SMSTEMPLATE"] : ",null").")";
			$insertToLogSMS = $conoracle->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
												VALUES".$textcombine);
			if($insertToLogSMS->execute()){
				$delTempSms = $conoracle->prepare("DELETE FROM smstemplogwassent WHERE id_logsent = :id_logsent");
				$delTempSms->execute([':id_logsent'=> $rowTarget["ID_LOGSENT"]]);
			}
		}
	}
}
?>