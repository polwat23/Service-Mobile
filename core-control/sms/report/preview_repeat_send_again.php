<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_component'],$dataComing)){
	if($dataComing["menu_component"] == 'reportsmssuccess'){
		$getMessageLog = $conoracle->prepare("SELECT sms_message,member_no,tel_mobile
											FROM smslogwassent WHERE id_logsent = :id_logsent");
		$getMessageLog->execute([':id_logsent' => $dataComing["id_logsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["MEMBER_NO"]) && $rowMessage["MEMBER_NO"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["MEMBER_NO"],$conoracle);
		}else{
			$destination_temp = array();
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["TEL_MOBILE"];
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
				$arrGroupSuccess["MESSAGE"] = ($rowMessage["SMS_MESSAGE"] ?? "-");
				$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
				$arrGroupAllSuccess[] = $arrGroupSuccess;
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
				$arrGroupCheckSend["MESSAGE"] = ($rowMessage["SMS_MESSAGE"] ?? "-");
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		foreach($arrDestGRPNotCorrect as $target){
			$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
			$arrGroupCheckSend["REF"] = $target["TEL"];
			$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
			$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else if($dataComing["menu_component"] == 'reportsmsnotsuccess'){
		$getMessageLog = $conoracle->prepare("SELECT topic,message,member_no,send_platform,tel_mobile
											FROM smswasnotsent WHERE id_smsnotsent = :id_smsnotsent");
		$getMessageLog->execute([':id_smsnotsent' => $dataComing["id_smsnotsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		if($rowMessage["SEND_PLATFORM"] == 'sms'){
			$arrDestGRP = array();
			$arrayTel = array();
			if(isset($rowMessage["MEMBER_NO"]) && $rowMessage["MEMBER_NO"] != ""){
				$arrayTel = $func->getSMSPerson('person',$rowMessage["MEMBER_NO"],$conoracle);
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
					$arrGroupSuccess["MESSAGE"] = ($rowMessage["MESSAGE"] ?? "-");
					$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["MESSAGE"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
					$arrGroupCheckSend["MESSAGE"] = ($rowMessage["MESSAGE"] ?? "-");
					$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["MESSAGE"];
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			foreach($arrDestGRPNotCorrect as $target){
				$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
				$arrGroupCheckSend["REF"] = $target["TEL"];
				$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["MESSAGE"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			$destination = array();
			$arrToken = $func->getFCMToken('person',$rowMessage["MEMBER_NO"],$conoracle);
			foreach($arrToken["LIST_SEND"] as $dest){
				if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
					if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["MESSAGE"] = $rowMessage["MESSAGE"].'^'.$rowMessage["TOPIC"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["MESSAGE"] = $rowMessage["MESSAGE"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["MESSAGE"] = $rowMessage["MESSAGE"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
			foreach($arrDiff as $memb_diff){
				$arrGroupCheckSend["DESTINATION"] = $memb_diff;
				$arrGroupCheckSend["REF"] = $memb_diff;
				$arrGroupCheckSend["MESSAGE"] = $rowMessage["MESSAGE"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}
	}else if($dataComing["menu_component"] == 'reportnotifysuccess'){
		$getMessageLog = $conoracle->prepare("SELECT his_title,his_detail,member_no
											FROM gchistory WHERE id_history = :id_history");
		$getMessageLog->execute([':id_history' => $dataComing["id_history"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$destination = array();
		$arrToken = $func->getFCMToken('person',$rowMessage["MEMBER_NO"],$conoracle);
		foreach($arrToken["LIST_SEND"] as $dest){
			if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
				if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
					$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
					$arrGroupSuccess["MESSAGE"] = $rowMessage["HIS_DETAIL"].'^'.$rowMessage["HIS_TITLE"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}else{
					$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
					$arrGroupCheckSend["MESSAGE"] = $rowMessage["HIS_DETAIL"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["MESSAGE"] = $rowMessage["HIS_DETAIL"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		$arrDiff = array_diff($destination,$arrToken["MEMBER_NO"]);
		foreach($arrDiff as $memb_diff){
			$arrGroupCheckSend["DESTINATION"] = $memb_diff;
			$arrGroupCheckSend["REF"] = $memb_diff;
			$arrGroupCheckSend["MESSAGE"] = $rowMessage["HIS_DETAIL"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else if($dataComing["menu_component"] == 'reportsmstranwassent'){
		$getMessageLog = $conoracle->prepare("SELECT sms_message,member_no,tel_mobile
											FROM smstranwassent WHERE id_smssent = :id_smssent");
		$getMessageLog->execute([':id_smssent' => $dataComing["id_smssent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["MEMBER_NO"]) && $rowMessage["MEMBER_NO"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["MEMBER_NO"],$conoracle);
		}else{
			$destination_temp = array();
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["TEL_MOBILE"];
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
				$arrGroupSuccess["MESSAGE"] = ($rowMessage["SMS_MESSAGE"] ?? "-");
				$arrGroupSuccess["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
				$arrGroupAllSuccess[] = $arrGroupSuccess;
			}else{
				$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
				$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
				$arrGroupCheckSend["MESSAGE"] = ($rowMessage["SMS_MESSAGE"] ?? "-");
				$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
				$arrGroupAllFailed[] = $arrGroupCheckSend;
			}
		}
		foreach($arrDestGRPNotCorrect as $target){
			$arrGroupCheckSend["DESTINATION"] = $target["TEL"];
			$arrGroupCheckSend["REF"] = $target["TEL"];
			$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
			$arrGroupCheckSend["REF_MESSAGE"] = $rowMessage["SMS_MESSAGE"];
			$arrGroupAllFailed[] = $arrGroupCheckSend;
		}
		$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
		$arrayResult['FAILED'] = $arrGroupAllFailed;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>
