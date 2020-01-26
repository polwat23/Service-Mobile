<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','encode_avatar','channel'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$arrayResult = array();
		$member_no = $payload["member_no"];
		$encode_avatar = $dataComing["encode_avatar"];
		$destination = __DIR__.'/../../resource/avatar/'.$member_no;
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createAvatar = $lib->base64_to_img($encode_avatar,$file_name,$destination,$webP);
		if($createAvatar == 'oversize'){
			$arrayResult['RESPONSE_CODE'] = "WS0008";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createAvatar){
				$path_avatar = '/resource/avatar/'.$member_no.'/'.$createAvatar["normal_path"];
				$insertIntoInfo = $conmysql->prepare("UPDATE gcmemberaccount SET path_avatar = :path_avatar,upload_from_channel = :channel,upload_date = NOW()
														WHERE member_no = :member_no");
				if($insertIntoInfo->execute([
					':path_avatar' => $path_avatar,
					':channel' => $dataComing["channel"],
					':member_no' => $member_no
				])){
					$arrayResult['PATH_AVATAR'] = $config["URL_SERVICE"].$path_avatar;
					$arrayResult['PATH_AVATAR_WEBP'] = $config["URL_SERVICE"].'/resource/avatar/'.$member_no.'/'.$createAvatar["webP_path"];
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrExecute = [
						':path_avatar' => $path_avatar,
						':channel' => $dataComing["channel"],
						':member_no' => $member_no
					];
					$arrError = array();
					$arrError["EXECUTE"] = $arrExecute;
					$arrError["QUERY"] = $insertIntoInfo;
					$arrError["ERROR_CODE"] = 'WS1008';
					$lib->addLogtoTxt($arrError,'upload_error');
					$arrayResult['RESPONSE_CODE'] = "WS1008";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0007";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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