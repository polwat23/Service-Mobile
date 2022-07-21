<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall',$conoracle) || 
	$func->check_permission_core($payload,'sms','sendmessageperson',$conoracle)){
		if($dataComing["channel_send"] == "mobile_app"){
			if(isset($dataComing["send_image"]) && $dataComing["send_image"] != null){
				$destination = __DIR__.'/../../../resource/image_wait_to_be_sent';
				$file_name = $lib->randomText('all',6);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_img($dataComing["send_image"],$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../include/exit_footer.php');
				}else{
					if($createImage){
						$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../include/exit_footer.php');
					}
				}
			}
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			$grpMember = array();
			foreach($dataComing["destination"] as $member_no) {
				$grpMember[] = "'".$lib->mb_str_pad($member_no)."'";
			}
			$getCoopPhone = $conoracle->prepare("SELECT mb.IVR_PASSWORD,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME 
												 FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
												 WHERE mb.member_no IN(".implode(',',$grpMember).")");
			$getCoopPhone->execute();
			while($rowTarget = $getCoopPhone->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget["IVR_PASSWORD"] = " **** ";//$rowTarget["IVR_PASSWORD"];
				$arrToken = $func->getFCMToken('person',$rowTarget["MEMBER_NO"],$conoracle);
				$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
				if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
					if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $rowTarget["FULLNAME"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}else{
					if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
						if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
							$arrGroupSuccess["REF"] = $rowTarget["MEMBER_NO"];
							$arrGroupSuccess["FULLNAME"] = $rowTarget["FULLNAME"];
							$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
							$arrGroupCheckSend["FULLNAME"] = $rowTarget["FULLNAME"];
							$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $rowTarget["FULLNAME"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
			}
			foreach($dataComing["destination"] as $destination) {
				$destination = $lib->mb_str_pad($destination);
				if(array_search($destination, array_column($arrGroupAllSuccess, 'DESTINATION')) === false && array_search($destination, array_column($arrGroupAllFailed, 'DESTINATION')) === false
				&& array_search($destination, array_column($arrGroupAllSuccess, 'DESTINATION')) === false){
					$arrGroupCheckSend["DESTINATION"] = $destination;
					$arrGroupCheckSend["REF"] = $destination;
					$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			$grpMember = array();
			foreach($dataComing["destination"] as $member_no) {
				$grpMember[] = "'".$lib->mb_str_pad($member_no)."'";
			}
			$getCoopPhone = $conoracle->prepare("SELECT IVR_PASSWORD,MEMBER_NO FROM MBMEMBMASTER WHERE member_no IN(".implode(',',$grpMember).")");
			$getCoopPhone->execute();
			while($rowTarget = $getCoopPhone->fetch(PDO::FETCH_ASSOC)){
				$arrTarget["IVR_PASSWORD"] = " **** ";//$rowTarget["IVR_PASSWORD"];
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conoracle);
				foreach($arrayTel as $dest){
					if(isset($dest["TEL"]) && $dest["TEL"] != ""){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["FULLNAME"] = $dest["FULLNAME"];
						$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["TEL"] = $lib->formatphone($dest["TEL"],'-');
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $dest["FULLNAME"];
						$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["TEL"] = "ไม่พบเบอร์โทรศัพท์";
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"];
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
			}
			foreach($dataComing["destination"] as $destination) {
				$destination = $lib->mb_str_pad($destination);
				if(array_search($destination, array_column($arrGroupAllSuccess, 'REF')) === false && array_search($destination, array_column($arrGroupAllFailed, 'DESTINATION')) === false
				&& array_search($destination, array_column($arrGroupAllSuccess, 'REF')) === false){
					$arrGroupCheckSend["DESTINATION"] = $destination;
					$arrGroupCheckSend["REF"] = $destination;
					$arrGroupCheckSend["TEL"] = "-";
					$arrGroupCheckSend["MESSAGE"] = "ไม่สามารถระบุเลขปลายทางได้";
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['DEBUG'] = $getCoopPhone;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>