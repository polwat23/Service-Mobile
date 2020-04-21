<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','source_deptaccount_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		if($dataComing["source_deptaccount_no"] == $dataComing["deptaccount_no"]){
			$arrayResult['RESPONSE_CODE'] = "WS0045";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrarDataAcc = array();
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			foreach($arrResponseAPI->accountDetail as $accData){
				if ($accData->coopAccountNo == $dataComing["deptaccount_no"]){
					if($accData->accountStatus == "0" && $accData->creditFlag == "0"){
						$checkAllowToTransaction = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
						$checkAllowToTransaction->execute([':member_no' => $payload["member_no"]]);
						if($checkAllowToTransaction->rowCount() > 0){
							$arrarDataAcc["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
							$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
							$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($accData->coopAccountNo,$func->getConstant('hidden_dep'));
							$arrarDataAcc["DEPTACCOUNT_NAME"] = preg_replace('!\s+!', ' ',preg_replace('/\"/','',$accData->coopAccountName));
							$arrarDataAcc["DEPT_TYPE"] = $accData->accountDesc;
							$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
							$arrayResult['RESULT'] = TRUE;
							echo json_encode($arrayResult);
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0026";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0054";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0025";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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