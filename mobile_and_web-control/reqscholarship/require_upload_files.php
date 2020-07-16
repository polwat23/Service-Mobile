<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrUploadFiles = array();
		$arrayFileManda = [
			(object) [
				'seq_no' => 1,
				'document_desc' => 'หน้าปกสมุดผลการศึกษา (ถ้ามี)',
				'mandatory' => '0',
			],
			(object) [
				'seq_no' => 2,
				'document_desc' => 'ผลการศึกษา ปีการศึกษา '.(date('Y') + 542).' (ภาคเรียนที่ 1)',
				'mandatory' => '1',
			],
			(object) [
				'seq_no' => 3,
				'document_desc' => 'ผลการศึกษา ปีการศึกษา '.(date('Y') + 542).' (ภาคเรียนที่ 2)',
				'mandatory' => '1',
			],
			(object) [
				'seq_no' => 4,
				'document_desc' => 'เอกสารอื่นๆ (ถ้ามี)',
				'mandatory' => '0',
			]
		];
		foreach($arrayFileManda as $fileObj){
			$arrayUpload = array();
			$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$fileObj->seq_no;
			$arrayUpload["UPLOAD_LABEL"] = $fileObj->document_desc;
			$arrayUpload["IS_MANDATORY"] = $fileObj->mandatory;
			$arrUploadFiles[] = $arrayUpload;
		}
		$getUploadFiles = $conoracle->prepare("
																	SELECT 5 as seq_no, 'ใบเสร็จค่าเทอม ปีการศึกษา '||(EXTRACT(year from sysdate) +543) as document_desc,'1' as manda
																		FROM ASNREQSCHOLARSHIP 
																		WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +542) and APPROVE_STATUS = 1 and 
																		school_level in ('13', '26', '33', '43', '53', '62') and
																		CHILDCARD_ID = :child_id");
		$getUploadFiles->execute([':child_id' => $dataComing["childcard_id"]]);
		while($rowUploadFile = $getUploadFiles->fetch(PDO::FETCH_ASSOC)){
			$arrayUpload = array();
			$arrayUpload["UPLOAD_NAME"] = date('YmdHis').$rowUploadFile["SEQ_NO"];
			$arrayUpload["UPLOAD_LABEL"] = $rowUploadFile["DOCUMENT_DESC"];
			$arrayUpload["IS_MANDATORY"] = $rowUploadFile["MANDA"];
			$arrUploadFiles[] = $arrayUpload;
		}
		$arrayResult['LIST_UPLOAD'] = $arrUploadFiles;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>