<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','encode_avatar','channel'],$dataComing)){
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
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไฟล์ต้องมีขนาดไม่เกิน 1.5 MB";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "File size support 1.5 MB only";
			}
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
					$arrayResult['PATH_AVATAR'] = $path_avatar;
					$arrayResult['PATH_AVATAR_WEBP'] = '/resource/avatar/'.$member_no.'/'.$createAvatar["webP_path"];
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
					if($lang_locale == 'th'){
						$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอัพโหลดรูปโปรไฟล์ได้กรุณาติดต่อสหกรณ์ #WS1008";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot upload avatar please contact cooperative #WS1008";
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0007";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "คุณสามารถอัพโหลดได้เฉพาะ JPG, JPEG, PNG";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "You can upload JPG, JPEG, PNG only";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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