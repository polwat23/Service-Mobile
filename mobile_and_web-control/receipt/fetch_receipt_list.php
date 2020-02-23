<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arraySlipGrp = array();
		$fetchSlipCount = $conoracle->prepare("SELECT COUNT(to_char(slip_date,'YYYY')) as COUNT_SLIP_YEAR,to_char(slip_date,'YYYY') as YEAR_SLIP
												FROM slslippayin WHERE member_no = :member_no GROUP BY to_char(slip_date,'YYYY') ORDER BY YEAR_SLIP DESC");
		$fetchSlipCount->execute([':member_no' => $member_no]);
		while($rowslipcountyear = $fetchSlipCount->fetch(PDO::FETCH_ASSOC)){
			$arraySlipYear = array();
			$arraySlipYear["COUNT_SLIP_YEAR"] = $rowslipcountyear["COUNT_SLIP_YEAR"];
			$arraySlipYear["YEAR_SLIP"] = $rowslipcountyear["YEAR_SLIP"] + 543;
			$fetchSlipMonthCount = $conoracle->prepare("SELECT COUNT(to_char(slip_date,'MM')) as COUNT_SLIP_MONTH,to_char(slip_date,'MM') as MONTH_SLIP
														FROM slslippayin WHERE member_no = :member_no and 
														to_char(slip_date,'YYYY') = :year_slip GROUP BY to_char(slip_date,'MM')");
			$fetchSlipMonthCount->execute([
				':member_no' => $member_no,
				':year_slip' => $rowslipcountyear["YEAR_SLIP"]
			]);
			while($rowslipcountmonth = $fetchSlipMonthCount->fetch(PDO::FETCH_ASSOC)){
				$arraySlipMonth = array();
				$arraySlipMonth["COUNT_SLIP_MONTH"] = $rowslipcountmonth["COUNT_SLIP_MONTH"];
				$arraySlipMonth["MONTH_SLIP"] = $lib->convertperiodkp($rowslipcountyear["YEAR_SLIP"].$rowslipcountmonth["MONTH_SLIP"],true);
				$fetchSlipInMonth = $conoracle->prepare("SELECT slt.sliptype_desc,sl.payinslip_no
														FROM slslippayin sl LEFT JOIN slucfsliptype slt ON sl.sliptype_code = slt.sliptype_code
														WHERE member_no = :member_no and to_char(slip_date,'YYYYMM') = :slip_date");
				$fetchSlipInMonth->execute([
					':member_no' => $member_no,
					':slip_date' => $rowslipcountyear["YEAR_SLIP"].$rowslipcountmonth["MONTH_SLIP"]
				]);
				while($rowslip = $fetchSlipInMonth->fetch(PDO::FETCH_ASSOC)){
					$arraySlip = array();
					$arraySlip["SLIP_TYPE"] = $rowslip["SLIPTYPE_DESC"];
					$arraySlip["SLIP_NO"] = $rowslip["PAYINSLIP_NO"];
					$arraySlipMonth["SLIP_LIST"][] = $arraySlip;
				}
				$arraySlipYear["MONTH_SLIP_LIST"][] = $arraySlipMonth;
			}
			$arraySlipGrp[] = $arraySlipYear;
		}
		$arrayResult['SLIP_LIST'] = $arraySlipGrp;
		$arrayResult['RESULT'] = TRUE;
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>