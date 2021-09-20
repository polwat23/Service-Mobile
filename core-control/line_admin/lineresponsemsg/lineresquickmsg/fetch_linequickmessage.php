<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
		$arrayGroup = array();
		
		$fetchMsg = $conmysql->prepare("SELECT id_quickmsg,text,update_date FROM lbquickmessage WHERE is_use = '1'");
		$fetchMsg->execute();
			
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$fetchActions = $conmysql->prepare("SELECT ac.id_action,ac.text,ac.type,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.data,ac.mode,ac.initial,ac.max,ac.min FROM  lbquickmessagemap qmm
									   LEFT JOIN lbaction ac ON ac.id_action = qmm.action_id
									   WHERE qmm.is_use = '1' AND ac.is_use ='1' AND qmm.quickmessage_id = :id_quickmsg");
			$fetchActions->execute([
				':id_quickmsg' => $rowMsg["id_quickmsg"]
			]);
			$actions = array();
			$type = null;
			while($rowAction = $fetchActions->fetch(PDO::FETCH_ASSOC)){
						$arrAction = array();
						$arrAction["ACTION_ID"] = $rowAction["id_action"];
						$arrAction["ID"] = $rowAction["id_action"];
						$arrAction["TYPE"] = $rowAction["type"];
						$arrAction["TEXT"] = $rowAction["text"];
						$arrAction["URL"] = $rowAction["url"];
						$arrAction["AREA_X"] = $rowAction["area_x"];
						$arrAction["AREA_Y"] = $rowAction["area_y"];
						$arrAction["WIDTH"] = $rowAction["width"];
						$arrAction["HEIGHT"] = $rowAction["height"];
						$arrAction["LABEL"] = $rowAction["label"];
						$arrAction["DATA"] = $rowAction["data"];
						$arrAction["MODE"] = $rowAction["mode"];
						$arrAction["INITIAL"] = $rowAction["initial"];
						$arrAction["MAX"] = $rowAction["max"];
						$arrAction["MIN"] = $rowAction["min"];
						$type = $rowAction["type"];
						$actions[]= $arrAction;
					}
			$arrMsg = array();
			$arrMsg["ID_QUICKMSG"] = $rowMsg["id_quickmsg"];
			$arrMsg["TEXT"] = $rowMsg["text"];
			$arrMsg["ACTIONS"] = $actions;
			$arrMsg["TYPE"] = $type;
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