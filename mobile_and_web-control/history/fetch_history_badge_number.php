<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Notification')){
		$getBadge = $conmysql->prepare("SELECT IFNULL(COUNT(id_history),0) as badge,his_type FROM gchistory 
										WHERE member_no = :member_no AND his_read_status = '0'
										GROUP BY his_type");
		$getBadge->execute([
			':member_no' => $payload["member_no"]
		]);
		if($getBadge->rowCount() > 0){
			while($badgeData = $getBadge->fetch(PDO::FETCH_ASSOC)){
				$arrayResult['BADGE_'.$badgeData["his_type"]] = isset($badgeData["badge"]) ? $badgeData["badge"] : 0;
			}
			if(isset($arrayResult['BADGE_1'])){
				$arrayResult['BADGE_1'] = $arrayResult['BADGE_1'];
			}else{
				$arrayResult['BADGE_1'] = 0;
			}
			if(isset($arrayResult['BADGE_2'])){
				$arrayResult['BADGE_2'] = $arrayResult['BADGE_2'];
			}else{
				$arrayResult['BADGE_2'] = 0;
			}
			$arrayResult['BADGE_SUMMARY'] = $arrayResult['BADGE_1'] + $arrayResult['BADGE_2'];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
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