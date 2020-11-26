<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$time = date("Hi");
		if($time >= 0000 && $time <= 0200){
			$arrayResult['RESPONSE_CODE'] = "WS0035";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,
												csb.bank_format_account,csb.bank_format_account_hide,gba.bank_code,csb.bank_short_name
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
				$arrAccBind = array();
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["BANK_NAME"] = $rowAccBind["bank_short_name"];
				$arrAccBind["BANK_CODE"] = $rowAccBind["bank_code"];
				$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
				$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
				$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
				$arrGroupAccBind["BIND"][] = $arrAccBind;
			}
			$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no 
													FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON 
													gat.id_accountconstant = gct.id_accountconstant
													WHERE gct.allow_transaction = '1' and gat.member_no = :member_no and gat.is_use = '1' and gct.dept_type_code <> '50'");
			$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
			if($fetchAccAllowTrans->rowCount() > 0){
				while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
					$arrayDept[] = $rowAccAllow["deptaccount_no"];
				}
				$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
				$arrDataAPI["MemberID"] = substr($member_no,-6);
				$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
				if(!$arrResponseAPI["RESULT"]){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS9999",
						":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponseAPI = json_decode($arrResponseAPI);
				if($arrResponseAPI->responseCode == "200"){
					foreach($arrResponseAPI->accountDetail as $accData){
						if (in_array($accData->coopAccountNo, $arrayDept) && $accData->accountStatus == "0"){
							$arrAccCoop = array();
							$arrAccCoop["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
							$arrAccCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
							$arrAccCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccCoop["DEPTACCOUNT_NO_FORMAT"],$func->getConstant('hidden_dep'));
							$arrAccCoop["ACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
							$arrAccCoop["DEPT_TYPE"] = $accData->accountDesc;
							$arrAccCoop["BALANCE"] = preg_replace('/,/', '', $accData->availableBalance);
							$arrAccCoop["BALANCE_FORMAT"] = $accData->availableBalance;
							$arrGroupAccBind["COOP"][] = $arrAccCoop;
						}
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS9001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			if(sizeof($arrGroupAccBind["BIND"]) > 0 && sizeof($arrGroupAccBind["COOP"]) > 0){
				$arrayResult['ACCOUNT'] = $arrGroupAccBind;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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