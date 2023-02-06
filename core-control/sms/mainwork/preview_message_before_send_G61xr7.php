<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
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
			$getNormCont = $conmssql->prepare("select rcv.wc_id, wc.coop_shortname, rcv.wfmember_no, LTRIM(RTRIM(rcv.member_no)) as member_no , mty.wcmembertype_desc , pre.prename_desc, mt.deptaccount_name, mt.deptaccount_sname, 
									cast(substring(rcv.recv_period,1,4) as int)+1 as keep_year , rcv.fee_year+rcv.ins_amt as item_amt 
									from wcrecievemonth rcv
									join wccontcoop wc on (rcv.wc_id = wc.wc_id)
									join wcdeptmaster mt on (mt.wc_id = rcv.wc_id and mt.deptaccount_no = rcv.wfmember_no)
									left join wcmembertype mty on (mty.wc_id = mt.wc_id and mty.wftype_code = mt.wftype_code)
									left join mbucfprename pre on (pre.prename_code = mt.prename_code)
									where  rcv.status_post = 8  ".(($dataComing["type_send"] == "person") ? (" and rcv.MEMBER_NO in('".implode("','",$member_destination)."')") : "")."  order by rcv.wc_id");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){	
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["WCMEMBERTYPE_DESC"] = $rowTarget["wcmembertype_desc"];
				$arrTarget["ITEM_AMT"] = $rowTarget["item_amt"];
				$arrTarget["KEEP_YEAR"] = $rowTarget["keep_year"];
				$arrTarget["COOP_SHORTNAME"] = $rowTarget["coop_shortname"];
				$arrToken = $func->getFCMToken('person',$rowTarget["member_no"],$conmssql);
				$arrMessage = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
				$getName = $conmssql->prepare("SELECT MP.PRENAME_DESC + MB.MEMB_NAME + ' ' +  MB.MEMB_SURNAME AS fullname 
												FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												WHERE MB.MEMBER_NO = :member_no");
				$getName->execute([':member_no' => $rowTarget["member_no"]]);
				$rowTetName = $getName->fetch(PDO::FETCH_ASSOC);
				if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
					if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $rowTarget["member_no"];
						$arrGroupSuccess["FULLNAME"] = $rowTetName["fullname"];
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["member_no"];
						$arrGroupCheckSend["REF"] = $rowTarget["member_no"];
						$arrGroupCheckSend["FULLNAME"] = $rowTetName["fullname"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}					
				}else{
					if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
						if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
							$arrGroupSuccess["DESTINATION"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
							$arrGroupSuccess["REF"] = $rowTarget["MEMBER_NO"];
							$arrGroupSuccess["FULLNAME"] = $rowTetName["fullname"];
							$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
							$arrGroupAllSuccess[] = $arrGroupSuccess;
						}else{
							$arrGroupCheckSend["DESTINATION"] = $member_no["member_no"];
							$arrGroupCheckSend["REF"] = $rowTarget["member_no"];
							$arrGroupCheckSend["FULLNAME"] = $rowTetName["fullname"];
							$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^บัญชีนี้ไม่ประสงค์รับการแจ้งเตือนข่าวสาร';
							$arrGroupAllFailed[] = $arrGroupCheckSend;
						}
					}else{
						$arrGroupCheckSend["DESTINATION"] = $rowTarget["member_no"];
						$arrGroupCheckSend["REF"] = $rowTarget["member_no"];
						$arrGroupCheckSend["FULLNAME"] = $rowTetName["fullname"];
						$arrGroupCheckSend["MESSAGE"] = $arrMessage["BODY"].'^ไม่สามารถระบุเครื่องในการรับแจ้งเตือนได้';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
				//$arrayResult['AADAS'] = json_encode($arrTarget);
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');	
		}else{
			$arrGroupAllSuccess = array();
			$arrGroupAllFailed = array();
			
			
			$getNormCont = $conmssql->prepare("select rcv.wc_id, wc.coop_shortname, rcv.wfmember_no, rcv.member_no , mty.wcmembertype_desc , pre.prename_desc, mt.deptaccount_name, mt.deptaccount_sname, cast(substring(rcv.recv_period,1,4) as int)+1 as keep_year , rcv.fee_year+rcv.ins_amt as item_amt 
									from wcrecievemonth rcv
									join wccontcoop wc on (rcv.wc_id = wc.wc_id)
									join wcdeptmaster mt on (mt.wc_id = rcv.wc_id and mt.deptaccount_no = rcv.wfmember_no)
									left join wcmembertype mty on (mty.wc_id = mt.wc_id and mty.wftype_code = mt.wftype_code)
									left join mbucfprename pre on (pre.prename_code = mt.prename_code)
									where  ".(($dataComing["type_send"] == "person") ? (" and rcv.MEMBER_NO in('".implode("','",$member_destination)."')") : "")." and rcv.status_post = 8 
									order by rcv.wc_id");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrTarget = array();
				$arrTarget["WCMEMBERTYPE_DESC"] = $rowTarget["wcmembertype_desc"];
				$arrTarget["ITEM_AMT"] = $rowTarget["item_amt"];
				$arrTarget["KEEP_YEAR "] = $rowTarget["keep_year"];
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conmssql);
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