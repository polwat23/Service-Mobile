<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoan = array();
			$structureReqLoan["coop_id"] = $config["COOP_ID"];
			$structureReqLoan["member_no"] = $member_no;
			$structureReqLoan["loantype_code"] = "02001";//$dataComing["loantype_code"];
			$structureReqLoan["operate_date"] = date("c");
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"atr_lnatm" => $structureReqLoan
				];
				$resultWS = $clientWS->__call("of_getloanpermiss_IVR", array($argumentWS));
				$responseSoap = $resultWS->atr_lnatm;
				$arrCredit["LOANTYPE_DESC"] = "สามัญปกติ";
				$arrCredit["LOANTYPE_CODE"] = "02001";
				$arrCredit["BUY_SHARE_MORE"] = $responseSoap->buyshr_amt ?? 0;
				$arrCredit['LOAN_PERMIT_AMT'] = $responseSoap->loanpermiss_amt ?? 0;
				$arrOldContract = array();
				if($responseSoap->nombalance_amt > 0){
					$arrOldNormal['TYPE_DESC'] = "สามัญ";
					
					$arrNormalContract['LOANTYPE_DESC'] = "สามัญคงที่";
					$arrNormalContract['CONTRACT_NO'] = "สม2562-00004";
					$arrNormalContract['BALANCE'] = $responseSoap->nombalance_amt ?? 0;
					$arrOldNormal['CONTRACT'][] = $arrNormalContract;
					$arrOldContract[] = $arrOldNormal;
				}
				
				if($responseSoap->emerbalance_amt > 0){
					$arrOldEmer['TYPE_DESC'] = "ฉุกเฉิน";
					
					$arrEmerContract['LOANTYPE_DESC'] = "ฉุกเฉิน Mobile สมาชิก";
					$arrEmerContract['CONTRACT_NO'] = "สม2562-00004";
					$arrEmerContract['BALANCE'] = $responseSoap->emerbalance_amt ?? 0;
					$arrOldEmer['CONTRACT'][] = $arrEmerContract;
					$arrOldContract[] = $arrOldEmer;
				}
				
				$arrCredit["OLD_CONTRACT"] = $arrOldContract;
				$arrayResult["LOAN_CREDIT"][] = $arrCredit;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0058",
					":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0058";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0058",
				":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." คำนวณสิทธิ์กู้ไม่ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0058";
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