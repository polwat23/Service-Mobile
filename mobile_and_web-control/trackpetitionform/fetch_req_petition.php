<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PetitionFormTrack')){
		$arrGrpReq = array();
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$fetchReqPetition = $conmysql->prepare("SELECT reqdoc_no, member_no, petitionform_code, petitionform_desc, document_url, req_status, remark, req_desc, effect_date, request_date, update_date
											FROM gcpetitionformreq
											WHERE member_no = :member_no and req_status = :req_status ORDER BY update_date DESC");
			$fetchReqPetition->execute([
				':member_no' => $payload["member_no"],
				':req_status' => $dataComing["req_status"]
			]);
			while($rowReq = $fetchReqPetition->fetch(PDO::FETCH_ASSOC)){
				$arrayReq = array();
				$arrayReq["REQDOC_NO"] = $rowReq["reqdoc_no"];
				$arrayReq["MEMBER_NO"] = $rowReq["member_no"];
				$arrayReq["PETITIONFORM_CODE"] = $rowReq["petitionform_code"];
				$arrayReq["PETITIONFORM_DESC"] = $rowReq["petitionform_desc"];
				$arrayReq["DOCUMENT_URL"] = $rowReq["document_url"];
				$arrayReq["REQ_STATUS"] = $rowReq["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_PETITION_STATUS"][0][$rowReq["req_status"]][0][$lang_locale];
				$arrayReq["REMARK"] = $rowReq["remark"];
				$arrayReq["REQ_DESC"] = $rowReq["req_desc"];
				$arrayReq["EFFECT_DATE"] = $rowReq["effect_date"];
				$arrayReq["REQUEST_DATE"] = $rowReq["request_date"];
				$arrayReq["UPDATE_DATE"] = $rowReq["update_date"];
				$arrGrpReq[] = $arrayReq;
			}
		}else{
			$fetchReqPetition = $conmysql->prepare("SELECT reqdoc_no, member_no, petitionform_code, petitionform_desc, document_url, req_status, remark, req_desc, effect_date, request_date, update_date
											FROM gcpetitionformreq
											WHERE member_no = :member_no ORDER BY update_date DESC");
			$fetchReqPetition->execute([
				':member_no' => $payload["member_no"]
			]);
			while($rowReq = $fetchReqPetition->fetch(PDO::FETCH_ASSOC)){
				$arrayReq = array();
				$arrayReq["REQDOC_NO"] = $rowReq["reqdoc_no"];
				$arrayReq["MEMBER_NO"] = $rowReq["member_no"];
				$arrayReq["PETITIONFORM_CODE"] = $rowReq["petitionform_code"];
				$arrayReq["PETITIONFORM_DESC"] = $rowReq["petitionform_desc"];
				$arrayReq["DOCUMENT_URL"] = $rowReq["document_url"];
				$arrayReq["REQ_STATUS"] = $rowReq["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_PETITION_STATUS"][0][$rowReq["req_status"]][0][$lang_locale];
				$arrayReq["REMARK"] = $rowReq["remark"];
				$arrayReq["REQ_DESC"] = $rowReq["req_desc"];
				$arrayReq["EFFECT_DATE"] = $rowReq["effect_date"];
				$arrayReq["REQUEST_DATE"] = $rowReq["request_date"];
				$arrayReq["UPDATE_DATE"] = $rowReq["update_date"];
				$arrGrpReq[] = $arrayReq;
			}
		}
		$filter = array();
		$filter[] = ["STATUS" => "8", "DESC" => "รออนุมัติ"];
		$filter[] = ["STATUS" => "7", "DESC" => "ลงรับรอตรวจสิทธิ์เพิ่มเติม"];
		$filter[] = ["STATUS" => "9", "DESC" => "ยกเลิกใบคำขอ"];
		$filter[] = ["STATUS" => "-9", "DESC" => "ไม่อนุมัติ"];
		$filter[] = ["STATUS" => "1", "DESC" => "รออนุมัติ"];
		$arrayResult['REQ_LIST'] = $arrGrpReq;
		$arrayResult['FILTER'] = $filter;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>