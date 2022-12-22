<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrChildGrp = array();
		$arrChildCheck = array();
		//new
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
						$arrChild['REQUEST_STATUS_COLOR'] = "#FF0000";
						
					}
					if($rowStatus["REQUEST_STATUS"] == '9'){
						$arrChild['REQUEST_STATUS_COLOR'] = "#FF6000";
					}
					$arrChild['REQUEST_STATUS'] = $rowStatus["REQUEST_STATUS"];
					$arrChild["STATUS_DESC"] = $configError["STATUS_REQ_SCHOLAR"][0]["REQUEST_STATUS"][0][$rowStatus["REQUEST_STATUS"]][0][$lang_locale];
				}
				$checkChildHaveThisYear = $conoracle->prepare("SELECT NVL(APPROVE_STATUS,8) as APPROVE_STATUS, ASNREQUEST_DOCNO
																FROM ASNREQSCHOLARSHIP WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and childcard_id = :childcard_id and  NVL(APPROVE_STATUS,8) > 0");
				$checkChildHaveThisYear->execute([':childcard_id' => $rowChildAdd["CHILDCARD_ID"]]);
				$rowChildThisYear = $checkChildHaveThisYear->fetch(PDO::FETCH_ASSOC);
				
				//เอกสารยืนยันคำขอ
				$checkChildDoc = $conoracle->prepare("SELECT ASNREQUEST_DOCNO
																FROM ASNREQSCHOLARSHIP 
																WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and childcard_id = :childcard_id AND APPROVE_STATUS > 0");
				$checkChildDoc->execute([':childcard_id' => $rowChildAdd["CHILDCARD_ID"]]);
				$rowChildDoc = $checkChildDoc->fetch(PDO::FETCH_ASSOC);
				if(isset($rowChildDoc["ASNREQUEST_DOCNO"])){
					$arrChild["ASNREQUEST_DOCNO"] = $rowChildDoc["ASNREQUEST_DOCNO"];
					$getDocReq = $conoracle->prepare("SELECT ASNRS.ASNREQUEST_DOCNO, ASNRS.MEMBER_NO, ASNRS.SCHOLARSHIP_YEAR,  ASNRS.CHILD_NAME,  ASNRS.CHILD_SURNAME, CPRE.PRENAME_DESC AS CHILD_PRENAME, MBMP.PRENAME_DESC,MBM.MEMB_NAME,MBM.MEMB_SURNAME,ASNRS.DEPTACCOUNT_NO,
																	ASNRS.CHILDBIRTH_DATE, ASNRS.CHILDCARD_ID, SLEV.LEVEL_DESC, ASNST.TYPE_DESC, ASNRS.SCHOOL_NAME, ASNRS.ASNREQUEST_DATE
																	FROM ASNREQSCHOLARSHIP ASNRS 
																	LEFT JOIN MBUCFPRENAME CPRE ON CPRE.PRENAME_CODE = ASNRS.CHILDPRENAME_CODE
																	LEFT JOIN MBMEMBMASTER MBM ON MBM.MEMBER_NO = ASNRS.MEMBER_NO
																	LEFT JOIN MBUCFPRENAME MBMP ON MBMP.PRENAME_CODE = MBM.PRENAME_CODE
																	LEFT JOIN ASNUCFSCHOOLLEVEL SLEV ON SLEV.SCHOOL_LEVEL = ASNRS.SCHOOL_LEVEL
																	LEFT JOIN ASNUCFSCHOLARSHIPTYPE ASNST ON ASNST.SCHOLARSHIP_TYPE = ASNRS.SCHOLARSHIP_TYPE AND ASNST.COOP_ID = ASNRS.COOP_ID
																	WHERE ASNRS.ASNREQUEST_DOCNO = :request_docno");
					$getDocReq->execute([':request_docno' => $rowChildDoc["ASNREQUEST_DOCNO"]]);
					$rowDocReq = $getDocReq->fetch(PDO::FETCH_ASSOC);
					$arrChild['REQ_DOC'] = $rowDocReq;
				}
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
			$arrChildCheck[$rowChildAdd["CHILDCARD_ID"]] = $rowChildAdd["CHILDCARD_ID"];
		}
		// old
		$checkChildHave = $conoracle->prepare("SELECT asch.childcard_id as CHILDCARD_ID, mp.prename_desc||asch.child_name||'   '||asch.child_surname as CHILD_NAME, asch.ASNREQUEST_DOCNO
															FROM ASNREQSCHOLARSHIP asch LEFT JOIN mbucfprename mp ON  asch.childprename_code = mp.prename_code
															WHERE asch.approve_status = 1 and asch.scholarship_year = (EXTRACT(year from sysdate) +542) and asch.member_no = :member_no");
		$checkChildHave->execute([':member_no' => $member_no]);
		while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
			$arrChild = array();
			//เอกสารยืนยันคำขอ
			$checkChildDoc = $conoracle->prepare("SELECT ASNREQUEST_DOCNO FROM ASNREQSCHOLARSHIP WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and childcard_id = :childcard_id AND APPROVE_STATUS > 0");
			$checkChildDoc->execute([':childcard_id' => $rowChild["CHILDCARD_ID"]]);
			$rowChildDoc = $checkChildDoc->fetch(PDO::FETCH_ASSOC);
			if(isset($rowChildDoc["ASNREQUEST_DOCNO"])){
				$arrChild["ASNREQUEST_DOCNO"] = $rowChildDoc["ASNREQUEST_DOCNO"];
				$getDocReq = $conoracle->prepare("SELECT ASNRS.ASNREQUEST_DOCNO, ASNRS.MEMBER_NO, ASNRS.SCHOLARSHIP_YEAR,  ASNRS.CHILD_NAME,  ASNRS.CHILD_SURNAME, CPRE.PRENAME_DESC AS CHILD_PRENAME, MBMP.PRENAME_DESC,MBM.MEMB_NAME,MBM.MEMB_SURNAME,ASNRS.DEPTACCOUNT_NO,
																ASNRS.CHILDBIRTH_DATE, ASNRS.CHILDCARD_ID, SLEV.LEVEL_DESC, ASNST.TYPE_DESC, ASNRS.SCHOOL_NAME, ASNRS.ASNREQUEST_DATE
																FROM ASNREQSCHOLARSHIP ASNRS 
																LEFT JOIN MBUCFPRENAME CPRE ON CPRE.PRENAME_CODE = ASNRS.CHILDPRENAME_CODE
																LEFT JOIN MBMEMBMASTER MBM ON MBM.MEMBER_NO = ASNRS.MEMBER_NO
																LEFT JOIN MBUCFPRENAME MBMP ON MBMP.PRENAME_CODE = MBM.PRENAME_CODE
																LEFT JOIN ASNUCFSCHOOLLEVEL SLEV ON SLEV.SCHOOL_LEVEL = ASNRS.SCHOOL_LEVEL
																LEFT JOIN ASNUCFSCHOLARSHIPTYPE ASNST ON ASNST.SCHOLARSHIP_TYPE = ASNRS.SCHOLARSHIP_TYPE AND ASNST.COOP_ID = ASNRS.COOP_ID
																WHERE ASNRS.ASNREQUEST_DOCNO = :request_docno");
				$getDocReq->execute([':request_docno' => $rowChildDoc["ASNREQUEST_DOCNO"]]);
				$rowDocReq = $getDocReq->fetch(PDO::FETCH_ASSOC);
				$arrChild['REQ_DOC'] = $rowDocReq;
			}
			
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
			
			if(isset($arrChildCheck[$rowChild["CHILDCARD_ID"]])){
				
			}else{
				$arrChildGrp[] = $arrChild;
			}
		}
		
		$arrUploadFiles = array();
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."5";
		$arrayUpload["UPLOAD_SEQ"] = "5";
		$arrayUpload["UPLOAD_LABEL"] = "ใบรับรองการศึกษาจากสถาบันการศึกษา หรือ ใบเสร็จค่าเทอม ปีการศึกษา ".(date("Y")-0+543);
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."6";
		$arrayUpload["UPLOAD_SEQ"] = "6";
		$arrayUpload["UPLOAD_LABEL"] = "ใบรับรองผลการศึกษารายวิชาหรือสมุดรายงานประจำตัวนักเรียน ปีการศึกษา ".(date("Y")-0+542)."  (ภาคเรียนที่ 1) โดยเกรดต้องสมบูรณ์ ไม่ติด 0, ร, มส, มผ, I และ F";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."7";
		$arrayUpload["UPLOAD_SEQ"] = "7";
		$arrayUpload["UPLOAD_LABEL"] = "ใบรับรองผลการศึกษารายวิชาหรือสมุดรายงานประจำตัวนักเรียน ปีการศึกษา ".(date("Y")-0+542)."  (ภาคเรียนที่ 2) โดยเกรดต้องสมบูรณ์ ไม่ติด 0, ร, มส, มผ, I และ F";
		$arrayUpload["IS_MANDATORY"] = 0;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."8";
		$arrayUpload["UPLOAD_SEQ"] = "8";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาทะเบียนบ้านหรือสูติบัตรที่แสดงว่าเป็นบุตรโดยชอบด้วยกฎหมาย (ไม่รวมบุตรบุญธรรม)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."9";
		$arrayUpload["UPLOAD_SEQ"] = "9";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาบัตรประชาชน (ผู้ยื่นเรื่อง)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload = array();
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."10";
		$arrayUpload["UPLOAD_SEQ"] = "10";
		$arrayUpload["UPLOAD_LABEL"] = "สำเนาสมุดคู่บัญชีเงินฝากออมทรัพย์สหกรณ์ (แผ่นถัดจากหน้าปก)";
		$arrayUpload["IS_MANDATORY"] = 1;
		$arrayUpload["IS_UPLOADED"] = 0;
		$arrUploadFiles[] = $arrayUpload;
		$arrayUpload["UPLOAD_NAME"] = date('YmdHis')."11";
		$arrayUpload["UPLOAD_SEQ"] = "11";
		$arrayUpload["UPLOAD_LABEL"] = "เอกสารอื่นๆ (ถ้ามี)";
		$arrayUpload["IS_MANDATORY"] = 0;
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