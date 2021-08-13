<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
		$arrayGroup = array();
		$fetchMsg = $conmysql->prepare("SELECT lmt.id_textmaptype,lmt.id_textincome,lmt.id_ref,lmt.type_message,li.text_income,lot.text_message,loq.id_action,loi.image_url,lol.title,lol.address,lol.latitude,lol.longtitude
													FROM lbtextmaptype lmt
													JOIN lbincoming li ON li.id_textincome = lmt.id_textincome
													 LEFT OUTER JOIN lbtextmessage lot ON (lot.id_textmessage = lmt.id_ref AND lmt.type_message = 'text')
													 LEFT OUTER JOIN lbquickmessage loq ON (loq.id_quickmsg = lmt.id_ref AND lmt.type_message = 'action')
													 LEFT OUTER JOIN lbimagemessage loi ON (loi.id_imagemsg = lmt.id_ref AND lmt.type_message = 'image')
													 LEFT OUTER JOIN lblocation lol ON (lol.id_location = lmt.id_ref AND lmt.type_message = 'location')
													 WHERE lmt.is_use = '1' AND li.is_use ='1' AND
													 ((lmt.type_message = 'text' AND lot.is_use = '1') OR 
													  (lmt.type_message = 'action' AND loq.is_use = '1') OR 
													  (lmt.type_message = 'image' AND loi.is_use = '1') OR 
													  (lmt.type_message = 'lol' AND lol.is_use = '1'))
													  ORDER BY lmt.create_date DESC");
		$fetchMsg->execute();
		while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
			$arrMsg = array();
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
			
			if($rowMsg["type_message"] == "action"){
				$fetchQuickMsg = $conmysql->prepare("SELECT qm.id_quickmsg, at.id_action, at.type, at.text, at.url, at.area_x, at.area_y, at.width, at.height, at.label, at.data, at.mode, at.initial, at.max, at.min, qm.create_date, qm.update_date, qm.update_by, at.is_use as is_use_action
													FROM lbquickmessage qm
													JOIN lbaction at ON qm.id_action = at.id_action
													WHERE qm.is_use = '1' AND qm.id_action = :id_action");
				$fetchQuickMsg->execute([
					':id_action' => $rowMsg["id_action"]
				]);
				
				while($rowQuickMsg= $fetchQuickMsg->fetch(PDO::FETCH_ASSOC)){
					$arrAction = array();
					$arrAction["TYPE"] = $rowQuickMsg["type"];
					$arrAction["TEXT"] = $rowQuickMsg["text"];
					$arrAction["URL"] = $rowQuickMsg["url"];
					$arrAction["LABEL"] = $rowQuickMsg["label"];
					$arrAction["DATA"] = $rowQuickMsg["data"];
					$arrAction["MODE"] = $rowQuickMsg["mode"];
					$arrAction["INITIAL"] = $rowQuickMsg["initial"];
					$arrAction["MAX"] = $rowQuickMsg["max"];
					$arrAction["MIN"] = $rowQuickMsg["min"];
					$arrAction["AREA_X"] = $rowQuickMsg["area_x"];
					$arrAction["AREA_Y"] = $rowQuickMsg["area_y"];
					$arrAction["WIDTH"] = $rowQuickMsg["width"];
					$arrAction["HEIGHT"] = $rowQuickMsg["height"];
					$arrAction["IS_USE_ACTION"] = $rowQuickMsg["is_use_action"];
					$arrMsg["ACTIONS"][] = $arrAction;
				}			
			}
			
			$arrayGroup[] = $arrMsg;
		}
		$arrayResult["REPLY_DATA"] = $arrayGroup;
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