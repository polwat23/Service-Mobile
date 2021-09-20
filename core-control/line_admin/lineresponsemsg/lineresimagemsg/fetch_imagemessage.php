<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
		$arrayGroup = array();
		$fetchMsg = $conmysql->prepare("SELECT id_imagemsg, image_url, update_date FROM lbimagemessage WHERE is_use = '1' ORDER BY update_date desc");
		$fetchMsg->execute();
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
			$arrMsg["ID_IMAGEMSG"] = $rowMsg["id_imagemsg"];
			$arrMsg["IMAGE_URL"] = $rowMsg["image_url"];
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