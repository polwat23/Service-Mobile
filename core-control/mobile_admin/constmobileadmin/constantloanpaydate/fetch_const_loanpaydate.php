<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantloanpaydate')){
		$arrayGroup = array();
		$fetchConstLoanPayDate = $conmysql->prepare("SELECT id_loanpaydate, loanpaydate FROM gcconstantloanpaydate WHERE is_use = '1'");
		$fetchConstLoanPayDate->execute();
		while($rowConst = $fetchConstLoanPayDate->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["ID_LOANPAYDATE"] = $rowConst["id_loanpaydate"];
			$arrConst["LOANPAYDATE"] = $rowConst["loanpaydate"];
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["LOANPAYDATE_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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