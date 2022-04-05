<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
		$arrayGroup = array();
		$fetchMsg = $conmysql->prepare("SELECT id_bubble,title,data,update_date,creat_by 
										FROM lbbublemessage  
										WHERE is_use = '1'
										ORDER BY update_date DESC ");
		$fetchMsg->execute();
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
			$arrMsg["ID_BUBBLE"] = $rowMsg["id_bubble"];
			$arrMsg["TITLE"] = $rowMsg["title"];
			$arrMsg["DATA"] = json_decode(($rowMsg["data"]),true);
			$arrMsg["CREAT_BY"] = $rowMsg["creat_by"];
			$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
			$arrayGroup[] = $arrMsg;
		}
		$arrayResult["BUBBLE_DATA"] = $arrayGroup;
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