<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$arrayResult = array();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no,acc_name ,acc_surname FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $member_no]);

		if($memberInfoMobile->rowCount() > 0){
				
			$memberInfo = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,
												MP.SUFFNAME_DESC
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $payload["ref_memno"]]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrayResult["COOP_NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["SUFFNAME_DESC"];
			
			$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
			$arrayResult["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			$arrayResult["NAME"] = $rowInfoMobile["acc_name"];
			$arrayResult["SURNAME"] = $rowInfoMobile["acc_surname"];
			$arrayResult["MEMBER_NO"] = $payload["member_no"];
			$arrayResult["REF_MEMNO"] = $payload["ref_memno"];
			if(isset($rowInfoMobile["path_avatar"])){
				if ($forceNewSecurity == true) {
					$arrayResult['AVATAR_PATH'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_TOKEN"] = $lib->generate_token_access_resource($rowInfoMobile["path_avatar"], $jwt_token, $config["SECRET_KEY_JWT"]);
					
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $explodePathAvatar[0].'.webp');
					$arrayResult["AVATAR_PATH_WEBP_TOKEN"] = $lib->generate_token_access_resource($explodePathAvatar[0].'.webp', $jwt_token, $config["SECRET_KEY_JWT"]);
				} else {
					$arrayResult["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
				}
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
			
			//	ข้อมูลทั่วไป		
			/*$memberInfo = $conmysql->prepare("SELECT register_date FROM gcmemberaccount WHERE member_no = :member_no"); 
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["register_date"],"ym");*/
			
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = $member_no;
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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