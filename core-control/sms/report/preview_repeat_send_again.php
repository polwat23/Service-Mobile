<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_component'],$dataComing)){
	if($dataComing["menu_component"] == 'reportsmssuccess'){
		$getMessageLog = $conmysql->prepare("SELECT sms_message,member_no,tel_mobile
											FROM smslogwassent WHERE id_logsent = :id_logsent");
		$getMessageLog->execute([':id_logsent' => $dataComing["id_logsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"]);
		}else{
			$destination_temp = array();
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["tel_mobile"];
			$arrDestGRP[] = $destination_temp;
		}
		if(isset($arrDestGRP)){
			$arrayMerge = array_merge($arrayTel,$arrDestGRP);
		}else{
			$arrayMerge = $arrayTel;
		}
		$arrGroupAllSuccess = array();
		$arrGroupAllFailed = array();
		foreach($arrayMerge as $dest){
			if(isset($dest["TEL"]) && $dest["TEL"] != ""){
				$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupSuccess["REF"] = $dest["MEMBER_NO"] ?? $dest["TEL"];
				$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
				$arrGroupSuccess["MESSAGE"] = ($rowMessage["sms_message"] ?? "-");
				$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["sms_message"];
				$arrGroupAllSuccess[] = $arrGroupSuccess;
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
				$arrGroupCheckSend["MESSAGE"] = ($rowMessage["sms_message"] ?? "-");
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["sms_message"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		foreach($arrDestGRPNotCorrect as $target){
			$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
			$arrGroupCheckSend["REF"] = $target["TEL"];
			$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
			$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["sms_message"];
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else if($dataComing["menu_component"] == 'reportsmsnotsuccess'){
		$getMessageLog = $conmysql->prepare("SELECT topic,message,member_no,send_platform,tel_mobile
											FROM smswasnotsent WHERE id_smsnotsent = :id_smsnotsent");
		$getMessageLog->execute([':id_smsnotsent' => $dataComing["id_smsnotsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		if($rowMessage["send_platform"] == 'sms'){
			$arrDestGRP = array();
			$arrayTel = array();
			if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
				$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"]);
			}else{
				$destination_temp = array();
				$destination_temp["MEMBER_NO"] = null;
				$destination_temp["TEL"] = $rowMessage["tel_mobile"];
				$arrDestGRP[] = $destination_temp;
			}
			if(isset($arrDestGRP)){
				$arrayMerge = array_merge($arrayTel,$arrDestGRP);
			}else{
				$arrayMerge = $arrayTel;
			}
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			foreach($arrayMerge as $dest){
				if(isset($dest["TEL"]) && $dest["TEL"] != ""){
					$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupSuccess["REF"] = $dest["MEMBER_NO"] ?? $dest["TEL"];
					$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
					$arrGroupSuccess["MESSAGE"] = ($rowMessage["message"] ?? "-");
					$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["message"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
					$arrGroupCheckSend["MESSAGE"] = ($rowMessage["message"] ?? "-");
					$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["message"];
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			foreach($arrDestGRPNotCorrect as $target){
				$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
				$arrGroupCheckSend["REF"] = $target["TEL"];
				$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["message"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$destination = array();
			$arrToken = $func->getFCMToken('person',$rowMessage["member_no"]);
			foreach($arrToken["LIST_SEND"] as $dest){
				if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
					if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["MESSAGE"] = $rowMessage["message"].'^'.$rowMessage["topic"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["MESSAGE"] = $rowMessage["message"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["MESSAGE"] = $rowMessage["message"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
			foreach($arrDiff as $memb_diff){
				$arrGroupCheckSend["DESTINATION"] = $memb_diff;
				$arrGroupCheckSend["REF"] = $memb_diff;
				$arrGroupCheckSend["MESSAGE"] = $rowMessage["message"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}
	}else if($dataComing["menu_component"] == 'reportnotifysuccess'){
		$getMessageLog = $conmysql->prepare("SELECT his_title,his_detail,member_no
											FROM gchistory WHERE id_history = :id_history");
		$getMessageLog->execute([':id_history' => $dataComing["id_history"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$destination = array();
		$arrToken = $func->getFCMToken('person',$rowMessage["member_no"]);
		foreach($arrToken["LIST_SEND"] as $dest){
			if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
				if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
					$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
					$arrGroupSuccess["MESSAGE"] = $rowMessage["his_detail"].'^'.$rowMessage["his_title"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["MESSAGE"] = $rowMessage["his_detail"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["MESSAGE"] = $rowMessage["his_detail"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
		foreach($arrDiff as $memb_diff){
			$arrGroupCheckSend["DESTINATION"] = $memb_diff;
			$arrGroupCheckSend["REF"] = $memb_diff;
			$arrGroupCheckSend["MESSAGE"] = $rowMessage["his_detail"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else if($dataComing["menu_component"] == 'reportsmstranwassent'){
		$getMessageLog = $conmysql->prepare("SELECT sms_message,member_no,tel_mobile
											FROM smstranwassent WHERE id_smssent = :id_smssent");
		$getMessageLog->execute([':id_smssent' => $dataComing["id_smssent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"]);
		}else{
			$destination_temp = array();
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["tel_mobile"];
			$arrDestGRP[] = $destination_temp;
		}
		if(isset($arrDestGRP)){
			$arrayMerge = array_merge($arrayTel,$arrDestGRP);
		}else{
			$arrayMerge = $arrayTel;
		}
		$arrGroupAllSuccess = array();
		$arrGroupAllFailed = array();
		foreach($arrayMerge as $dest){
			if(isset($dest["TEL"]) && $dest["TEL"] != ""){
				$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupSuccess["REF"] = $dest["MEMBER_NO"] ?? $dest["TEL"];
				$arrGroupSuccess["TEL"] = $lib->formatphone(substr($dest["TEL"],0,10),'-');
				$arrGroupSuccess["MESSAGE"] = ($rowMessage["sms_message"] ?? "-");
				$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["sms_message"];
				$arrGroupAllSuccess[] = $arrGroupSuccess;
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
				$arrGroupCheckSend["MESSAGE"] = ($rowMessage["sms_message"] ?? "-");
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["sms_message"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		foreach($arrDestGRPNotCorrect as $target){
			$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
			$arrGroupCheckSend["REF"] = $target["TEL"];
			$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
			$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["sms_message"];
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
