<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactionimage')){
		$arrayGroup = array();
		$fetchMsg = $conmysql->prepare("SELECT id_imagemap, image_url, width_size, height_size, update_date FROM lbimagemap WHERE is_use = '1'");
		$fetchMsg->execute();
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
			$arrMsg["ID_IMAGEMAP"] = $rowMsg["id_imagemap"];
			$arrMsg["IMAGE_URL"] = $rowMsg["image_url"];
			$arrMsg["WIDTH_SIZE"] = $rowMsg["width_size"];
			$arrMsg["HEIGHT_SIZE"] = $rowMsg["height_size"];
			$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
			$arrayGroup[] = $arrMsg;
		}
		$arrayResult["IMAGE_DATA"] = $arrayGroup;
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