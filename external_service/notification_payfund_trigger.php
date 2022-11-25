<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT mb.member_no FROM gcmemberaccount mb LEFT JOIN gcuserlogin log on mb.member_no = log.member_no WHERE log.is_login = '1' and log.channel = 'mobile_app' and mb.fcm_token IS NOT NULL");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["member_no"]."'";
}

$chunk = array_chunk($arrayStmItem, 1000);
	
foreach($chunk as $dest){
	$fetchDataSTM = $conoracle->prepare("SELECT 
					WD.DEPTACCOUNT_NO
					FROM WCFUNDMASTER WF
					LEFT JOIN WCDEPTMASTER WD ON (WD.DEPTACCOUNT_NO = WF.DEPTACCOUNT_NO) 
					WHERE 
					WF.FUNDTYPE_CODE IN ('001', '002') and WD.DEPTACCOUNT_NO in (" . implode(',', $dest) . ")
					");
	$fetchDataSTM->execute();
	while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){

		$arrToken = $func->getFCMToken('person',$rowSTM["DEPTACCOUNT_NO"]);
		foreach($arrToken["LIST_SEND"] as $dest){
			if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
				$arrMessage = array();
				$arrPayloadNotify = array();
				$arrMessage["SUBJECT"] = "📢 ประกาศ กสธท.";
				$arrMessage["BODY"] = "กสธท. ล้านที่ 2 , ล้านที่ 3👉🏻แจ้งเตือนให้ชำระเงินค่าเบี้ยประกันชีวิตต่ออายุประจำปี 2566 ‼️โดยให้ท่านชำระผ่านศูนย์ประสานงาน หรือชำระด้วยตนเอง (สำหรับสมาชิกสมัครตรง) ได้ตั้งแต่วันนี้เป็นต้นไป (รายละเอียดตามเอกสารแนบ)🙏🏻";
				$arrMessage["PATH_IMAGE"] = $config["URL_SERVICE"] . "/resource/utility_icon/fund_preiod_256512.jpg";
				$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
				$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				$arrPayloadNotify["SEND_BY"] = 'system';
				$arrPayloadNotify["TYPE_NOTIFY"] = "2";
				if($lib->sendNotify($arrPayloadNotify,"person")){
					$func->insertHistory($arrPayloadNotify,'2');
				}
			}
		}
		
		foreach($arrToken["LIST_SEND_HW"] as $dest){
			if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
				$arrMessage = array();
				$arrPayloadNotify = array();
				$arrMessage["SUBJECT"] = "📢 ประกาศ กสธท.";
				$arrMessage["BODY"] = "กสธท. ล้านที่ 2 , ล้านที่ 3👉🏻แจ้งเตือนให้ชำระเงินค่าเบี้ยประกันชีวิตต่ออายุประจำปี 2566 ‼️โดยให้ท่านชำระผ่านศูนย์ประสานงาน หรือชำระด้วยตนเอง (สำหรับสมาชิกสมัครตรง) ได้ตั้งแต่วันนี้เป็นต้นไป (รายละเอียดตามเอกสารแนบ)🙏🏻";
				$arrMessage["PATH_IMAGE"] = "http://cpct.coopsiam.com/service-cpct/resource/utility_icon/fund_preiod_256512.jpg";
				$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
				$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				$arrPayloadNotify["SEND_BY"] = 'system';
				$arrPayloadNotify["TYPE_NOTIFY"] = "2";
				if($lib->sendNotifyHW($arrPayloadNotify,"person")){
					$func->insertHistory($arrPayloadNotify,'2');
				}
			}
		}

	}
}
?>
