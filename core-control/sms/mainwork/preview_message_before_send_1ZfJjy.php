<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall',$conoracle) || 
	$func->check_permission_core($payload,'sms','sendmessageperson',$conoracle)){
		$member_destination = array();
		if($dataComing["type_send"] == "person"){
			if(isset($dataComing["destination"]) && $dataComing["destination"] != null){
				foreach($dataComing["destination"] as $desMemberNo){
					$member_destination[] = strtolower($lib->mb_str_pad($desMemberNo));
				}
			}
		}
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
			$getNormCont = $conoracle->prepare("select slo.ASSISTSLIP_NO,slo.member_no,TRIM(TO_CHAR(slo.payoutnet_amt, '999,999,999,999.99')) as payoutnet_amt,
												slu.assisttype_desc,TO_CHAR(slo.slip_date, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as slip_date 
												from assslippayout slo LEFT JOIN assucfassisttype slu 
												ON slo.assisttype_code = slu.assisttype_code 
												WHERE TRUNC(TO_CHAR(slo.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and slo.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and slo.sync_notify_flag = 0 and slo.assisttype_code NOT IN('51','80')");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["ASSISTTYPE_DESC"] = $rowTarget["ASSISTTYPE_DESC"];
				$arrTarget["PAYOUTNET_AMT"] = $rowTarget["PAYOUTNET_AMT"];
				$arrTarget["SLIP_DATE"] = $rowTarget["SLIP_DATE"];
				$arrToken = $func->getFCMToken('person',$rowTarget["MEMBER_NO"],$conoracle);
				$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
				$getName = $conoracle->prepare("SELECT MP.PRENAME_DESC || MB.MEMB_NAME ||' '|| MB.MEMB_SURNAME AS FULLNAME 
												FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												WHERE MB.MEMBER_NO = :member_no");
				$getName->execute([':member_no' => $rowTarget["MEMBER_NO"]]);
				$rowTetName = $getName->fetch(PDO::FETCH_ASSOC);
				if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
					if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupSuccess["FULLNAME"] = $rowTetName["FULLNAME"];
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $rowTetName["FULLNAME"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}else{
					if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
						if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
							$arrGroupSuccess["REF"] = $rowTarget["MEMBER_NO"];
							$arrGroupSuccess["FULLNAME"] = $rowTetName["FULLNAME"];
							$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
							$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
							$arrGroupCheckSend["FULLNAME"] = $rowTetName["FULLNAME"];
							$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $rowTarget["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $rowTetName["FULLNAME"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');	
		}else{
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			$getNormCont = $conoracle->prepare("select slo.ASSISTSLIP_NO,slo.member_no,TRIM(TO_CHAR(slo.payoutnet_amt, '999,999,999,999.99')) as payoutnet_amt,
												slu.assisttype_desc,TO_CHAR(slo.slip_date, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as slip_date 
												from assslippayout slo LEFT JOIN assucfassisttype slu 
												ON slo.assisttype_code = slu.assisttype_code 
												WHERE TRUNC(TO_CHAR(slo.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and slo.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and slo.sync_notify_flag = 0 and slo.assisttype_code NOT IN('51','80')");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["ASSISTTYPE_DESC"] = $rowTarget["ASSISTTYPE_DESC"];
				$arrTarget["PAYOUTNET_AMT"] = $rowTarget["PAYOUTNET_AMT"];
				$arrTarget["SLIP_DATE"] = $rowTarget["SLIP_DATE"];
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conoracle);
				foreach($arrayTel as $dest){
					if(isset($dest["TEL"]) && $dest["TEL"] != ""){
						$arrGroupSuccess["DESTINATION"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $dest["MEMBER_NO"];
						$arrGroupSuccess["FULLNAME"] = $dest["FULLNAME"];
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
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['DE'] = $getNormCont;
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