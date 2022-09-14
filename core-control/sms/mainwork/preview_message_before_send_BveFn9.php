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
			$getNormCont = $conoracle->prepare("SELECT sld.ITEM_PAYAMT,sl.MEMBER_NO,sld.SLIPITEM_DESC,TO_CHAR(sl.operate_date,'YYYY-MM-DD HH24:MI') as ENTRY_DATE,
											NVL(sld.LOANCONTRACT_NO,sl.MEMBER_NO) as LOANCONTRACT_NO,sld.ITEM_BALANCE,sld.SLIPITEMTYPE_CODE
												FROM slslippayin sl LEFT JOIN slslippayindet sld ON sl.payinslip_no = sld.payinslip_no
												where TRUNC(TO_CHAR(sl.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and sl.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and sl.ref_system = 'BIL' and sl.slip_status = '1'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$prefixText = '';
				if($rowTarget["SLIPITEMTYPE_CODE"] == 'I01'){
					$prefixText = 'ชำระ ';
					$arrTarget["LOANCONTRACT_NO"] = '';
				}else if($rowTarget["SLIPITEMTYPE_CODE"] == 'LON'){
					$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
				}else{
					$arrTarget["LOANCONTRACT_NO"] = '';
				}
				$arrTarget["SLIPITEM_DESC"] = $prefixText.$rowTarget["SLIPITEM_DESC"];
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["ITEM_BALANCE"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["ITEM_PAYAMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["ENTRY_DATE"],'d m Y');
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
			$getNormContDP = $conoracle->prepare("SELECT dp.deptaccount_no,
												 dpm.member_no,dp.deptslip_amt,dpc.recppaytype_desc,dp.prncbal,TO_CHAR(dp.deptslip_date,'YYYY-MM-DD') as deptslip_date
												FROM dpdeptslip dp LEFT JOIN dpdeptmaster dpm ON dp.deptaccount_no = dpm.deptaccount_no
												LEFT JOIN dpucfrecppaytype dpc ON dp.recppaytype_code = dpc.recppaytype_code
												WHERE dp.recppaytype_code = 'DTB' 
												and TRUNC(TO_CHAR(dp.deptslip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and dpm.MEMBER_NO in('".implode("','",$member_destination)."')") : ""));
			$getNormContDP->execute();
			while($rowTarget = $getNormContDP->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["LOANCONTRACT_NO"] = $rowTarget["DEPTACCOUNT_NO"];
				$arrTarget["SLIPITEM_DESC"] = 'ฝากเงินเข้าบัญชี';
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["PRNCBAL"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["DEPTSLIP_AMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["DEPTSLIP_DATE"],'d m Y');
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
			
			
			$getNormCont = $conoracle->prepare("SELECT sld.ITEM_PAYAMT,sl.MEMBER_NO,sld.SLIPITEM_DESC,TO_CHAR(sl.operate_date,'YYYY-MM-DD') as ENTRY_DATE,
													NVL(sld.LOANCONTRACT_NO,sl.MEMBER_NO) as LOANCONTRACT_NO,sld.ITEM_BALANCE,sld.SLIPITEMTYPE_CODE
												FROM slslippayin sl LEFT JOIN slslippayindet sld ON sl.payinslip_no = sld.payinslip_no
												where TRUNC(TO_CHAR(sl.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and sl.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and sl.ref_system = 'BIL' and sl.slip_status = '1'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$prefixText = '';
				if($rowTarget["SLIPITEMTYPE_CODE"] == 'I01'){
					$prefixText = 'ชำระ ';
					$arrTarget["LOANCONTRACT_NO"] = '';
				}else if($rowTarget["SLIPITEMTYPE_CODE"] == 'LON'){
					$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
				}else{
					$arrTarget["LOANCONTRACT_NO"] = '';
				}
				$arrTarget["SLIPITEM_DESC"] = $prefixText.$rowTarget["SLIPITEM_DESC"];
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["ITEM_BALANCE"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["ITEM_PAYAMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["ENTRY_DATE"],'d m Y');
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
			$getNormContDP = $conoracle->prepare("SELECT dp.deptaccount_no,
												 dpm.member_no,dp.deptslip_amt,dpc.recppaytype_desc,dp.prncbal,TO_CHAR(dp.deptslip_date,'YYYY-MM-DD') as deptslip_date
												FROM dpdeptslip dp LEFT JOIN dpdeptmaster dpm ON dp.deptaccount_no = dpm.deptaccount_no
												LEFT JOIN dpucfrecppaytype dpc ON dp.recppaytype_code = dpc.recppaytype_code
												WHERE dp.recppaytype_code = 'DTB' 
												and TRUNC(TO_CHAR(dp.deptslip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and dpm.MEMBER_NO in('".implode("','",$member_destination)."')") : ""));
			$getNormContDP->execute();
			while($rowTarget = $getNormContDP->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["LOANCONTRACT_NO"] = $rowTarget["DEPTACCOUNT_NO"];
				$arrTarget["SLIPITEM_DESC"] = 'ฝากเงินเข้าบัญชี';
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["PRNCBAL"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["DEPTSLIP_AMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["DEPTSLIP_DATE"],'d m Y');
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