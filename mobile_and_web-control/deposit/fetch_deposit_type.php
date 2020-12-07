<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllAccount = array();
		$arrTypeAllow = array();
		$getTypeAllowShow = $conmysql->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
												WHERE gct.allow_showdetail = '1' and gat.member_no = :member_no and gat.is_use = '1'");
		$getTypeAllowShow->execute([':member_no' => $payload["member_no"]]);
		while($rowTypeAllow = $getTypeAllowShow->fetch(PDO::FETCH_ASSOC)){
			$arrTypeAllow[] = $rowTypeAllow["deptaccount_no"];
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
				if (in_array($accData->coopAccountNo, $arrTypeAllow) && $accData->accountStatus == "0"){
					$arrayResult['SUM_BALANCE'] += preg_replace('/,/', '', $accData->accountBalance);
					$arrAccount = array();
					$arrGroupAccount = array();
					$arrAccount["DEPTACCOUNT_NO"] =  $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
					$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($arrAccount["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
					$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
					$arrAccount["BALANCE"] = $accData->accountBalance;
					if(isset($accData->rcvintrAccountNo) && $accData->rcvintrAccountNo != ""){
						$arrAccount['RECV_INT_ACCOUNT_NO'] = $accData->rcvintrAccountNo;
					}
					if($dataComing["channel"] == 'mobile_app'){
						$fetchAlias = $conmysql->prepare("SELECT alias_name,path_alias_img FROM gcdeptalias WHERE deptaccount_no = :account_no");
						$fetchAlias->execute([
							':account_no' => $accData->coopAccountNo
						]);
						$rowAlias = $fetchAlias->fetch(PDO::FETCH_ASSOC);
						$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"] ?? null;
						if(isset($rowAlias["path_alias_img"])){
							$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
							$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp';
						}else{
							$arrAccount["ALIAS_PATH_IMG"] = null;
						}
					}
					$arrGroupAccount['TYPE_ACCOUNT'] = $accData->accountDesc;
					$arrGroupAccount['DEPT_TYPE_CODE'] = $accData->accountType;
					if(array_search($accData->accountDesc,array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
						($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
						$arrAllAccount[] = $arrGroupAccount;
					}else{
						($arrAllAccount[array_search($accData->accountDesc,array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
					}
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrayResult['SUM_BALANCE'] = number_format($arrayResult['SUM_BALANCE'],2);
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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