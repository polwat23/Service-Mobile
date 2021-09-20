<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','res_type'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
		$arrayGroup = array();
		//message
		if($dataComing["res_type"] == "text"){
			$fetchMsg = $conmysql->prepare("SELECT id_textmessage, text_message, update_date FROM lbtextmessage WHERE is_use = '1' ORDER BY update_date desc");
			$fetchMsg->execute();
			while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
				$arrMsg = array();
				$arrMsg["ID_TEXTMESSAGE"] = $rowMsg["id_textmessage"];
				$arrMsg["TEXT_MESSAGE"] = $rowMsg["text_message"];
				$arrMsg["TYPE_MESSAGE"] = $dataComing["res_type"];
				$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
				$arrayGroup[] = $arrMsg;
			}
		}else if($dataComing["res_type"] == "image"){
			$fetchMsg = $conmysql->prepare("SELECT id_imagemsg, image_url, update_date FROM lbimagemessage WHERE is_use = '1' ORDER BY update_date desc");
			$fetchMsg->execute();
			while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
				$arrMsg = array();
				$arrMsg["ID_IMAGEMSG"] = $rowMsg["id_imagemsg"];
				$arrMsg["IMAGE_URL"] = $rowMsg["image_url"];
				$arrMsg["TYPE_MESSAGE"] =  $dataComing["res_type"];
				$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
				$arrayGroup[] = $arrMsg;
			}
		}else if($dataComing["res_type"] == "location"){
			$fetchMsg = $conmysql->prepare("SELECT id_location, title, address, latitude, longtitude, update_date FROM lblocation WHERE is_use = '1' ORDER BY update_date desc");
			$fetchMsg->execute();
			while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
				$arrMsg = array();
				$arrMsg["ID_LOCATION"] = $rowMsg["id_location"];
				$arrMsg["TITLE"] = $rowMsg["title"];
				$arrMsg["ADDRESS"] = $rowMsg["address"];
				$arrMsg["LATITUDE"] = $rowMsg["latitude"];
				$arrMsg["LONGTITUDE"] = $rowMsg["longtitude"];
				$arrMsg["TYPE_MESSAGE"] =  $dataComing["res_type"];
				$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
				$arrayGroup[] = $arrMsg;
			}
		}else if($dataComing["res_type"] == "quick_reply"){
			$fetchMsg = $conmysql->prepare("SELECT qm.id_quickmsg, at.id_action, at.type, at.text, at.url, at.area_x, at.area_y, at.width, at.height, at.label, at.data, at.mode, at.initial, at.max, at.min, qm.create_date, qm.update_date, qm.update_by
														FROM lbquickmessage qm
														JOIN lbaction at ON qm.id_action = at.id_action
														WHERE qm.is_use = '1' AND at.is_use = '1'
														ORDER BY qm.update_date desc");
			$fetchMsg->execute();
				
			while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
				$arrMsg = array();
				$arrMsg["ID_QUICKMSG"] = $rowMsg["id_quickmsg"];
				$arrMsg["ID_ACTION"] = $rowMsg["id_action"];
				$arrMsg["TYPE"] = $rowMsg["type"];
				$arrMsg["TYPE_MESSAGE"] = $rowMsg["type"];
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
		}
		$arrayResult["RES_DATA"] = $arrayGroup;
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