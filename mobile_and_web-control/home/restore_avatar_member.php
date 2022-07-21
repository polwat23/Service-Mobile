<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','channel'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$updatAvatar = $conoracle->prepare("UPDATE gcmemberaccount SET path_avatar = null,upload_from_channel = :channel,upload_date = SYSDATE
												WHERE member_no = :member_no");
		if($updatAvatar->execute([
			':channel' => $dataComing["channel"],
			':member_no' => $payload["member_no"]
		])){
			$getAvatar = $conoracle->prepare("SELECT fm.base64_img,fmt.mimetypes,fm.DATA_TYPE,to_char(fm.update_date,'YYYYMMDDHH24MI') as UPDATE_DATE 
												FROM fomimagemaster fm LEFT JOIN fomucfmimetype fmt ON fm.data_type = fmt.typefile
												where fm.system_code = 'mbshr' and fm.column_name = 'member_no' 
												and fm.column_data = :member_no and fm.img_type_code = '001' and rownum <= 1 ORDER BY fm.seq_no DESC");
			$getAvatar->execute([':member_no' => $member_no]);
			$rowAvatar = $getAvatar->fetch(PDO::FETCH_ASSOC);
			if(isset($rowAvatar["DATA_TYPE"]) && $rowAvatar["DATA_TYPE"] != ""){
				if($rowAvatar["DATA_TYPE"] == '.tif'){
					$filename = $member_no.'.tif';
					$filenameExpected = $member_no.'.jpg';
					$pathname = __DIR__.'/../../resource/defaultAvatar/'.$filename;
					$pathnameAcctual = __DIR__.'/../../resource/defaultAvatar/'.$filenameExpected;
					$pathnameTmp = __DIR__.'/../../resource/defaultAvatar/temp_'.$filename;
					if(file_exists($pathname)){
						$fileModify = date("YmdHi", filemtime($pathname));
						if($rowAvatar["UPDATE_DATE"] >= $fileModify){
							unlink($pathnameAcctual);
							unlink($pathname);
							file_put_contents($pathnameTmp,stream_get_contents($rowAvatar["BASE64_IMG"]));
							$arrSendDataAPI["inputFile"] = $pathnameTmp;
							$arrSendDataAPI["outputFile"] = $pathname;
							$arrSendDataAPI["width"] = 800;
							$arrSendDataAPI["height"] = 1280;
							$responseAPIConvert = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendDataAPI);
							if(!$responseAPIConvert["RESULT"]){
								unlink($pathnameTmp);
							}
							$arrResponseAPI = json_decode($responseAPIConvert);
							if($arrResponseAPI->RESULT){
								unlink($pathnameTmp);
								$arrSendData["prefix_file_name"] = $member_no;
								$arrSendData["output"] = $config["PATH_SERVICE"].'/resource/defaultAvatar/'.$member_no.'.jpg';
								$arrSendData["file_name"] = $config["PATH_SERVICE"].'/resource/defaultAvatar/'.$filename;
								$responseAPI = $lib->posting_data($config["URL_CONVERT"],$arrSendData);
								if(!$responseAPI["RESULT"]){
									unlink($pathnameTmp);
								}
								$arrResponse = json_decode($responseAPI);
								if($arrResponse->RESULT){
									$DataURLBase64 = '/resource/defaultAvatar/'.$member_no.'.jpg?vd='.$rowAvatar["UPDATE_DATE"];
								}else{
									unlink($pathnameTmp);
								}
							}else{
								unlink($pathnameTmp);
							}
						}else{
							$DataURLBase64 = '/resource/defaultAvatar/'.$member_no.'.jpg';
						}
					}else{
						file_put_contents($pathnameTmp,stream_get_contents($rowAvatar["BASE64_IMG"]));
						$arrSendDataAPI["inputFile"] = $pathnameTmp;
						$arrSendDataAPI["outputFile"] = $pathname;
						$arrSendDataAPI["width"] = 900;
						$arrSendDataAPI["height"] = 1280;
						$responseAPIConvert = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendDataAPI);
						if(!$responseAPIConvert["RESULT"]){
							unlink($pathnameTmp);
						}
						$arrResponseAPI = json_decode($responseAPIConvert);
						if($arrResponseAPI->RESULT){
							unlink($pathnameTmp);
							$arrSendData["prefix_file_name"] = $member_no;
							$arrSendData["output"] = $config["PATH_SERVICE"].'/resource/defaultAvatar/'.$member_no.'.jpg';
							$arrSendData["file_name"] = $config["PATH_SERVICE"].'/resource/defaultAvatar/'.$filename;
							$responseAPI = $lib->posting_data($config["URL_CONVERT"],$arrSendData);
							if(!$responseAPI["RESULT"]){
								unlink($pathname);
							}
							$arrResponse = json_decode($responseAPI);
							if($arrResponse->RESULT){
								$DataURLBase64 = '/resource/defaultAvatar/'.$member_no.'.jpg';
							}else{
								unlink($pathname);
							}
						}else{
							unlink($pathnameTmp);
						}
					}
				}else{
					$filename = $member_no.$rowAvatar["DATA_TYPE"];
					$pathnameTmp = __DIR__.'/../../resource/defaultAvatar/temp_'.$filename;
					$pathname = __DIR__.'/../../resource/defaultAvatar/'.$filename;
					if(file_exists($pathname)){
						$fileModify = date("YmdHi", filemtime($pathname));
						if($rowAvatar["UPDATE_DATE"] >= $fileModify){
							unlink($pathname);
							file_put_contents($pathnameTmp,stream_get_contents($rowAvatar["BASE64_IMG"]));
							$arrSendData["inputFile"] = $pathnameTmp;
							$arrSendData["outputFile"] = $pathname;
							$arrSendData["width"] = 900;
							$arrSendData["height"] = 1280;
							$responseAPI = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendData);
							if(!$responseAPI["RESULT"]){
								unlink($pathnameTmp);
							}
							$arrResponse = json_decode($responseAPI);
							if($arrResponse->RESULT){
								unlink($pathnameTmp);
								$DataURLBase64 = '/resource/defaultAvatar/'.$filename.'?v='.$rowAvatar["UPDATE_DATE"];
							}else{
								unlink($pathnameTmp);
							}
						}else{
							$DataURLBase64 = '/resource/defaultAvatar/'.$filename.'?v='.$rowAvatar["UPDATE_DATE"];
						}
					}else{
						file_put_contents($pathnameTmp,stream_get_contents($rowAvatar["BASE64_IMG"]));
						$arrSendData["inputFile"] = $pathnameTmp;
						$arrSendData["outputFile"] = $pathname;
						$arrSendData["width"] = 900;
						$arrSendData["height"] = 1280;
						$responseAPI = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendData);
						if(!$responseAPI["RESULT"]){
							unlink($pathnameTmp);
						}
						$arrResponse = json_decode($responseAPI);
						if($arrResponse->RESULT){
							unlink($pathnameTmp);
							$DataURLBase64 = '/resource/defaultAvatar/'.$filename.'?v='.$rowAvatar["UPDATE_DATE"];
						}else{
							unlink($pathnameTmp);
						}
					}
				}
				
				$arrayResult["PATH_AVATAR"] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256",$DataURLBase64);
				$arrayResult["AVATAR_PATH_TOKEN"] = $lib->generate_token_access_resource($DataURLBase64, $jwt_token, $config["SECRET_KEY_JWT"]);
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1008",
				":error_desc" => "อัพโหลดรูปโปรไฟล์ไม่ได้ Path => ".$path_avatar."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "อัพโหลดรูปโปรไฟล์ไม่ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updatAvatar->queryString."\n"."Param => ". json_encode([
				':channel' => $dataComing["channel"],
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1008";
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