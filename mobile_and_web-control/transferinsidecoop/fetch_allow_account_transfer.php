<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
												WHERE gct.allow_transaction = '1' and gat.member_no = :member_no and gat.is_use = '1'");
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
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				foreach($arrResponseAPI->accountDetail as $accData){
					if (in_array($accData->coopAccountNo, $arrayDept) && $accData->accountStatus == "0"){
						$arrAccAllow = array();
						$arrAccAllow["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($accData->coopAccountNo,$func->getConstant('hidden_dep'));
						$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
						$arrAccAllow["DEPT_TYPE"] = $accData->accountDesc;
						$arrAccAllow["BALANCE"] = preg_replace('/,/', '', $accData->availableBalance);
						$arrAccAllow["BALANCE_FORMAT"] = $accData->availableBalance;
						$arrAccAllow["BALANCE_DEST"] = preg_replace('/,/', '', $accData->accountBalance);
						$arrAccAllow["CAN_DEPOSIT"] = $accData->creditFlag == '1' ? '0' : '1';
						$arrAccAllow["CAN_WITHDRAW"] = $accData->debitFlag == '1' ? '0' : '1';
						$arrGroupAccAllow[] = $arrAccAllow;
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS9001";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$getAccFav = $conmysql->prepare("SELECT fav_refno,name_fav,destination FROM gcfavoritelist WHERE member_no = :member_no and flag_trans = 'TRN'");
				$getAccFav->execute([':member_no' => $payload["member_no"]]);
				while($rowAccFav = $getAccFav->fetch(PDO::FETCH_ASSOC)){
					$arrFavMenu = array();
					$arrFavMenu["NAME_FAV"] = $rowAccFav["name_fav"];
					$arrFavMenu["FAV_REFNO"] = $rowAccFav["fav_refno"];
					$arrFavMenu["DESTINATION"] = $rowAccFav["destination"];
					$arrFavMenu["DESTINATION_FORMAT"] = $lib->formataccount($rowAccFav["destination"],$func->getConstant('dep_format'));
					$arrFavMenu["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowAccFav["destination"],$func->getConstant('hidden_dep'));
					$arrGroupAccFav[] = $arrFavMenu;
				}
			}
			if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccFav) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult["FORMAT_DEPT"] = $func->getConstant('dep_format');
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
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
		":error_code" => "WS1016",
		":error_desc" => "รีเซ็ต Pin ไม่ได้ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไม่สามารถรีเซ็ต PIN ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateResetPin->queryString."\n"."Param => ". json_encode([
		':member_no' => $payload["member_no"]
	]);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS1016";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	require_once('../../include/exit_footer.php');
	
}
?>