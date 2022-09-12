<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$getLoanCredit = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_estimate_creditloan = '1'");
		$arrLoanAllow = array();
		$getLoanCredit->execute();
		while($rowCreditAllow = $getLoanCredit->fetch(PDO::FETCH_ASSOC)){
			$arrLoanAllow[] = "'".$rowCreditAllow["loantype_code"]."'";
		}
		/*$fetchIntrate = $conoracle->prepare("select (lir.interest_rate ) as interest_rate,lp.loantype_desc,lp.loantype_code from lnloantype lp LEFT JOIN lncfloanintratedet lir
												ON lp.inttabrate_code = lir.loanintrate_code where lp.loantype_code IN(".implode(',',$arrLoanAllow).") and
												to_char(sysdate,'YYYY-MM-DD') BETWEEN 
												to_char(lir.effective_date,'YYYY-MM-DD') and to_char(lir.expire_date,'YYYY-MM-DD')");
		$fetchIntrate->execute();*/
		$ArrRowIntrate = array();
		$intrate = array();
		$intrate["INTEREST_RATE"] = "1";
		$intrate["LOANTYPE_CODE"] = "0";
		$intrate["LOANTYPE_DESC"] = "สินเชื่อสามัญปกติ";
		$ArrRowIntrate[] = $intrate;
		$intrate = array();
		$intrate["INTEREST_RATE"] = "1";
		$intrate["LOANTYPE_CODE"] = "1";
		$intrate["LOANTYPE_DESC"] = "สินเชื่อคุณภาพ";
		$ArrRowIntrate[] = $intrate;
		$intrate = array();
		$intrate["INTEREST_RATE"] = "1";
		$intrate["LOANTYPE_CODE"] = "2";
		$intrate["LOANTYPE_DESC"] = "สินเชื่อคุณภาพแบบขั้นบันได";
		$ArrRowIntrate[] = $intrate;
		
		$arrIntGroup = array();
		foreach ($ArrRowIntrate as &$rowIntrate) {
			$arrIntrate = array();
			$arrIntrate["INT_RATE"] = $rowIntrate["INTEREST_RATE"];
			$arrIntrate["LOANTYPE_CODE"] = $rowIntrate["LOANTYPE_CODE"];
			$arrIntrate["LOANTYPE_DESC"] = $rowIntrate["LOANTYPE_DESC"];
			$arrIntrate["EXTEND_COLUMN"] = array();
			if($rowIntrate["LOANTYPE_CODE"] == "2"){
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "แต่ละงวดห่างกัน";
				$arrExtendColumn["COLUMN_KEY"] = "each_period";
				$arrExtendColumn["COLUMN_TYPE"] = "int";
				$arrExtendColumn["COLUMN_DEFAULT"] = "1";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "อัตราค่าบริการส่วนลดเริ่มต้น";
				$arrExtendColumn["COLUMN_TYPE"] = "double";
				$arrExtendColumn["COLUMN_KEY"] = "min_int";
				$arrExtendColumn["COLUMN_DEFAULT"] = "3.5";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "จำนวนช่วง";
				$arrExtendColumn["COLUMN_KEY"] = "group_period";
				$arrExtendColumn["COLUMN_TYPE"] = "int";
				$arrExtendColumn["COLUMN_DEFAULT"] = "3";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "ห่างกันช่วงละ (ปี)";
				$arrExtendColumn["COLUMN_KEY"] = "group_period_range";
				$arrExtendColumn["COLUMN_TYPE"] = "int";
				$arrExtendColumn["COLUMN_DEFAULT"] = "5";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "ค่าบริการแต่ละช่วง";
				$arrExtendColumn["COLUMN_KEY"] = "int_period_range";
				$arrExtendColumn["COLUMN_TYPE"] = "double";
				$arrExtendColumn["COLUMN_DEFAULT"] = "0.5";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
			}else{
				$arrExtendColumn = array();
				$arrExtendColumn["COLUMN_LABEL"] = "แต่ละงวดห่างกัน";
				$arrExtendColumn["COLUMN_KEY"] = "each_period";
				$arrExtendColumn["COLUMN_TYPE"] = "int";
				$arrExtendColumn["COLUMN_DEFAULT"] = "1";
				$arrExtendColumn["IS_REQUIRE"] = true;
				$arrIntrate["EXTEND_COLUMN"][] = $arrExtendColumn;
			}
			$arrIntGroup[] = $arrIntrate;
		}
		$arrayResult['INT_RATE'] = $arrIntGroup;
		$arrayResult['DISABLED_CAL_TYPE'] = TRUE;
		$arrayResult['DISABLED_PAY_TYPE'] = TRUE;
		$arrayResult['DISABLED_PAY_DATE'] = TRUE;
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