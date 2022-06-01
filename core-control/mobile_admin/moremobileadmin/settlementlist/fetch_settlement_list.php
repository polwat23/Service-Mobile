<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','settlementlist')){
		$arrGrp = array();
		$day = date('d');
		if($day > 7){
			$dateNow = new DateTime('now');
			$dateNow->modify('+1 month');
			$dateNow = $dateNow->format('Y-m-d');
			if(isset($dataComing['month_period']) && $dataComing['month_period'] != ""){
				$arrayResult['SETTLEMENT_PERIOD'] = $dataComing['month_period'];
			}else{
				$arrayResult['SETTLEMENT_PERIOD'] = $dateNow;
			}
			$dateUpload = new DateTime('now');
			$dateUpload->modify('+2 month');
			$dateUpload = $dateUpload->format('Y-m-d');
			$arrayResult['UPLOAD_PERIOD'] = $dateUpload;
		}else{
			$dateNow = new DateTime('now');
			$dateNow = $dateNow->format('Y-m-d');
			if(isset($dataComing['month_period']) && $dataComing['month_period'] != ""){
				$arrayResult['SETTLEMENT_PERIOD'] = $dataComing['month_period'];
			}else{
				$arrayResult['SETTLEMENT_PERIOD'] = $dateNow;
			}
			$dateUpload = new DateTime('now');
			$dateUpload->modify('+1 month');
			$dateUpload = $dateUpload->format('Y-m-d');
			$arrayResult['UPLOAD_PERIOD'] = $dateUpload;
		}
		$getAllSettlement = $conmysql->prepare("
			SELECT settlement_id, emp_no, salary, settlement_amt, upload_date, month_period FROM gcmembsettlement WHERE is_use = '1' and MONTH(month_period) = MONTH(:month_period) 
			and YEAR(month_period) = YEAR(:month_period)
		");
		$getAllSettlement->execute([
			':month_period' => $arrayResult['SETTLEMENT_PERIOD']
		]);
		while($rowAllSettlement = $getAllSettlement->fetch(PDO::FETCH_ASSOC)){
			$getMembInfo = $conmssql->prepare("
				SELECT MEMB_NAME, MEMB_SURNAME, MEMBER_NO FROM MBMEMBMASTER WHERE SALARY_ID = :emp_no
			");
			$getMembInfo->execute([
				':emp_no' => $rowAllSettlement["emp_no"]
			]);
			$rowMembInfo = $getMembInfo->fetch(PDO::FETCH_ASSOC);
			$arrData = array();
			$arrData["SETTLEMENT_ID"] = $rowAllSettlement["settlement_id"];
			$arrData["EMP_NO"] = $rowAllSettlement["emp_no"];
			$arrData["SALARY"] = $rowAllSettlement["salary"];
			$arrData["SETTLEMENT_AMT"] = $rowAllSettlement["settlement_amt"];
			$arrData["UPLOAD_DATE"] = $rowAllSettlement["upload_date"];
			$arrData["MONTH_PERIOD"] = $rowAllSettlement["month_period"];
			$arrData["MEMBER_NO"] = $rowMembInfo["MEMBER_NO"];
			$arrData["MEMBER_FULLNAME"] = $rowMembInfo["MEMB_NAME"]." ".$rowMembInfo["MEMB_SURNAME"];
			$arrGrp[] = $arrData;
		}
		
		$arrayResult['SETTLEMENT_LIST'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>