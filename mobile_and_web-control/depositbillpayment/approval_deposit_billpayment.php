<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['amt_transfer'],$dataComing)){
	$transaction_no = $dataComing["tran_id"];
	$etn_ref = $dataComing["bank_ref"];
	$cmd_operate = substr($dataComing["coop_account_no"],0,2);
	$coop_account_no = preg_replace('/-/','',substr($dataComing["coop_account_no"],2));
	$time = time();
	$fee_amt = 0;
	$amt_transfer = $dataComing["amt_transfer"];
	$getAccData = $conoracle->prepare("SELECT DPM.DEPTACCOUNT_NO,DPT.MINDEPT_AMT,DPT.LIMITDEPT_FLAG,DPT.LIMITDEPT_AMT,
											DPT.MAXBALANCE,DPT.MAXBALANCE_FLAG,DPM.PRNCBAL,DPT.DEPTTYPE_CODE
											FROM DPDEPTMASTER DPM 
											LEFT JOIN DPDEPTTYPE DPT ON DPM.DEPTTYPE_CODE = DPT.DEPTTYPE_CODE
											WHERE DPM.DEPTACCOUNT_NO = :account_no");
	$getAccData->execute([':account_no' => $coop_account_no]);
	$rowAccData = $getAccData->fetch(PDO::FETCH_ASSOC);
	if(isset($rowAccData["DEPTACCOUNT_NO"]) && $rowAccData["DEPTACCOUNT_NO"] != ""){
		$checkCanReceive = $conmysql->prepare("SELECT allow_deposit_outside FROM gcconstantaccountdept WHERE dept_type_code = :depttype_code");
		$checkCanReceive->execute([':depttype_code' => $rowAccData["DEPTTYPE_CODE"]]);
		$rowCanReceive = $checkCanReceive->fetch(PDO::FETCH_ASSOC);
		if($rowCanReceive["allow_deposit_outside"] == '1'){
			if($rowAccData["LIMITDEPT_FLAG"] == '1' && $amt_transfer >= $rowAccData["LIMITDEPT_AMT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
			if($rowAccData["MAXBALANCE_FLAG"] == '1' && $amt_transfer + $rowAccData["PRNCBAL"] > $rowAccData["MAXBALANCE"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
			if($amt_transfer < $rowAccData["MINDEPT_AMT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0056";
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($rowAccDataDest["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
			$getNameMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												WHERE mb.member_no = :member_no");
			$getNameMember->execute([':member_no' => $dataComing["member_no"]]);
			$rowName = $getNameMember->fetch(PDO::FETCH_ASSOC);
			$arrayResult['RECEIVE_NAME'] = $rowName["PRENAME_SHORT"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
			$arrayResult['COOP_ACCOUNT_NO'] = $coop_account_no;
			$arrayResult['RESULT'] = TRUE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0026";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($rowAccDataDest["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			$arrayResult['RESULT'] = FALSE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0025";
		$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($rowAccDataDest["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
		$arrayResult['RESULT'] = FALSE;
		ob_flush();
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
	$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	ob_flush();
	echo json_encode($arrayResult);
	exit();
}
?>