<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		if(in_array('999',$dataComing["number_candidate"])){
			$insertElection = $conmysql->prepare("INSERT INTO gcelection(member_no,year_election,id_userlogin)
																		VALUES(:member_no,YEAR(NOW())+543,:id_userlogin)");
			if($insertElection->execute([
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE_CODE'] = "WS0127";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				require_once('../../include/exit_footer.php');
			}
		}else{
			$bulkInsert = array();
			foreach($dataComing["number_candidate"] as $id_cdperson){
				$bulkInsert[] = "('".$payload["member_no"]."',".$id_cdperson.",'1',YEAR(NOW())+543,".$payload["id_userlogin"].")";
			}
			$insertElection = $conmysql->prepare("INSERT INTO gcelection(member_no,id_cdperson,is_countscore,year_election,id_userlogin)
																		VALUES".implode(',',$bulkInsert));
			if($insertElection->execute()){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE_CODE'] = "WS0127";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				require_once('../../include/exit_footer.php');
			}
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