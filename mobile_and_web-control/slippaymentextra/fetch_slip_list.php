<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipExtraPayment')){
		$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGrp = array();
		$limit_show_slip_extra = $func->getConstant("limit_show_slip_extra");
		$getListCountSlip = $conoracle->prepare("SELECT * FROM (SELECT EXTRACT(YEAR FROM SLIP_DATE) AS EACH_YEAR,COUNT(SLIP_NO) AS C_SLIP 
										FROM CMSHRLONSLIP WHERE TRIM(MEMBER_NO) = :member_no GROUP BY EXTRACT(YEAR FROM SLIP_DATE) 
										ORDER BY EXTRACT(YEAR FROM SLIP_DATE) DESC) WHERE rownum <= :limit_show_slip_extra");
		$getListCountSlip->execute([
			':member_no' => $member_no,
			':limit_show_slip_extra' => $limit_show_slip_extra 
		]);
		while($rowListCountSlip = $getListCountSlip->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["YEAR"] = $rowListCountSlip["EACH_YEAR"];
			$arrayYear["COUNT"] = $rowListCountSlip["C_SLIP"];
			$getMonthSlip = $conoracle->prepare("SELECT EXTRACT(MONTH FROM SLIP_DATE) AS EACH_MONTH
												FROM CMSHRLONSLIP WHERE TRIM(MEMBER_NO) = :member_no and EXTRACT(YEAR FROM SLIP_DATE) = :year
												GROUP BY EXTRACT(MONTH FROM SLIP_DATE) ORDER BY EXTRACT(MONTH FROM SLIP_DATE) ASC");
			$getMonthSlip->execute([
				':member_no' => $member_no,
				':year' => $rowListCountSlip["EACH_YEAR"]
			]);
			while($rowMonth = $getMonthSlip->fetch(PDO::FETCH_ASSOC)){
				$arrayMonth = array();
				$arrayMonth["MONTH"] = $thaimonth[$rowMonth["EACH_MONTH"]];
				$getListSlip = $conoracle->prepare("SELECT DOCUMENT_NO FROM CMSHRLONSLIP 
													WHERE TRIM(member_no) = :member_no and EXTRACT(YEAR FROM SLIP_DATE) = :year 
													and EXTRACT(MONTH FROM SLIP_DATE) = :month");
				$getListSlip->execute([
					':member_no' => $member_no,
					':year' => $rowListCountSlip["EACH_YEAR"],
					':month' => $rowMonth["EACH_MONTH"]
				]);
				while($rowListSlip = $getListSlip->fetch(PDO::FETCH_ASSOC)){
					$arraySlip = array();
					$arraySlip["SLIP_NO"] = TRIM($rowListSlip["DOCUMENT_NO"]);
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