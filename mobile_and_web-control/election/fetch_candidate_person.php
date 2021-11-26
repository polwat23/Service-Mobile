<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getGrpElec = $conmysql->prepare("SELECT group_code,group_desc,group_number FROM gcgroupcandidate WHERE year_election = YEAR(NOW()) + 543 ORDER BY group_number ASC");
		$getGrpElec->execute();
		$arrElec = array();
		while($rowGrpELec = $getGrpElec->fetch(PDO::FETCH_ASSOC)){
			$arrGrpElec = array();
			$arrGrpElec["GROUP_CODE"] = $rowGrpELec["group_code"];
			$arrGrpElec["GROUP_DESC"] = $rowGrpELec["group_desc"];
			$arrGrpElec["GROUP_NUMBER"] = $rowGrpELec["group_number"];
			$getELecPerson = $conmysql->prepare("SELECT number_candidate,candidate_name,candidate_avatar FROM gccandidate 
																	WHERE group_code = :grp_code and year_election = YEAR(NOW()) + 543 ORDER BY number_candidate ASC");
			$getELecPerson->execute([':grp_code' => $rowGrpELec["group_code"]]);
			while($rowElecPerson = $getELecPerson->fetch(PDO::FETCH_ASSOC)){
				$arrElecPerson = array();
				$arrElecPerson["CANDIDATE_NUMBER"] = $rowElecPerson['number_candidate'];
				$arrElecPerson["CANDIDATE_NAME"] = $rowElecPerson['candidate_name'];
				$arrElecPerson["CANDIDATE_AVATAR"] = $rowElecPerson['candidate_avatar'];
				$arrGrpElec["PERSON"][] = $arrElecPerson;
			}
			$arrElec[] = $arrGrpElec;
		}
		$arrayResult['SELECT_MIN'] = 1;
		$arrayResult['SELECT_MAX'] = 7;
		$arrayResult['ELECTION_PERSON'] = $arrElec;
		$arrayResult['TEXT_HEADER'] = "ลงคะแนนสรรหาคณะกรรมการดำเนินการ ปี 2565";
		$arrayResult['TEXT_TITLE'] = "สมาชิกเลือกได้ไม่เกิน  7  หมายเลข";
		$arrayResult['TEXT_FOOTER'] = "กรุณาตรวจสอบการเลือกหมายเลขของท่านให้เรียบร้อยก่อน กด ยืนยัน เพราะไม่สามารถกลับมาแก้ไขการสรรหาของท่านได้";
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