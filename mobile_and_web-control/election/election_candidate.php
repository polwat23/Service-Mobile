<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$conmysql->beginTransaction();
		$getELectioned = $conmysql->prepare("SELECT * FROM gcelection et
														JOIN gccandidate cd On et.id_cdperson = cd.id_cdperson
														JOIN gcgroupcandidate gc On gc.group_code = cd.group_code
														WHERE et.member_no = :member_no and et.year_election = YEAR(CURDATE())+543 and gc.group_pages = :page_id");
		$getELectioned->execute([
			':member_no' => $payload["member_no"],
			':page_id' => $dataComing["page_id"]
		]);
		if($getELectioned->rowCount() == 0){
			if(in_array('999',$dataComing["number_candidate"])){
				$insertElection = $conmysql->prepare("INSERT INTO gcelection(member_no,year_election,id_userlogin,group_page)
																			VALUES(:member_no,YEAR(NOW())+543,:id_userlogin,:page_id)");
				if($insertElection->execute([
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':page_id' => $dataComing["page_id"]
				])){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['IF'] = 1;
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['RESPONSE_CODE'] = "WS0127";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					require_once('../../include/exit_footer.php');
				}
			}else{
				$bulkInsert = array();
				foreach($dataComing["number_candidate"] as $id_cdperson){
					$bulkInsert[] = "('".$payload["member_no"]."',".$id_cdperson.",'1',YEAR(NOW())+543,".$payload["id_userlogin"].",".$dataComing["page_id"].")";
				}
				$insertElection = $conmysql->prepare("INSERT INTO gcelection(member_no,id_cdperson,is_countscore,year_election,id_userlogin,group_page)
																			VALUES".implode(',',$bulkInsert));
				if($insertElection->execute()){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['IF'] = 2;
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['RESPONSE_CODE'] = "WS0127";
					$arrayResult['RESPONSE_MESSAGE'] = $configError["ELECTION"][0]["ELECTION_REPEAT"][0][$lang_locale];
					require_once('../../include/exit_footer.php');
				}
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE_CODE'] = "WS0127";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			require_once('../../include/exit_footer.php');
		}
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