<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','keycodeelectionlist')){
		$arrGrp = array();
		
		$getAllReqDocno = $conmysql->prepare("SELECT id_regelection, member_no, keycode, tel_mobile, register_date FROM logregisterelection");
		$getAllReqDocno->execute();
		while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
			$arrDocno = array();
			$arrDocno["ID_REGELECTION"] = $rowDocno["id_regelection"];
			$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
			$arrDocno["KEYCODE"] = $rowDocno["keycode"];
			$arrDocno["TEL_MOBILE"] = $rowDocno["tel_mobile"];
			$arrDocno["REGISTER_DATE"] = $lib->convertdate($rowDocno["register_date"],'d m Y',true);
			$arrDocno["REGISTER_DATE_RAW"] = $rowDocno["register_date"];
			$arrGrp[] = $arrDocno;
		}
			
		$arrayResult['REG_LIST'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
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