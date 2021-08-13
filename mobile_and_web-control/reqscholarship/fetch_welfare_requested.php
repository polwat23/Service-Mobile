<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrChildGrp = array();
		$checkChildHave = $conoracle->prepare("SELECT asch.childcard_id as CHILDCARD_ID, mp.prename_desc||asch.child_name||'   '||asch.child_surname as CHILD_NAME
															FROM ASNREQSCHOLARSHIP asch LEFT JOIN mbucfprename mp ON  asch.childprename_code = mp.prename_code
															WHERE asch.approve_status = 1 and asch.scholarship_year = (EXTRACT(year from sysdate) +542) and asch.member_no = :member_no");
		$checkChildHave->execute([':member_no' => $member_no]);
		while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
			$arrChild = array();
			$arrChild["CHILDCARD_ID"] = $rowChild["CHILDCARD_ID"];
			$arrChild["CHILDCARD_ID_FORMAT"] = $lib->formatcitizen($rowChild["CHILDCARD_ID"]);
			$arrChild["CHILD_NAME"] = $rowChild["CHILD_NAME"];
			$getStatusDoc = $conoracle->prepare("SELECT REQUEST_STATUS, CANCEL_REMARK FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id");
			$getStatusDoc->execute([':child_id' => $rowChild["CHILDCARD_ID"]]);
			$rowStatus = $getStatusDoc->fetch(PDO::FETCH_ASSOC);
			if(isset($rowStatus["REQUEST_STATUS"]) && $rowStatus["REQUEST_STATUS"] != ""){
				if($rowStatus["REQUEST_STATUS"] == '-9'){
					$arrChild["CAN_REQUEST"] = FALSE;
				}else{
					if($rowStatus["REQUEST_STATUS"] == '-1'){
						$arrChild["REMARK"] = $rowStatus["CANCEL_REMARK"];
					}
					$arrChild['REQUEST_STATUS'] = $rowStatus["REQUEST_STATUS"];
					$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["REQUEST_STATUS"][0][$rowStatus["REQUEST_STATUS"]][0][$lang_locale];
				}
				$checkChildHaveThisYear = $conoracle->prepare("SELECT NVL(APPROVE_STATUS,8) as APPROVE_STATUS
																FROM ASNREQSCHOLARSHIP WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and childcard_id = :childcard_id");
				$checkChildHaveThisYear->execute([':childcard_id' => $rowChild["CHILDCARD_ID"]]);
				$rowChildThisYear = $checkChildHaveThisYear->fetch(PDO::FETCH_ASSOC);
				if(isset($rowChildThisYear["APPROVE_STATUS"]) && $rowChildThisYear["APPROVE_STATUS"] != ""){
					if($rowChildThisYear["APPROVE_STATUS"] != "-9"){
						$arrChild["CAN_REQUEST"] = FALSE;
						$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["APPROVE_STATUS"][0]["REQUESTED"][0][$lang_locale];
					}else{
						$arrChild["CAN_REQUEST"] = TRUE;
					}
				}else{
					$arrChild["CAN_REQUEST"] = TRUE;
				}
			}else{
				$arrChild["CAN_REQUEST"] = TRUE;
				//$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["REQUEST_STATUS"][0]["99"][0][$lang_locale];
			}
			$arrChildGrp[] = $arrChild;
		}
		
		$checkChildAdd = $conoracle->prepare("SELECT REQUEST_STATUS, CANCEL_REMARK, CHILD_NAME, CHILD_SURNAME, CHILDCARD_ID FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) AND MEMBER_NO = :member_no");
		$checkChildAdd->execute([':member_no' => $member_no]);
		while($rowChildAdd = $checkChildAdd->fetch(PDO::FETCH_ASSOC)){
			$arrChild = array();
			$arrChild["CHILDCARD_ID"] = $rowChildAdd["CHILDCARD_ID"];
			$arrChild["CHILDCARD_ID_FORMAT"] = $lib->formatcitizen($rowChildAdd["CHILDCARD_ID"]);
			$arrChild["CHILD_NAME"] = $rowChildAdd["CHILD_NAME"]." ".$rowChildAdd["CHILD_SURNAME"];
			$getStatusDoc = $conoracle->prepare("SELECT REQUEST_STATUS, CANCEL_REMARK FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id");
			$getStatusDoc->execute([':child_id' => $rowChildAdd["CHILDCARD_ID"]]);
			$rowStatus = $getStatusDoc->fetch(PDO::FETCH_ASSOC);
			if(isset($rowStatus["REQUEST_STATUS"]) && $rowStatus["REQUEST_STATUS"] != ""){
				if($rowStatus["REQUEST_STATUS"] == '-9'){
					$arrChild["CAN_REQUEST"] = FALSE;
				}else{
					if($rowStatus["REQUEST_STATUS"] == '-1'){
						$arrChild["REMARK"] = $rowStatus["CANCEL_REMARK"];
					}
					$arrChild['REQUEST_STATUS'] = $rowStatus["REQUEST_STATUS"];
					$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["REQUEST_STATUS"][0][$rowStatus["REQUEST_STATUS"]][0][$lang_locale];
				}
				$checkChildHaveThisYear = $conoracle->prepare("SELECT NVL(APPROVE_STATUS,8) as APPROVE_STATUS
																FROM ASNREQSCHOLARSHIP WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and childcard_id = :childcard_id");
				$checkChildHaveThisYear->execute([':childcard_id' => $rowChildAdd["CHILDCARD_ID"]]);
				$rowChildThisYear = $checkChildHaveThisYear->fetch(PDO::FETCH_ASSOC);
				if(isset($rowChildThisYear["APPROVE_STATUS"]) && $rowChildThisYear["APPROVE_STATUS"] != ""){
					if($rowChildThisYear["APPROVE_STATUS"] != "-9"){
						$arrChild["CAN_REQUEST"] = FALSE;
						$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["APPROVE_STATUS"][0]["REQUESTED"][0][$lang_locale];
					}else{
						$arrChild["CAN_REQUEST"] = TRUE;
					}
				}else{
					$arrChild["CAN_REQUEST"] = TRUE;
				}
			}else{
				$arrChild["CAN_REQUEST"] = TRUE;
				//$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["REQUEST_STATUS"][0]["99"][0][$lang_locale];
			}
			$arrChildGrp[] = $arrChild;
		}
		
		$arrUploadFiles = array();
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."0";
		$arrayUpload["UPLOAD_SEQ"] = "0";
		$arrayUpload["UPLOAD_LABEL"] = "ใบเสร็จค่าเทอมหรือใบรับรองการศึกษาจากสถาบันที่กำลังศึกษาอยู่";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."1";
		$arrayUpload["UPLOAD_SEQ"] = "1";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาทะเบียนบ้านหรือสูติบัตรที่แสดงว่าเป็นบุตร โดยชอบทำตามกฎหมาย (ไม่รวมบุตรบุญธรรม)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."2";
		$arrayUpload["UPLOAD_SEQ"] = "2";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาบัตรประชาชนสมาชิก (ผู้ยื่นเรื่อง)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."3";
		$arrayUpload["UPLOAD_SEQ"] = "3";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาเลขที่บัญชีสมุดคู่บัญชีเงินฝากออมทรัพย์สหกรณ์ (แผ่นถัดไปจากปก)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		
		$arrayResult['CHILD'] = $arrChildGrp;
		$arrayResult['REQ_UPLOAD'] = $arrUploadFiles;
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