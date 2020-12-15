<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpYear = array();
		$yearAss = 0;
		$fetchAssGrpYear = $conoracle->prepare("SELECT ASSIST_YEAR as ASSIST_YEAR,sum(PAY_BALANCE) as ASS_RECEIVED FROM asscontmaster 
												WHERE member_no = :member_no GROUP BY ASSIST_YEAR ORDER BY ASSIST_YEAR DESC");
		$fetchAssGrpYear->execute([':member_no' => $member_no]);
		while($rowAssYear = $fetchAssGrpYear->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["ASSIST_YEAR"] = $rowAssYear["ASSIST_YEAR"] + 543;
			$arrayYear["ASS_RECEIVED"] = number_format($rowAssYear["ASS_RECEIVED"],2);
			if($yearAss < $rowAssYear["ASSIST_YEAR"]){
				$yearAss = $rowAssYear["ASSIST_YEAR"];
			}
			$arrayGrpYear[] = $arrayYear;
		}
		if(isset($dataComing["ass_year"]) && $dataComing["ass_year"] != ""){
			$yearAss = $dataComing["ass_year"] - 543;
		}
		$fetchAssType = $conoracle->prepare("SELECT ast.ASSISTTYPE_DESC,ast.ASSISTTYPE_CODE,asm.ASSCONTRACT_NO as ASSCONTRACT_NO,asm.ASS_RCVNAME,
												asm.ASS_RCVCARDID,asm.PAY_BALANCE as ASSIST_AMT,asm.APPROVE_DATE as APPROVE_DATE,asm.APPROVE_AMT,
												asm.WITHDRAWABLE_AMT
												FROM asscontmaster asm LEFT JOIN 
												assucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE and 
												asm.coop_id = ast.coop_id WHERE asm.member_no = :member_no 
												and asm.asscont_status = 1 and asm.ASSIST_YEAR = :year");
		$fetchAssType->execute([
			':member_no' => $member_no,
			':year' => $yearAss
		]);
		$arrGroupAss = array();
		while($rowAssType = $fetchAssType->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssType["ASSIST_AMT"],2);
			$arrAss["WITHDRAWABLE_AMT"] = number_format($rowAssType["WITHDRAWABLE_AMT"],2);
			$arrAss["APPROVE_AMT"] = number_format($rowAssType["APPROVE_AMT"],2);
			$arrAss["APPROVE_DATE"] = $lib->convertdate($rowAssType["APPROVE_DATE"],'d m Y');
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrAss["RECEIVE_NAME"] = $rowAssType["ASS_RCVNAME"];
			$arrAss["RECEIVE_CARDID"] = $rowAssType["ASS_RCVCARDID"];
			$arrGroupAss[] = $arrAss;
		}
		$arrayResult["IS_STM"] = TRUE;
		$arrayResult["YEAR"] = $arrayGrpYear;
		$arrayResult["ASSIST"] = $arrGroupAss;
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