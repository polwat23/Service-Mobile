<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$arrGroupAccAllow = array();
		$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no,gat.is_use 
														FROM gcuserallowacctransaction gat
														WHERE gat.member_no = :member_no and gat.is_use <> '-9'");
		$fetchAccountBeenAllow->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccountBeenAllow->rowCount() > 0){
			while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
				$arrAccBeenAllow = array();
				$getDetailAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpt.depttype_desc,dpm.depttype_code,dpm.transonline_flag
														FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
														WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0");
				$getDetailAcc->execute([':deptaccount_no' => $rowAccBeenAllow["deptaccount_no"]]);
				$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
				if(isset($rowDetailAcc["DEPTACCOUNT_NAME"])){
					if($rowDetailAcc["TRANSONLINE_FLAG"] == '0'){
						$arrAccBeenAllow["FLAG_NAME"] = $configError['ACC_FLAG_OFF'][0][$lang_locale];
					}
					$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowDetailAcc["DEPTACCOUNT_NAME"]));
					$arrAccBeenAllow["DEPT_TYPE"] = $rowDetailAcc["DEPTTYPE_DESC"];
					$arrAccBeenAllow["DEPTACCOUNT_NO"] = $rowAccBeenAllow["deptaccount_no"];
					$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBeenAllow["deptaccount_no"],$func->getConstant('dep_format'));
					$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBeenAllow["deptaccount_no"],$func->getConstant('hidden_dep'));
					$arrAccBeenAllow["STATUS_ALLOW"] = $rowAccBeenAllow["is_use"];
					$arrGroupAccAllow[] = $arrAccBeenAllow;
				}
			}
			$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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