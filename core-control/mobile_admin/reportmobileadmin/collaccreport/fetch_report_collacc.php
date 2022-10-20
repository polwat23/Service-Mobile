<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','collaccreport')){
		$arrGrp = array();
		
		$getCollAcc = $conmssql->prepare("SELECT REMARK, MEMBER_NO FROM MBMEMBMASTER WHERE REMARK != '' AND REMARK IS NOT NULL");
		$getCollAcc->execute();
		while($rowCollAcc = $getCollAcc->fetch(PDO::FETCH_ASSOC)){
			$arrExtraCredit = array();
			$arrExtraCredit["REMARK"] = $rowCollAcc["REMARK"];
			$arrExtraCredit["MEMBER_NO"] = $rowCollAcc["MEMBER_NO"];
			$arrGrp[] = $arrExtraCredit;
		}
				
		$arrayResult['COLL_ACC'] = $arrGrp;
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