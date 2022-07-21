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
			$getNormCont = $conoracle->prepare("select lr.LOANCONTRACT_NO,llc.REF_COLLNO,TRIM(TO_CHAR(lr.loanrequest_amt, '999,999,999,999.99')) as LOANREQUEST_AMT,
												lt.LOANTYPE_DESC,lr.MEMBER_NO,TO_CHAR(lr.LOANREQUEST_DATE, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as request_date,
												TRIM(TO_CHAR(llc.collactsequest_amt, '999,999,999,999.99')) as COLLACTIVE_AMT,mp.prename_desc||mb.memb_name|| ' ' ||mb.memb_surname as FULL_NAME 
												from lnreqloan lr LEFT JOIN lnreqloancoll llc ON lr.loanrequest_docno = llc.loanrequest_docno LEFT JOIN lnloantype lt 
												ON lr.LOANTYPE_CODE = lt.LOANTYPE_CODE LEFT JOIN mbmembmaster mb ON lr.member_no = mb.member_no 
												LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code WHERE  llc.loancolltype_code = '01' 
												and lr.loanrequest_status = 1 
												and llc.ref_collno IS NOT NULL and TRUNC(TO_CHAR(lr.APPROVE_DATE,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and lr.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and lr.sync_notify_sms_flag = 0 and lt.monitfetter_grop = 'NORM' ORDER BY lr.LOANCONTRACT_NO ASC");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["LOANTYPE_DESC"] = $rowTarget["LOANTYPE_DESC"];
				$arrTarget["LOANREQUEST_DOCNO"] = $rowTarget["LOANCONTRACT_NO"];
				$arrTarget["FULL_NAME"] = $rowTarget["FULL_NAME"];
				$arrTarget["COLLACTIVE_AMT"] = $rowTarget["LOANREQUEST_AMT"];
				$arrToken = $func->getFCMToken('person',$rowTarget["REF_COLLNO"],$conoracle);
				$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
				$getName = $conoracle->prepare("SELECT MP.PRENAME_DESC || MB.MEMB_NAME ||' '|| MB.MEMB_SURNAME AS FULLNAME 
												FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												WHERE MB.MEMBER_NO = :member_no");
				$getName->execute([':member_no' => $rowTarget["MEMBER_NO"]]);
				$rowTetName = $getName->fetch(PDO::FETCH_ASSOC);
				if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
					if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $rowTarget["REF_COLLNO"];
						$arrGroupSuccess["FULLNAME"] = $rowTetName["FULLNAME"];
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["REF_COLLNO"];
						$arrGroupCheckSend["REF"] = $rowTarget["REF_COLLNO"];
						$arrGroupCheckSend["FULLNAME"] = $rowTetName["FULLNAME"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}else{
					if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
						if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
							$arrGroupSuccess["REF"] = $rowTarget["REF_COLLNO"];
							$arrGroupSuccess["FULLNAME"] = $rowTetName["FULLNAME"];
							$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $rowTarget["REF_COLLNO"];
							$arrGroupCheckSend["REF"] = $rowTarget["REF_COLLNO"];
							$arrGroupCheckSend["FULLNAME"] = $rowTetName["FULLNAME"];
							$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["REF_COLLNO"];
						$arrGroupCheckSend["REF"] = $rowTarget["REF_COLLNO"];
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
			$getNormCont = $conoracle->prepare("select lr.LOANCONTRACT_NO,llc.REF_COLLNO,TRIM(TO_CHAR(lr.loanrequest_amt, '999,999,999,999.99')) as LOANREQUEST_AMT,
												lt.LOANTYPE_DESC,lr.MEMBER_NO,TO_CHAR(lr.LOANREQUEST_DATE, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as request_date,
												TRIM(TO_CHAR(llc.collactsequest_amt, '999,999,999,999.99')) as COLLACTIVE_AMT,mp.prename_desc||mb.memb_name|| ' ' ||mb.memb_surname as FULL_NAME 
												from lnreqloan lr LEFT JOIN lnreqloancoll llc ON lr.loanrequest_docno = llc.loanrequest_docno LEFT JOIN lnloantype lt 
												ON lr.LOANTYPE_CODE = lt.LOANTYPE_CODE LEFT JOIN mbmembmaster mb ON lr.member_no = mb.member_no 
												LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code WHERE  llc.loancolltype_code = '01' 
												and lr.loanrequest_status = 1 
												and llc.ref_collno IS NOT NULL and TRUNC(TO_CHAR(lr.APPROVE_DATE,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and lr.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and lr.sync_notify_sms_flag = 0 and lt.monitfetter_grop = 'NORM' ORDER BY lr.LOANCONTRACT_NO ASC");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrTarget = array();
				$arrTarget["LOANTYPE_DESC"] = $rowTarget["LOANTYPE_DESC"];
				$arrTarget["LOANREQUEST_DOCNO"] = $rowTarget["LOANCONTRACT_NO"];
				$arrTarget["FULL_NAME"] = $rowTarget["FULL_NAME"];
				$arrTarget["COLLACTIVE_AMT"] = $rowTarget["LOANREQUEST_AMT"];
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				$arrayTel = $func->getSMSPerson('person',$rowTarget["REF_COLLNO"],$conoracle);
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
						$arrGroupCheckSend["REF"] = $dest["MEMBER_NO"];
						$arrGroupCheckSend["FULLNAME"] = $dest["FULLNAME"];
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