<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$arrFileUploaded = array();
		$arrUploadFiles = array();
		$arrChildCheck = array();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkFileUpload = $conoracle->prepare("SELECT seq_no, document_desc FROM asnreqschshiponlinedet
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id and upload_status <> 8 order by seq_no");
		$checkFileUpload->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowFileUpload = $checkFileUpload->fetch(PDO::FETCH_ASSOC)){
			$arrFileUploaded[] = $rowFileUpload;
		}
		// old
		$checkChildHave = $conoracle->prepare("SELECT asch.childcard_id as CHILDCARD_ID, mp.prename_desc||asch.child_name||'   '||asch.child_surname as CHILD_NAME
															FROM ASNREQSCHOLARSHIP asch LEFT JOIN mbucfprename mp ON  asch.childprename_code = mp.prename_code
															WHERE asch.approve_status = 1 and asch.scholarship_year = (EXTRACT(year from sysdate) +542) and asch.member_no = :member_no and  asch.childcard_id = :child_id");
		$checkChildHave->execute([
			':member_no' => $member_no,
			':child_id' => $dataComing["childcard_id"]
		]);
		while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
			$arrChildCheck[] = $rowChild["CHILDCARD_ID"];
		}
		if(count($arrChildCheck) > 0){
			$arrayFileManda = [
				(object) [
					'seq_no' => 1,
					'document_desc' => 'ใบรับรองผลการศึกษารายวิชาหรือสมุดรายงานประจำตัวนักเรียน ปีการศึกษา 2563  (ภาคเรียนที่ 1 และภาคเรียนที่ 2) โดยเกรดต้องสมบูรณ์ ไม่ติด 0, ร, มส, มผ, I และ F',
					'mandatory' => '1',
				],
				(object) [
					'seq_no' => 2,
					'document_desc' => 'เอกสารอื่นๆ (ถ้ามี)',
					'mandatory' => '0',
				]
			];
		}else{
			$arrayFileManda = [
				(object) [
					'seq_no' => 6,
					'document_desc' => "ใบรับรองผลการศึกษารายวิชาหรือสมุดรายงานประจำตัวนักเรียน ปีการศึกษา 2563  (ภาคเรียนที่ 1 และภาคเรียนที่ 2) โดยเกรดต้องสมบูรณ์ ไม่ติด 0, ร, มส, มผ, I และ F",
					'mandatory' => '1',
				],
				(object) [
					'seq_no' => 7,
					'document_desc' => "สำเนาทะเบียนบ้านหรือสูติบัตรที่แสดงว่าเป็นบุตรโดยชอบด้วยกฎหมาย (ไม่รวมบุตรบุญธรรม)",
					'mandatory' => '1',
				],
				(object) [
					'seq_no' => 8,
					'document_desc' => "สำเนาบัตรประชาชน (ผู้ยื่นเรื่อง)",
					'mandatory' => '1',
				],
				(object) [
					'seq_no' => 9,
					'document_desc' => "สำเนาสมุดคู่บัญชีเงินฝากออมทรัพย์สหกรณ์ (แผ่นถัดจากหน้าปก)",
					'mandatory' => '1',
				]
			];
		}
		foreach($arrayFileManda as $fileObj){
			if(array_search($fileObj->seq_no,array_column($arrFileUploaded,'SEQ_NO')) === False){
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$fileObj->seq_no;
				$arrayUpload["UPLOAD_SEQ"] = $fileObj->seq_no;
				$arrayUpload["UPLOAD_LABEL"] = $fileObj->document_desc;
				$arrayUpload["IS_MANDATORY"] = $fileObj->mandatory;
				$arrayUpload["IS_UPLOADED"] = 0;
				$arrUploadFiles[] = $arrayUpload;
			}else{
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$fileObj->seq_no;
				$arrayUpload["UPLOAD_SEQ"] = $fileObj->seq_no;
				$arrayUpload["UPLOAD_LABEL"] = $fileObj->document_desc;
				$arrayUpload["IS_MANDATORY"] = 0;
				$arrayUpload["IS_UPLOADED"] = 1;
				$arrUploadFiles[] = $arrayUpload;
			}
		}
		//new upload 
		
		/*$getUploadFiles = $conoracle->prepare("SELECT 5 as seq_no, 'ใบเสร็จค่าเทอม ปีการศึกษา '||(EXTRACT(year from sysdate) +543) as document_desc,'1' as manda
																		FROM ASNREQSCHOLARSHIP 
																		WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +542) and APPROVE_STATUS = 1 and 
																		school_level in ('13', '26', '33', '43', '53', '62') and
																		CHILDCARD_ID = :child_id");
		$getUploadFiles->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowUploadFile = $getUploadFiles->fetch(PDO::FETCH_ASSOC)){
			if(array_search($rowUploadFile["SEQ_NO"],array_column($arrFileUploaded,'SEQ_NO')) === False){
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_SEQ"] = $rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_LABEL"] = $rowUploadFile["DOCUMENT_DESC"];
				$arrayUpload["IS_MANDATORY"] = $rowUploadFile["MANDA"];
				$arrayUpload["IS_UPLOADED"] = 0;
				$arrUploadFiles[] = $arrayUpload;
			}else{
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_SEQ"] = $rowUploadFile["SEQ_NO"];
				$arrayUpload["UPLOAD_LABEL"] = $rowUploadFile["DOCUMENT_DESC"];
				$arrayUpload["IS_MANDATORY"] = 0;
				$arrayUpload["IS_UPLOADED"] = 1;
				$arrUploadFiles[] = $arrayUpload;
			}
		}*/
		$getFileUploadWaitforSend = $conoracle->prepare("SELECT seq_no, document_desc,'1' as manda FROM asnreqschshiponlinedet
																		WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id and upload_status = 8 order by seq_no");
		$getFileUploadWaitforSend->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowFileWait = $getFileUploadWaitforSend->fetch(PDO::FETCH_ASSOC)){
			if(array_search($rowFileWait["SEQ_NO"],array_column($arrUploadFiles,'UPLOAD_SEQ')) === False){
				$arrayUpload = array();
				$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowFileWait["SEQ_NO"];
				$arrayUpload["UPLOAD_SEQ"] = $rowFileWait["SEQ_NO"];
				$arrayUpload["UPLOAD_LABEL"] = $rowFileWait["DOCUMENT_DESC"];
				$arrayUpload["IS_MANDATORY"] = $rowFileWait["MANDA"];
				$arrayUpload["IS_UPLOADED"] = 0;
				$arrUploadFiles[] = $arrayUpload;
			}
		}
		usort($arrUploadFiles, 'compare_seqno');
		$checkReqStatus = $conoracle->prepare("SELECT CHILDCARD_ID,REQUEST_STATUS, CANCEL_REMARK FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) and CHILDCARD_ID = :child_id and REQUEST_STATUS <> 8");
		$checkReqStatus->execute([':child_id' => $dataComing["childcard_id"]]);
		$rowReqStatus = $checkReqStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowReqStatus["CHILDCARD_ID"])){
			if($rowReqStatus["REQUEST_STATUS"] == 1){
				$arrayResult['CAN_CLEAR'] = TRUE;
			}
			$arrayResult['REQUEST_STATUS'] = $rowReqStatus["REQUEST_STATUS"];
		}
		$arrayResult['LIST_UPLOAD'] = $arrUploadFiles;
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

function compare_seqno($a, $b)  {
	return strnatcmp($a['UPLOAD_SEQ'], $b['UPLOAD_SEQ']);
}
?>