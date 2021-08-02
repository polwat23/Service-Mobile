<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
		$arrayGroup = array();
		
		$fetchMsg = $conmysql->prepare("SELECT qm.id_quickmsg, at.id_action, at.type, at.text, at.url, at.area_x, at.area_y, at.width, at.height, at.label, at.data, at.mode, at.initial, at.max, at.min, qm.create_date, qm.update_date, qm.update_by
													FROM lbquickmessage qm
													JOIN lbaction at ON qm.id_action = at.id_action
													WHERE qm.is_use = '1'
													ORDER BY qm.update_date desc");
		$fetchMsg->execute();
			
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
			$arrMsg["ID_QUICKMSG"] = $rowMsg["id_quickmsg"];
			$arrMsg["ID_ACTION"] = $rowMsg["id_action"];
			$arrMsg["TYPE"] = $rowMsg["type"];
			$arrMsg["TEXT"] = $rowMsg["text"];
			$arrMsg["URL"] = $rowMsg["url"];
			$arrMsg["AREA_X"] = $rowMsg["area_x"];
			$arrMsg["AREA_Y"] = $rowMsg["area_y"];
			$arrMsg["WIDTH"] = $rowMsg["width"];
			$arrMsg["HEIGHT"] = $rowMsg["height"];
			$arrMsg["LABEL"] = $rowMsg["label"];
			$arrMsg["DATA"] = $rowMsg["data"];
			$arrMsg["MODE"] = $rowMsg["mode"];
			$arrMsg["INITIAL"] = $rowMsg["initial"];
			$arrMsg["MAX"] = $rowMsg["max"];
			$arrMsg["MIN"] = $rowMsg["min"];
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