<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arraySlipGrp = array();
		$fetchSlipCount = $conoracle->prepare("SELECT COUNT(kmd.kpslip_no) as COUNT_SLIP_YEAR,to_char(km.receipt_date,'YYYY') as YEAR_SLIP
														FROM kpmastreceive km LEFT JOIN kpmastreceivedet kmd ON km.kpslip_no = kmd.kpslip_no and km.coop_id = kmd.coop_id
														WHERE km.member_no = :member_no and km.keeping_status = '1' GROUP BY to_char(km.receipt_date,'YYYY') ORDER BY YEAR_SLIP DESC");
		$fetchSlipCount->execute([':member_no' => $member_no]);
		while($rowslipcountyear = $fetchSlipCount->fetch(PDO::FETCH_ASSOC)){
			$arraySlipYear = array();
			$arraySlipYear["COUNT_SLIP_YEAR"] = $rowslipcountyear["COUNT_SLIP_YEAR"];
			$arraySlipYear["YEAR_SLIP"] = $rowslipcountyear["YEAR_SLIP"] + 543;
			$fetchSlipMonthCount = $conoracle->prepare("SELECT COUNT(kmd.kpslip_no) as COUNT_SLIP_MONTH,to_char(km.receipt_date,'MM') as MONTH_SLIP
														FROM kpmastreceive km LEFT JOIN kpmastreceivedet kmd ON km.kpslip_no = kmd.kpslip_no and km.coop_id = kmd.coop_id
													 	WHERE km.member_no = :member_no and 
														to_char(km.receipt_date,'YYYY') = :year_slip GROUP BY to_char(km.receipt_date,'MM') ORDER BY MONTH_SLIP DESC");
			$fetchSlipMonthCount->execute([
				':member_no' => $member_no,
				':year_slip' => $rowslipcountyear["YEAR_SLIP"]
			]);
			while($rowslipcountmonth = $fetchSlipMonthCount->fetch(PDO::FETCH_ASSOC)){
				$arraySlipMonth = array();
				$arraySlipMonth["COUNT_SLIP_MONTH"] = $rowslipcountmonth["COUNT_SLIP_MONTH"];
				$arraySlipMonth["MONTH_SLIP"] = $lib->convertperiodkp($rowslipcountyear["YEAR_SLIP"].$rowslipcountmonth["MONTH_SLIP"],true);
				$fetchSlipInMonth = $conoracle->prepare("SELECT kit.KEEPITEMTYPE_DESC,km.KPSLIP_NO,km.seq_no,
													CASE kit.keepitemtype_grp 
														WHEN 'DEP' THEN km.description
														WHEN 'LON' THEN km.loancontract_no
													ELSE km.description END as PAY_ACCOUNT
														FROM kpmastreceivedet km LEFT JOIN KPUCFKEEPITEMTYPE kit ON km.keepitemtype_code = kit.keepitemtype_code
														WHERE km.member_no = :member_no and to_char(km.posting_date,'YYYYMM') = :slip_date");
				$fetchSlipInMonth->execute([
					':member_no' => $member_no,
					':slip_date' => $rowslipcountyear["YEAR_SLIP"].$rowslipcountmonth["MONTH_SLIP"]
				]);
				while($rowslip = $fetchSlipInMonth->fetch(PDO::FETCH_ASSOC)){
					$arraySlip = array();
					$arraySlip["SLIP_TYPE"] = $rowslip["KEEPITEMTYPE_DESC"].' '.$rowslip["PAY_ACCOUNT"];
					$arraySlip["SLIP_NO"] = $rowslip["KPSLIP_NO"].'/'.$rowslip["SEQ_NO"];
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