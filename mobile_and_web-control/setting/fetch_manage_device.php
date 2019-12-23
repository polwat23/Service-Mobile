<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageDevice')){
		$arrGroupDevice = array();
		$fetchSettingDevice = $conmysql->prepare("SELECT device_name,channel,unique_id,login_date,id_token
													FROM gcuserlogin WHERE is_login = '1' and member_no = :member_no 
													GROUP BY unique_id ORDER BY id_userlogin DESC");
		$fetchSettingDevice->execute([':member_no' => $payload["member_no"]]);
		if($fetchSettingDevice->rowCount() > 0){
			while($rowSetting = $fetchSettingDevice->fetch()){
				$arrDevice = array();
				$arrDevice["DEVICE_NAME"] = $rowSetting["device_name"];
				$arrDevice["CHANNEL"] = $rowSetting["channel"];
				if($rowSetting["unique_id"] == $dataComing["unique_id"]){
					$arrDevice["THIS_DEVICE"] = true;
				}
				$arrDevice["LOGIN_DATE"] = isset($rowSetting["login_date"]) ? $lib->convertdate($rowSetting["login_date"],'D m Y',true) : null;
				$arrDevice["ACCESS_DATE"] = isset($rowSetting["access_date"]) ? $lib->convertdate($rowSetting["access_date"],'D m Y',true) : null;
				$arrDevice["ID_TOKEN"] = $rowSetting["id_token"];
				$arrGroupDevice[] = $arrDevice;
			}
			if(sizeof($arrGroupDevice) > 0 || isset($new_token)){
				$arrayResult["DEVICE"] = $arrGroupDevice;
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
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>