<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqdoc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','petitionrequestlist')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcpetitionformreq SET req_status = '1',remark = :remark,approve_date = NOW() WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '7'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcpetitionformreq SET req_status = '7',remark = :remark WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => "petitionrequestlist",
					':use_list' => "change status",
					':details' => 'เปลี่ยนสถานะใบคำร้อง'.$dataComing["reqdoc_no"].' => '.$dataComing["req_status"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcpetitionformreq SET req_status = '-9',remark = :remark WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}
		$getDataReqDoc = $conmysql->prepare("SELECT member_no FROM gcpetitionformreq WHERE reqdoc_no = :reqdoc_no");
		$getDataReqDoc->execute([':reqdoc_no' => $dataComing["reqdoc_no"]]);
		$rowDataReq = $getDataReqDoc->fetch(PDO::FETCH_ASSOC);
		$arrToken = $func->getFCMToken('person',$rowDataReq["member_no"]);
		$templateMessage = $func->getTemplateSystem('PetitionForm',1);
		foreach($arrToken["LIST_SEND"] as $dest){
			$dataMerge = array();
			$dataMerge["REQ_STATUS_DESC"] = $configError["REQ_PETITION_STATUS"][0][$dataComing["req_status"]][0]['th'];
			$dataMerge["REQDOC_NO"] = $dataComing["reqdoc_no"];
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
			$arrPayloadNotify["ACTION_PAGE"] = "PetitionFormTrack";
			$arrPayloadNotify["ACTION_PARAMS"] = [];
			$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
			$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
			$arrMessage["BODY"] = $message_endpoint["BODY"];
			$arrMessage["PATH_IMAGE"] = null;
			$arrPayloadNotify["PAYLOAD"] = $arrMessage;
			$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
			if($func->insertHistory($arrPayloadNotify,'2')){
				$lib->sendNotify($arrPayloadNotify,"person");
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>