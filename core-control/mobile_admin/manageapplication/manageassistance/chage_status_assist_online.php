<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','assist_docno'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		if($dataComing["req_status"] == '1'){
			$approveAssist = $conmysql->prepare("UPDATE assreqmasteronline SET req_status = '1',remark = :remark WHERE assist_docno = :assist_docno");
			if($approveAssist->execute([
				':remark' => $dataComing["remark"] ?? null,
				':assist_docno' => $dataComing["assist_docno"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveAssist = $conmysql->prepare("UPDATE assreqmasteronline SET req_status = '-9',remark = :remark WHERE assist_docno = :assist_docno");
			if($approveAssist->execute([
				':remark' => $dataComing["remark"] ?? null,
				':assist_docno' => $dataComing["assist_docno"]
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
		$getDataReqDoc = $conmysql->prepare("SELECT member_no FROM assreqmasteronline WHERE assist_docno = :assist_docno");
		$getDataReqDoc->execute([':assist_docno' => $dataComing["assist_docno"]]);
		$rowDataReq = $getDataReqDoc->fetch(PDO::FETCH_ASSOC);
		$arrToken = $func->getFCMToken('person',$rowDataReq["member_no"]);
		$templateMessage = $func->getTemplateSystem('LoanAssistForm',1);
		foreach($arrToken["LIST_SEND"] as $dest){
			$dataMerge = array();
			$dataMerge["REQ_STATUS_DESC"] = $configError["REQ_ASSIST_STATUS"][0][$dataComing["req_status"]][0]['th'];
			$dataMerge["ASSIST_DOCNO"] = $dataComing["assist_docno"];
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
			$arrPayloadNotify["ACTION_PAGE"] = "AssistTrack";
			$arrPayloadNotify["ACTION_PARAMS"] = null;
			$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
			$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
			$arrMessage["BODY"] = $message_endpoint["BODY"];
			$arrMessage["PATH_IMAGE"] = null;
			$arrPayloadNotify["PAYLOAD"] = $arrMessage;
			$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
			$arrPayloadNotify["SEND_BY"] = $payload["username"];
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