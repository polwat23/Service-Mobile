<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipExtraPayment')){
		$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGrp = array();
		$limit_show_slip_extra = $func->getConstant("limit_show_slip_extra");
		$getListCountSlip = $conmssql->prepare("SELECT TOP ".$limit_show_slip_extra." convert(varchar(4), SLIP_DATE, 112) AS EACH_YEAR,
												COUNT(PAYINSLIP_NO) AS C_SLIP 
												FROM SLSLIPPAYIN WHERE LTRIM(RTRIM(MEMBER_NO)) = :member_no
												GROUP BY  convert(varchar(4), SLIP_DATE, 112) 
												ORDER BY  convert(varchar(4), SLIP_DATE, 112)");
		$getListCountSlip->execute([
			':member_no' => $member_no
		]);
		while($rowListCountSlip = $getListCountSlip->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["YEAR"] = $rowListCountSlip["EACH_YEAR"];
			$arrayYear["COUNT"] = $rowListCountSlip["C_SLIP"];
			$getMonthSlip = $conmssql->prepare("SELECT convert(varchar(2), SLIP_DATE, 101) AS EACH_MONTH
												FROM SLSLIPPAYIN WHERE LTRIM(RTRIM(MEMBER_NO)) = :member_no and convert(varchar(4), SLIP_DATE, 112)  = :year
												GROUP BY convert(varchar(2), SLIP_DATE, 101)
												ORDER BY convert(varchar(2), SLIP_DATE, 101) DESC");
			$getMonthSlip->execute([
				':member_no' => $member_no,
				':year' => $rowListCountSlip["EACH_YEAR"]
			]);
			while($rowMonth = $getMonthSlip->fetch(PDO::FETCH_ASSOC)){
				$arrayMonth = array();
				$arrayMonth["MONTH"] = $thaimonth[$rowMonth["EACH_MONTH"]];
				$getListSlip = $conmssql->prepare("SELECT PAYINSLIP_NO FROM SLSLIPPAYIN 
													WHERE LTRIM(RTRIM(MEMBER_NO)) = :member_no and convert(varchar(4), SLIP_DATE, 112)  = :year 
													and convert(varchar(2), SLIP_DATE, 101) = :month ORDER BY PAYINSLIP_NO ASC");
				$getListSlip->execute([
					':member_no' => $member_no,
					':year' => $rowListCountSlip["EACH_YEAR"],
					':month' => $rowMonth["EACH_MONTH"]
				]);
				while($rowListSlip = $getListSlip->fetch(PDO::FETCH_ASSOC)){
					$arraySlip = array();
					$arraySlip["SLIP_NO"] = TRIM($rowListSlip["PAYINSLIP_NO"]);
					$arrayMonth["SLIP"][] = $arraySlip;
				}
				$arrayYear["MONTH"][] = $arrayMonth;
			}
			$arrGrp[] = $arrayYear;	
		}
		$arrayResult['SLIP_LIST'] = $arrGrp;
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
