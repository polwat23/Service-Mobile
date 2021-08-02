<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SuspendingDebtQueue')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkHavePause = FALSE;
		$checkNotLoan = TRUE;
		$checkLoan = $conoracle->prepare("SELECT LOANCONTRACT_NO FROM LNCONTMASTER 
															WHERE CONTRACT_STATUS = 1 AND 
															LOANTYPE_CODE IN ('23','26','31','33') AND MEMBER_NO = :member_no");
		$checkLoan->execute([':member_no' => $member_no]);
		while($rowLoan = $checkLoan->fetch(PDO::FETCH_ASSOC)){
			$checkPause = $conoracle->prepare("SELECT LOANCONTRACT_NO FROM lnreqmoratorium WHERE loancontract_no = :contract_no and coop_id <> '000000' and request_status = '1'  and request_date between to_date('01012021','ddmmyyyy') and to_date('26032021','ddmmyyyy')");
			$checkPause->execute([
				':contract_no' => $rowLoan["LOANCONTRACT_NO"]
			]);
			$rowPause = $checkPause->fetch(PDO::FETCH_ASSOC);
			if(isset($rowPause["LOANCONTRACT_NO"]) && $rowPause["LOANCONTRACT_NO"] != ""){
				$checkHavePause = TRUE;
			}else{
				
			}
			$checkNotLoan = FALSE;
		}
		if($checkHavePause){
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
			if($checkNotLoan){
				$arrayResult['RESPONSE_CODE'] = "WS0119";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');	
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0118";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
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