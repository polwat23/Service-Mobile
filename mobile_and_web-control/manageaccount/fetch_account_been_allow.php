<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no,gat.is_use,gat.limit_transaction_amt,gct.allow_showdetail,gct.allow_transaction
														FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
														WHERE gat.member_no = :member_no and gat.is_use <> '-9'");
		$fetchAccountBeenAllow->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccountBeenAllow->rowCount() > 0){
			while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
				$arrAccBeenAllow = array();
				$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
				$arrDataAPI["MemberID"] = substr($member_no,-6);
				$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
				if(!$arrResponseAPI["RESULT"]){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1031",
						":error_desc" => "ติดต่อ Server เงินฝาก Egat ไม่ได้ "."\n".json_encode($arrResponseAPI),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." ติดต่อ Server เงินฝาก Egat ไม่ได้ "."\n".json_encode($arrResponseAPI);
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS1031";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponseAPI = json_decode($arrResponseAPI);
				if($arrResponseAPI->responseCode == "200"){
					foreach($arrResponseAPI->accountDetail as $accData){
						if($accData->coopAccountNo == $rowAccBeenAllow["deptaccount_no"]){
							if($rowAccBeenAllow["allow_transaction"] == '0' && $rowAccBeenAllow["allow_showdetail"] == '0'){
								$arrAccBeenAllow["FLAG_NAME"] = $configError['ACC_ONLINE_FLAG_OFF'][0][$lang_locale];
							}else if($rowAccBeenAllow["allow_transaction"] == '1' && $rowAccBeenAllow["allow_showdetail"] == '0'){
								$arrAccBeenAllow["FLAG_NAME"] = $configError['ACC_SHOW_FLAG_OFF'][0][$lang_locale];
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_FLAG_ON'][0][$lang_locale];
							}else if($rowAccBeenAllow["allow_transaction"] == '0' && $rowAccBeenAllow["allow_showdetail"] == '1'){
								$arrAccBeenAllow["FLAG_NAME"] = $configError['ACC_TRANS_FLAG_OFF'][0][$lang_locale];
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_ACC_SHOW_FLAG_ON'][0][$lang_locale];
							}else if($rowAccBeenAllow["allow_transaction"] == '1' && $rowAccBeenAllow["allow_showdetail"] == '1'){
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_ONLINE_FLAG_ON'][0][$lang_locale];
							}
							$arrAccBeenAllow["ALLOW_TRANSACTION"] = $rowAccBeenAllow["allow_transaction"];
							$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
							$arrAccBeenAllow["DEPT_TYPE"] = $accData->accountDesc;
							$limit_coop = $func->getConstant("limit_withdraw");
							if($rowAccBeenAllow["limit_transaction_amt"] > $limit_coop){
								$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $limit_coop;
							}else{
								$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $rowAccBeenAllow["limit_transaction_amt"];
							}
							$arrAccBeenAllow["LIMIT_COOP_TRANS_AMT"] = $limit_coop;
							$arrAccBeenAllow["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
							$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
							$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"],$func->getConstant('hidden_dep'));
							$arrAccBeenAllow["STATUS_ALLOW"] = $rowAccBeenAllow["is_use"];
							$arrGroupAccAllow[] = $arrAccBeenAllow;
						}
					}
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1031",
						":error_desc" => "Error ขาติดต่อ Server เงินฝาก Egat"."\n".json_encode($arrResponseAPI),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult['RESPONSE_CODE'] = "WS1031";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
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
	echo json_encode($arrayResult);
	exit();
}
?>