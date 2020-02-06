<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageNotification')){
		$fetchSettingNotify = $conmysql->prepare("SELECT receive_notify_news,receive_notify_transaction,receive_login_email
													FROM gcmemberaccount WHERE member_no = :member_no");
		$fetchSettingNotify->execute([':member_no' => $payload["member_no"]]);
		if($fetchSettingNotify->rowCount() > 0){
			$rowSetting = $fetchSettingNotify->fetch();
			$arrayResult["RECEIVE_NOTIFY_NEWS"] = $rowSetting["receive_notify_news"];
			$arrayResult["RECEIVE_NOTIFY_TRANSACTION"] = $rowSetting["receive_notify_transaction"];
			$arrayResult["RECEIVE_LOGIN_EMAIL"] = $rowSetting["receive_login_email"];
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