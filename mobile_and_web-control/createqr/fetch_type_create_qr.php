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
			$arrayGrpTrans[] = $arrTypeQR;
			if($rowTypeQR["trans_code_qr"] == '01'){
				$getAccountinTrans = $conoracle->prepare("SELECT DEPTACCOUNT_NO,DEPTACCOUNT_NAME,PRNCBAL FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1");
				$getAccountinTrans->execute([':member_no' => $member_no]);
				while($rowAccTrans = $getAccountinTrans->fetch(PDO::FETCH_ASSOC)){
					$arrAccTrans = array();
					$arrAccTrans["ACCOUNT_NO"] = $rowAccTrans["DEPTACCOUNT_NO"];
					$arrAccTrans["ACCOUNT_NAME"] = TRIM($rowAccTrans["DEPTACCOUNT_NAME"]);
					$arrAccTrans["PRIN_BAL"] = $rowAccTrans["PRNCBAL"];
					$arrAccTrans["TRANS_TYPE"] = $rowTypeQR["trans_code_qr"];
					$arrAccTrans["OPERATE_DESC"] = $rowTypeQR["operation_desc_".$lang_locale];
					$arrGrpAcc[] = $arrAccTrans;
				}
			}
		}
		$arrayResult["TYPE_TRANS"] = $arrayGrpTrans;
		$arrayResult["CHOOSE_ACCOUNT"] = $arrGrpAcc;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
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