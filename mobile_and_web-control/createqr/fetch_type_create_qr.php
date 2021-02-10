<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpTrans = array();
		$arrGrpAcc = array();
		$getTypeTransQR = $conmysql->prepare("SELECT trans_code_qr,trans_desc_qr,operation_desc_th,operation_desc_en FROM gcconttypetransqrcode WHERE is_use = '1'");
		$getTypeTransQR->execute();
		while($rowTypeQR = $getTypeTransQR->fetch(PDO::FETCH_ASSOC)){
			$arrTypeQR = array();
			$arrTypeQR["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
			$arrTypeQR["TRANS_DESC"] = $rowTypeQR["trans_desc_qr"];
			$arrTypeQR["OPERATE_DESC"] = $rowTypeQR["operation_desc_".$lang_locale];
			$arrayGrpTrans[] = $arrTypeQR;
			if($rowTypeQR["trans_code_qr"] == '01'){
				$formatDept = $func->getConstant('dep_format');
				$hiddenFormat = $func->getConstant('hidden_dep');
				$checkCanReceive = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept WHERE allow_deposit_outside = '1'");
				$checkCanReceive->execute();
				$arrDepttypeAllow = array();
				while($rowCanReceive = $checkCanReceive->fetch(PDO::FETCH_ASSOC)){
					$arrDepttypeAllow[] = $rowCanReceive["dept_type_code"];
				}
				$getAccountinTrans = $conoracle->prepare("SELECT DEPTACCOUNT_NO,DEPTACCOUNT_NAME,PRNCBAL FROM dpdeptmaster 
															WHERE member_no = :member_no and deptclose_status <> 1 and depttype_code IN('".implode(',',$arrDepttypeAllow)."')");
				$getAccountinTrans->execute([':member_no' => $member_no]);
				while($rowAccTrans = $getAccountinTrans->fetch(PDO::FETCH_ASSOC)){
					$arrAccTrans = array();
					$arrAccTrans["ACCOUNT_NO"] = $lib->formataccount($rowAccTrans["DEPTACCOUNT_NO"],$formatDept);
					$arrAccTrans["ACCOUNT_NO_HIDE"] = $lib->formataccount_hidden($arrAccTrans["ACCOUNT_NO"],$hiddenFormat);
					$arrAccTrans["ACCOUNT_NAME"] = TRIM($rowAccTrans["DEPTACCOUNT_NAME"]);
					$arrAccTrans["PRIN_BAL"] = $rowAccTrans["PRNCBAL"];
					$arrAccTrans["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrAccTrans;
				}
			}else if($rowTypeQR["trans_code_qr"] == '02'){
				
			}
		}
		$arrayResult["TYPE_TRANS"] = $arrayGrpTrans;
		$arrayResult["CHOOSE_ACCOUNT"] = $arrGrpAcc;
		$arrayResult["RESULT"] = TRUE;
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