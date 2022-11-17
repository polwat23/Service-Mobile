<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		if($dataComing["source_deptaccount_no"] == $dataComing["deptaccount_no"]){
			$arrayResult['RESPONSE_CODE'] = "WS0045";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		
		$arrarDataAcc = array();
		$getDataAcc = $conoracle->prepare("SELECT dpm.account_no as DEPTACCOUNT_NO,dpm.account_name as DEPTACCOUNT_NAME,dpt.ACC_DESC as DEPTTYPE_DESC,
											dpm.ACC_TYPE as depttype_code,dpm.BALANCE as PRNCBAL
											FROM BK_H_SAVINGACCOUNT dpm LEFT JOIN BK_M_ACC_TYPE dpt ON dpm.ACC_TYPE = dpt.ACC_TYPE
											WHERE dpm.ACC_STATUS = 'O' and dpm.account_no = :deptaccount_no");
		$getDataAcc->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
		$rowDataAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataAcc["DEPTTYPE_DESC"])){
			$fetchConstantAllowDept = $conmysql->prepare("SELECT allow_deposit_inside FROM gcconstantaccountdept 
														WHERE dept_type_code = :dept_type_code");
			$fetchConstantAllowDept->execute([
				':dept_type_code' => $rowDataAcc["DEPTTYPE_CODE"]
			]);
			$rowContAllow = $fetchConstantAllowDept->fetch(PDO::FETCH_ASSOC);
			if($rowContAllow["allow_deposit_inside"] == '1'){
				if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAcc["DEPTTYPE_CODE"].'.png')){
					$arrarDataAcc["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAcc["DEPTTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrarDataAcc["DEPT_TYPE_IMG"] = null;
				}
				$arrarDataAcc["DEPTACCOUNT_NO"] = $dataComing["deptaccount_no"];
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($dataComing["deptaccount_no"],$func->getConstant('dep_format'));
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($dataComing["deptaccount_no"],$func->getConstant('hidden_dep'));
				$arrarDataAcc["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
				$arrarDataAcc["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
				$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0026";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0025";
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
