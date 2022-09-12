<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBeneficiary = $conoracle->prepare("SELECT fm.base64_img,fmt.mimetypes,fm.data_type,to_char(fm.update_date,'YYYYMMDDHH24MI') as UPDATE_DATE 
												FROM fomimagemaster fm LEFT JOIN fomucfmimetype fmt ON fm.data_type = fmt.typefile
												where fm.system_code = 'mbshr' and fm.column_name = 'member_no' 
												and fm.column_data = :member_no and fm.img_type_code = '003' and rownum <= 1 ORDER BY fm.seq_no DESC");
		$getBeneficiary->execute([':member_no' => $member_no]);
		$rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC);
		if($rowBenefit["DATA_TYPE"] == '.tif'){
			$filename = $member_no.'.tif';
			$filenameExpected = $member_no.'.jpg';
			$pathname = __DIR__.'/../../resource/beneficiary/'.$filename;
			$pathnameAcctual = __DIR__.'/../../resource/beneficiary/'.$filenameExpected;
			$pathnameTmp = __DIR__.'/../../resource/beneficiary/temp_'.$filename;
			if(file_exists($pathname)){
				$fileModify = date("YmdHi", filemtime($pathname));
				if($rowBenefit["UPDATE_DATE"] >= $fileModify){
					unlink($pathnameAcctual);
					unlink($pathname);
					file_put_contents($pathnameTmp,stream_get_contents($rowBenefit["BASE64_IMG"]));
					$arrSendDataAPI["inputFile"] = $pathnameTmp;
					$arrSendDataAPI["outputFile"] = $pathname;
					$arrSendDataAPI["width"] = 900;
					$arrSendDataAPI["height"] = 1280;
					$responseAPIConvert = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendDataAPI);
					if(!$responseAPIConvert["RESULT"]){
						unlink($pathnameTmp);
						http_response_code(204);
					}
					$arrResponseAPI = json_decode($responseAPIConvert);
					if($arrResponseAPI->RESULT){
						unlink($pathnameTmp);
						$arrSendData["prefix_file_name"] = $member_no;
						$arrSendData["output"] = $config["PATH_SERVICE"].'/resource/beneficiary/'.$member_no.'.jpg';
						$arrSendData["file_name"] = $config["PATH_SERVICE"].'/resource/beneficiary/'.$filename;
						$responseAPI = $lib->posting_data($config["URL_CONVERT"],$arrSendData);
						if(!$responseAPI["RESULT"]){
							unlink($pathnameTmp);
							$message_error = "ไม่สามารถติดต่อ CONVERT Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n";
							$lib->sendLineNotify($message_error);
							http_response_code(204);
						}
						$arrResponse = json_decode($responseAPI);
						if($arrResponse->RESULT){
							$DataURLBase64 = '/resource/beneficiary/'.$member_no.'.jpg?vd='.$rowBenefit["UPDATE_DATE"];
						}else{
							unlink($pathnameTmp);
							$message_error = "ไม่สามารถ CONVERT ผู้รับผลได้ เพราะ ".json_encode($arrResponse->RESPONSE_MESSAGE);
							$lib->sendLineNotify($message_error);
							http_response_code(204);
						}
					}else{
						unlink($pathnameTmp);
						http_response_code(204);
					}
				}else{
					$DataURLBase64 = '/resource/beneficiary/'.$member_no.'.jpg?vd='.$rowBenefit["UPDATE_DATE"];
				}
			}else{
				file_put_contents($pathnameTmp,stream_get_contents($rowBenefit["BASE64_IMG"]));
				
				$arrSendDataAPI["inputFile"] = $pathnameTmp;
				$arrSendDataAPI["outputFile"] = $pathname;
				$arrSendDataAPI["width"] = 900;
				$arrSendDataAPI["height"] = 1280;
				$responseAPIConvert = $lib->posting_data($config["URL_CONVERT_IMG"],$arrSendDataAPI);
				if(!$responseAPIConvert["RESULT"]){
					unlink($pathnameTmp);
					http_response_code(204);
				}
				$arrResponseAPI = json_decode($responseAPIConvert);
				if($arrResponseAPI->RESULT){
					unlink($pathnameTmp);
					$arrSendData["prefix_file_name"] = $member_no;
					$arrSendData["output"] = $config["PATH_SERVICE"].'/resource/beneficiary/'.$member_no.'.jpg';
					$arrSendData["file_name"] = $config["PATH_SERVICE"].'/resource/beneficiary/'.$filename;
					$responseAPI = $lib->posting_data($config["URL_CONVERT"],$arrSendData);
					if(!$responseAPI["RESULT"]){
						unlink($pathname);
						$message_error = "ไม่สามารถติดต่อ CONVERT Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n";
						$lib->sendLineNotify($message_error);
						http_response_code(204);
						require_once('../../include/exit_footer.php');
					}
					$arrResponse = json_decode($responseAPI);
					if($arrResponse->RESULT){
						$DataURLBase64 = '/resource/beneficiary/'.$member_no.'.jpg?vd='.$rowBenefit["UPDATE_DATE"];
					}else{
						unlink($pathname);
						$message_error = "ไม่สามารถ CONVERT ผู้รับผลได้ เพราะ ".json_encode($arrResponse->RESPONSE_MESSAGE);
						$lib->sendLineNotify($message_error);
						http_response_code(204);
						require_once('../../include/exit_footer.php');
					}
				}else{
					unlink($pathnameTmp);
					http_response_code(204);
				}
			}
		}else{
			$filename = $member_no.$rowBenefit["DATA_TYPE"];
			$pathnameTmp = __DIR__.'/../../resource/beneficiary/temp_'.$filename;
			$pathname = __DIR__.'/../../resource/beneficiary/'.$filename;
			if(file_exists($pathname)){
				$fileModify = date("YmdHi", filemtime($pathname));
				if($rowBenefit["UPDATE_DATE"] >= $fileModify){
					unlink($pathname);
					file_put_contents($pathnameTmp,stream_get_contents($rowBenefit["BASE64_IMG"]));
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
						$DataURLBase64 = '/resource/beneficiary/'.$filename.'?v='.$rowBenefit["UPDATE_DATE"];
					}else{
						unlink($pathnameTmp);
					}
				}else{
					$DataURLBase64 = '/resource/beneficiary/'.$filename.'?v='.$rowBenefit["UPDATE_DATE"];
				}
			}else{
				file_put_contents($pathnameTmp,stream_get_contents($rowBenefit["BASE64_IMG"]));
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
					$DataURLBase64 = '/resource/beneficiary/'.$filename.'?v='.$rowBenefit["UPDATE_DATE"];
				}else{
					unlink($pathnameTmp);
				}
			}
		}
		if(isset($DataURLBase64) && $DataURLBase64 != ''){
			$arrayResult['DATA_TYPE'] = $rowBenefit["DATA_TYPE"] ?? 'pdf';
			$arrayResult["BENEFICIARY"] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256",$DataURLBase64);
			$arrayResult["FILE_TOKEN"] = $lib->generate_token_access_resource($DataURLBase64, $jwt_token, $config["SECRET_KEY_JWT"]);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
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