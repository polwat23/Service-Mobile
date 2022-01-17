<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqopendoc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqopenaccountlist')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqopenaccount SET req_status = '1' WHERE reqopendoc_no = :reqopendoc_no");
			if($approveReqLoan->execute([
				':reqopendoc_no' => $dataComing["reqopendoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => "reqopenaccountlist",
					':username' => $payload["username"],
					':use_list' => "change status",
					':details' => $dataComing["reqopendoc_no"]." to status : ".$dataComing["req_status"]
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}else if($dataComing["req_status"] == '7' || $dataComing["req_status"] == '6' || $dataComing["req_status"] == '2' || $dataComing["req_status"] == '3' || $dataComing["req_status"] == '4'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqopenaccount SET req_status = :req_status WHERE reqopendoc_no = :reqopendoc_no");
			if($approveReqLoan->execute([
				':req_status' => $dataComing["req_status"],
				':reqopendoc_no' => $dataComing["reqopendoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => "reqopenaccountlist",
					':username' => $payload["username"],
					':use_list' => "change status",
					':details' => $dataComing["reqopendoc_no"]." to status : ".$dataComing["req_status"]
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
		}else{
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqopenaccount SET req_status = '0' WHERE reqopendoc_no = :reqopendoc_no");
			if($approveReqLoan->execute([
				':reqopendoc_no' => $dataComing["reqopendoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => "reqopenaccountlist",
					':username' => $payload["username"],
					':use_list' => "change status",
					':details' => $dataComing["reqopendoc_no"]." to status : ".$dataComing["req_status"]
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}
		$getDataReqDoc = $conmysql->prepare("SELECT member_no FROM gcreqopenaccount WHERE reqopendoc_no = :reqopendoc_no");
		$getDataReqDoc->execute([':reqopendoc_no' => $dataComing["reqopendoc_no"]]);
		$rowDataReq = $getDataReqDoc->fetch(PDO::FETCH_ASSOC);
		$arrToken = $func->getFCMToken('person',$rowDataReq["member_no"]);
		$templateMessage = $func->getTemplateSystem('OpenAccountRequest',1);
		foreach($arrToken["LIST_SEND"] as $dest){
			$dataMerge = array();
			$dataMerge["REQ_STATUS_DESC"] = $configError["REQ_OPENACC_STATUS"][0][$dataComing["req_status"]][0]['th'];
			$dataMerge["REQDOC_NO"] = $dataComing["reqopendoc_no"];
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
			$arrPayloadNotify["ACTION_PAGE"] = null;
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