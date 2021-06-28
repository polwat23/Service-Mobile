<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantqrcode')){
		$arrayGroup = array();
		$fetchConstQrcode = $conmysql->prepare("SELECT ID_CONTTRANQR, TRANS_CODE_QR, TRANS_DESC_QR, OPERATION_DESC_TH, OPERATION_DESC_EN, UPDATE_DATE FROM GCCONTTYPETRANSQRCODE 
											WHERE IS_USE = '1'");
		$fetchConstQrcode->execute();
		while($rowConstQrcode = $fetchConstQrcode->fetch(PDO::FETCH_ASSOC)){
			$arrayConst = [];
			$arrayConst["ID_CONTTRANQR"] = $rowConstQrcode["ID_CONTTRANQR"];
			$arrayConst["TRANS_CODE_QR"] = $rowConstQrcode["TRANS_CODE_QR"];
			$arrayConst["TRANS_DESC_QR"] = $rowConstQrcode["TRANS_DESC_QR"];
			$arrayConst["OPERATION_DESC_TH"] = $rowConstQrcode["OPERATION_DESC_TH"];
			$arrayConst["OPERATION_DESC_EN"] = $rowConstQrcode["OPERATION_DESC_EN"];
			$arrayConst["UPDATE_DATE"] = $rowConstQrcode["UPDATE_DATE"];
			if($rowConstQrcode["TRANS_CODE_QR"] == '01' || $rowConstQrcode["TRANS_CODE_QR"] == '02'){
				$arrayConst["IS_LOCK"] = true;
			}else{
				$arrayConst["IS_LOCK"] = false;
			}
			$arrayGroup[] = $arrayConst;
		}
		$arrayResult["CONST_QRCODE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>