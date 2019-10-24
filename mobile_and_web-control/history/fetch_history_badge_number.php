<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token === 'expired'){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "SQL409";
				$arrayResult['RESPONSE'] = "Invalid RefreshToken is not correct or RefreshToken was expired";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}else{
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'Notification')){
			$getBadge = $conmysql->prepare("SELECT COUNT(id_history) as badge,his_type FROM gchistory 
											WHERE member_no = :member_no AND his_read_status = 0
											GROUP BY his_type");
			$getBadge->execute([
				':member_no' => $payload["member_no"]
			]);
			if($getBadge->rowCount() > 0){
				while($badgeData = $getBadge->fetch()){
					$arrayResult['BADGE_'.$badgeData["his_type"]] = $badgeData["badge"];
				}
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "PARAM500";
			$arrayResult['RESPONSE'] = "Not permission this menu";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>