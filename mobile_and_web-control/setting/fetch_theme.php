<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingTheme')){
		$jsonTheme = json_decode(file_get_contents(__DIR__.'/../../config/theme.json'), true);
		if($dataComing['resolution'] >= 1440){
			$deviceResolution = 'qhd';
		}else if($dataComing['resolution'] >= 1080){
			$deviceResolution = 'fhd';
		}else if($dataComing['resolution'] >= 720){
			$deviceResolution = 'hd';
		}else{
			$deviceResolution = 'sd';
		}
		$responseData = [];
		$responseData['default'] = $jsonTheme['default'];
		$theme = [];
		foreach($jsonTheme['theme'] as $value){
			$getImageBySize = [];
			$getImageBySize['name'] = $value['name'];
			$getImageBySize['url'] = $value[$deviceResolution];
			$theme[] = $getImageBySize;
		}
		$responseData['theme'] = $theme;
		echo json_encode($responseData);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>