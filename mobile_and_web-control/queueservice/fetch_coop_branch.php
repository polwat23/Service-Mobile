<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'QueueService')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkLoan = $conoracle->prepare("SELECT LOANCONTRACT_NO FROM LNCONTMASTER 
															WHERE CONTRACT_STATUS > 0 AND CONTRACT_STATUS <> 8 AND 
															LOANTYPE_CODE IN ('23','26','31','33') AND MEMBER_NO = :member_no");
		$checkLoan->execute([':member_no' => $member_no]);
		$rowLoan = $checkLoan->fetch(PDO::FETCH_ASSOC);
		if(isset($rowLoan) && $rowLoan["LOANCONTRACT_NO"] != ""){
			$arrChildGrp = array();
			$checkChildHave = $conoracle->prepare("SELECT PREFIX_COOP, COOP_ID FROM cmcoopmaster");
			$checkChildHave->execute();
			
			while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
				$arrChild = array();
				$arrChild["COOP_BRANCH_ID"] = $rowChild["COOP_ID"];
				$arrChild["COOP_BRANCH_DESC"] = $rowChild["PREFIX_COOP"];
				$arrChildGrp[] = $arrChild;
			}
			
			$arrayResult['COOP_BRANCH'] = $arrChildGrp;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
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