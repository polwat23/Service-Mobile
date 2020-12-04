<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$time = date("Hi");
		if($time >= 0000 && $time <= 0200){
			$arrayResult['RESPONSE_CODE'] = "WS0035";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,
												csb.bank_format_account,csb.bank_format_account_hide
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
				$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no
																FROM gcuserallowacctransaction gat
																WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' ");
				$fetchAccountBeenAllow->execute([':deptaccount_no' =>  $rowAccBind["deptaccount_no_coop"]]);
				if($fetchAccountBeenAllow->rowCount() > 0){
					$arrAccBind = array();
					$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
					$arrAccBind["DEPTACCOUNT_NO"] = $rowAccBind["deptaccount_no_coop"];
					$arrAccBind["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_coop"],$func->getConstant('dep_format'));
					$arrAccBind["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_coop"],$func->getConstant('hidden_dep'));
					$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
					$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
					$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
					$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
					$getDataAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpt.depttype_desc,dpm.prncbal,dpm.sequest_amount,dpm.sequest_status,dpt.minprncbal
														FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
														WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0 and dpm.transonline_flag = 1");
					$getDataAcc->execute([':deptaccount_no' => $rowAccBind["deptaccount_no_coop"]]);
					$rowDataAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
					if(isset($rowDataAcc["DEPTTYPE_DESC"])){
						$arrAccBind["ACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowDataAcc["DEPTACCOUNT_NAME"]));
						$arrAccBind["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
						if($rowDataAcc["SEQUEST_STATUS"] == '1'){
							$arrAccBind["BALANCE"] = $rowDataAcc["PRNCBAL"] - $rowDataAcc["SEQUEST_AMOUNT"] - $rowDataAcc["MINPRNCBAL"];
						}else{
							$arrAccBind["BALANCE"] = $rowDataAcc["PRNCBAL"] - $rowDataAcc["MINPRNCBAL"];
						}
						$arrAccBind["BALANCE_FORMAT"] = number_format($arrAccBind["BALANCE"],2);
						$arrGroupAccBind[] = $arrAccBind;
					}
				}
			}
			if(sizeof($arrGroupAccBind) > 0){
				$arrayResult['ACCOUNT'] = $arrGroupAccBind;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
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