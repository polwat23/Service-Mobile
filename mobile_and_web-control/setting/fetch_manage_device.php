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
			while($rowSetting = $fetchSettingDevice->fetch(PDO::FETCH_ASSOC)){
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
			$arrayResult["DEVICE"] = $arrGroupDevice;
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>