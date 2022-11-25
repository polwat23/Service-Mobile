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
	$fetchDataSTM = $conoracle->prepare("select 
					mt.deptaccount_no
					 from wcrecievemonth rcv
					join wcdeptmaster mt on mt.deptaccount_no = rcv.wfmember_no
					where rcv.recv_period = '256512'
					and rcv.wcitemtype_code = 'FEE'
					and rcv.status_post = 8
					and mt.deptclose_status = 0
					and mt.deptaccount_no in (" . implode(',', $dest) . ")
					");
	$fetchDataSTM->execute();
	while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
		$arrToken = $func->getFCMToken('person',$rowSTM["DEPTACCOUNT_NO"]);
		foreach($arrToken["LIST_SEND"] as $dest){
			if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
				$arrMessage = array();
				$arrPayloadNotify = array();
				$arrMessage["SUBJECT"] = "📢 ประกาศ สสธท.";
				$arrMessage["BODY"] = "📢 ประกาศ สสธท. 👉🏻แจ้งเตือนให้ชำระเงินสงเคราะห์ล่วงหน้าต่ออายุหรือคงสภาพประจำปี 2566 ‼️โดยให้ท่านชำระผ่านศูนย์ประสานงาน หรือชำระด้วยตนเอง (สำหรับสมาชิกสมัครตรง) ได้ตั้งแต่วันนี้เป็นต้นไป (รายละเอียดตามเอกสารแนบ)👇🏻🙏🏻";
				$arrMessage["PATH_IMAGE"] = $config["URL_SERVICE"] . "/resource/utility_icon/recv_period_256512.jpg";
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
				$arrMessage["SUBJECT"] = "📢 ประกาศ สสธท.";
				$arrMessage["BODY"] = "📢 ประกาศ สสธท. 👉🏻แจ้งเตือนให้ชำระเงินสงเคราะห์ล่วงหน้าต่ออายุหรือคงสภาพประจำปี 2566 ‼️โดยให้ท่านชำระผ่านศูนย์ประสานงาน หรือชำระด้วยตนเอง (สำหรับสมาชิกสมัครตรง) ได้ตั้งแต่วันนี้เป็นต้นไป (รายละเอียดตามเอกสารแนบ)👇🏻🙏🏻";
				$arrMessage["PATH_IMAGE"] = "http://cpct.coopsiam.com/service-cpct/resource/utility_icon/recv_period_256512.jpg";
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
