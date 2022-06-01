<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','docconfirmballist')){
		$arrGrp = array();
		$arrayExecute = array();
		if(isset($dataComing["balance_date"]) && $dataComing["balance_date"] != ''){
			$arrayExecute[':balance_date'] = $dataComing["balance_date"];
		}
		
		$getBalStatus = $conmysql->prepare("SELECT id_confirm, confirm_date,confirm_flag,confirmlon_list, confirmshr_list, balance_date, remark, url_path, member_no 
						FROM gcconfirmbalancelist 
						WHERE is_use = '1'".
						((isset($dataComing["balance_date"]) && $dataComing["balance_date"] != '') ? " and balance_date = :balance_date" : null));
		$getBalStatus->execute($arrayExecute);
		while($rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC)){
			$arrData = array();
			$arrData["ID_CONFIRM"] = $rowBalStatus["id_confirm"];
			$arrData["CONFIRM_DATE"] = $rowBalStatus["confirm_date"];
			$arrData["CONFIRM_FLAG"] = json_decode($rowBalStatus["confirm_flag"]);
			$arrData["CONFIRMLON_LIST"] = $rowBalStatus["confirmlon_list"];
			$arrData["CONFIRMSHR_LIST"] = $rowBalStatus["confirmshr_list"];
			$arrData["BALANCE_DATE"] = $rowBalStatus["balance_date"];
			$arrData["REMARK"] = $rowBalStatus["remark"];
			$arrData["URL_PATH"] = $rowBalStatus["url_path"];
			$arrData["MEMBER_NO"] = $rowBalStatus["member_no"];
			$arrGrp[] = $arrData;
		}
		
		$arrayResult['DOCCONFIRMBAL_LIST'] = $arrGrp;
		$arrayResult['BALANCE_DATE'] = $dataComing["balance_date"];
		$arrayResult['CAN_CANCEL'] = true;
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