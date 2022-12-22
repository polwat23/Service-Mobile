<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getPageOnYears = $conmysql->prepare("SELECT group_pages FROM gcgroupcandidate WHERE year_election = YEAR(NOW()) + 543 and is_use = '1' GROUP BY group_pages");
		$getPageOnYears->execute();
		$groupYear = array();
		while($rowYear = $getPageOnYears->fetch(PDO::FETCH_ASSOC)){
			$getGrpElec = $conmysql->prepare("SELECT group_code,group_desc,group_number FROM gcgroupcandidate WHERE group_pages = :group_pages  and is_use = '1' 
														ORDER BY group_number ASC");
			$getGrpElec->execute([':group_pages' => $rowYear["group_pages"]]);
			$arrElec = array();
			$arrElecTemp = array();
			$arrElec["PAGE_ID"] = $rowYear["group_pages"];
			$arrElec['SELECT_MIN'] = 1;
			$arrElec['SELECT_MAX'] = $rowYear["group_pages"] == "0" ? 1 : 7;
			$arrElec['TEXT_HEADER'] = $rowYear["group_pages"] == "0" ? "ลงคะแนนสรรหาประธาน ปี 2566" : "ลงคะแนนสรรหาคณะกรรมการดำเนินการ ปี 2566";
			$arrElec['BUTTON_TEXT'] = $rowYear["group_pages"] == "0" ? "ถัดไป" : "ยืนยัน";
			$arrElec['TEXT_TITLE'] = $rowYear["group_pages"] == "0" ?  "สมาชิกสรรหาประธานได้ 1 หมายเลข" : "สมาชิกสรรหาได้ทุกเขตไม่เกิน  7  หมายเลข";
			$arrElec['TEXT_TITLE_PROPS'] = array("family" => "bold");
			$arrElec['TEXT_FOOTER'] = "กรุณาตรวจสอบการลงคะแนนของท่านให้เรียบร้อยก่อน แล้วจึงกดยืนยันเพราะไม่สามารถกลับมาแก้ไขได้";
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
				if($arrGrpElec["GROUP_CODE"] == '999'){
					$arrElec["CANDIDATE"][] = $arrGrpElec;
				}else{
					if(sizeof($arrElecTemp) != $getGrpElec->rowCount() - 1){
						$arrElecTemp[] = $arrGrpElec;
					}
				}
			}
			if(sizeof($arrElecTemp) > 0){
				foreach($arrElecTemp as $temp) {
					$arrElec["CANDIDATE"][] = $temp;
				}
			}
			
			$getELectioned = $conmysql->prepare("SELECT * FROM gcelection et
														WHERE et.member_no = :member_no and et.year_election = YEAR(CURDATE())+543 and et.group_page = :page_id");
			$getELectioned->execute([
				':member_no' => $payload["member_no"],
				':page_id' => $rowYear["group_pages"],
			]);
			if($getELectioned->rowCount() == 0){
				$groupYear[] = $arrElec;
			}
		}
		$arrayResult['ELECTION_PERSON'] = $groupYear;
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