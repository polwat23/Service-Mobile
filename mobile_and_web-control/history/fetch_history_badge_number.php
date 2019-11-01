<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'Notification')){
		$getBadge = $conmysql->prepare("SELECT IFNULL(COUNT(id_history),0) as badge,his_type FROM gchistory 
										WHERE member_no = :member_no AND his_read_status = '0'
										GROUP BY his_type");
		$getBadge->execute([
			':member_no' => $payload["member_no"]
		]);
		if($getBadge->rowCount() > 0){
			while($badgeData = $getBadge->fetch()){
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
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>