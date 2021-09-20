<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_textincome'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
		$arrayGroup = array();
		
		$fetchMsgMap = $conmysql->prepare("SELECT id_textmaptype,id_textincome,id_ref,type_message
											FROM
												lbtextmaptype
											WHERE
												id_textincome = :id_textincome AND is_use = '1'			
											ORDER BY update_date DESC");
			$fetchMsgMap->execute([
				':id_textincome' => $dataComing["id_textincome"]
			]);
			$arrayGroupIncomming=[];
			$idItem =10000;
			
			while($rowMsg = $fetchMsgMap->fetch(PDO::FETCH_ASSOC)){
				$data = [];
				$arrMsg = [];
				$arrMsg["ID_TEXTMAPTYPE"] = $rowMsg["id_textmaptype"];
				$arrMsg["ID_TEXTINCOME"] = $rowMsg["id_textincome"];
				$arrMsg["ID_REF"] = $rowMsg["id_ref"];
				$arrMsg["TYPE_MESSAGE"] = $rowMsg["type_message"];
				
				
				$arrMsg["TEXT_INCOME"] = $rowMsg["text_income"];
				$arrMsg["TEXT_MESSAGE"] = $rowMsg["text_message"];
				$arrMsg["ID_ACTION"] = $rowMsg["id_action"];
				$arrMsg["IMAGE_URL"] = $rowMsg["image_url"];
				$arrMsg["TITLE"] = $rowMsg["title"];
				$arrMsg["ADDRESS"] = $rowMsg["address"];
				$arrMsg["LATITUDE"] = $rowMsg["latitude"];
				$arrMsg["LONGTITUDE"] = $rowMsg["longtitude"];
				$arrMsg["ACTIONS"] = array();
				
				if($rowMsg["type_message"] == "quick_reply"){
					$actions = array();
					$fetchAction = $conmysql->prepare("SELECT ac.id_action,ac.type,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.data,ac.mode,ac.initial,ac.max,ac.min FROM  lbquickmessagemap qmm
									   LEFT JOIN lbaction ac ON ac.id_action = qmm.action_id
									   WHERE qmm.is_use = '1' AND ac.is_use ='1' AND qmm.quickmessage_id = :id_ref");
					$fetchAction->execute([
						':id_ref' => $rowMsg["id_ref"]
					]);
					while($rowAction = $fetchAction->fetch(PDO::FETCH_ASSOC)){
						$arrAction = array();
						$arrAction["ACTION_ID"] = $rowAction["id_action"];
						$arrAction["TYPE"] = $rowAction["type"];
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
						$actions[]= $arrAction;
					}			
				}else if($rowMsg["type_message"] == "text"){
				
					$fetchTextMsg = $conmysql->prepare("SELECT text_message FROM lbtextmessage WHERE is_use ='1' AND id_textmessage =:id_ref");
					$fetchTextMsg->execute([
						':id_ref' => $rowMsg["id_ref"]
					]);
					while($rowTextMsg = $fetchTextMsg->fetch(PDO::FETCH_ASSOC)){
						$arrMsg["TEXT_MESSAGE"] = $rowTextMsg["text_message"];
					}
				}else if($rowMsg["type_message"] == "location"){
				
					$fetchLocationMsg = $conmysql->prepare("SELECT title,address,latitude,longtitude FROM lblocation WHERE is_use ='1' AND id_location =:id_ref");
					$fetchLocationMsg->execute([
						':id_ref' => $rowMsg["id_ref"]
					]);
					while($rowLocationMsg = $fetchLocationMsg->fetch(PDO::FETCH_ASSOC)){
						$arrMsg["TITLE"] = $rowLocationMsg["title"];
						$arrMsg["ADDRESS"] = $rowLocationMsg["address"];
						$arrMsg["LATITUDE"] = $rowLocationMsg["latitude"];
						$arrMsg["LONGTITUDE"] = $rowLocationMsg["longtitude"];
					}
				}else if($rowMsg["type_message"] == "image"){
					$fetchImageMsg = $conmysql->prepare("SELECT image_url FROM lbimagemessage WHERE is_use ='1' AND id_imagemsg = :id_ref");
					$fetchImageMsg->execute([
						':id_ref' => $rowMsg["id_ref"]
					]);
					while($rowfetchImageMsg = $fetchImageMsg->fetch(PDO::FETCH_ASSOC)){
						$arrMsg["IMAGE_URL"] = $rowfetchImageMsg["image_url"];
					}
				}
				$arrMsg["ACTIONS"] = $actions;
				$data["idItem"] = $idItem;
				$data["data"] = $arrMsg;
				$data["status"] = 'old';
				$arrayGroupIncomming[] = $data;
				$idItem++;
			}
		$arrayResult["RES_DATA"] = $arrayGroupIncomming;
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