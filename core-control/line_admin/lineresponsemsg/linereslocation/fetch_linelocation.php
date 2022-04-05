<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
		$arrayGroup = array();
		$fetchMsg = $conmysql->prepare("SELECT id_location, title, address, latitude, longtitude, update_date FROM lblocation WHERE is_use = '1' ORDER BY update_date desc");
		$fetchMsg->execute();
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
			$arrMsg["ID_LOCATION"] = $rowMsg["id_location"];
			$arrMsg["TITLE"] = $rowMsg["title"];
			$arrMsg["ADDRESS"] = $rowMsg["address"];
			$arrMsg["LATITUDE"] = $rowMsg["latitude"];
			$arrMsg["LONGTITUDE"] = $rowMsg["longtitude"];
			$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
			$arrayGroup[] = $arrMsg;
		}
		$arrayResult["MSG_DATA"] = $arrayGroup;
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