<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$fetchUserControl = $conoracle->prepare("SELECT mp.PRENAME_DESC,hr.EMP_NAME,hr.EMP_SURNAME,am.USER_NAME as USERNAME FROM amsecusers am 
										LEFT JOIN hremployee hr ON am.user_id = hr.emp_no
										LEFT JOIN mbucfprename mp ON hr.prename_code = mp.prename_code");
	$fetchUserControl->execute();
	$arrayGroupAll = array();
	while($rowUserControl = $fetchUserControl->fetch(PDO::FETCH_ASSOC)){
		$arrUser = array();
		$arrUser["USERNAME"] = $rowUserControl["USERNAME"];
		$arrUser["NAME"] = $rowUserControl["PRENAME_DESC"].$rowUserControl["EMP_NAME"]." ".$rowUserControl["EMP_SURNAME"];
		$arrayGroupAll[] = $arrUser;
	}
	$arrayResult['USERNAME'] = $arrayGroupAll;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
