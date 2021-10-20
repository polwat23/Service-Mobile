<?php
$arrPostData = array();
$arrPostData['replyToken'] = $reply_token;
$TextTemplate = $conmysql->prepare("SELECT ltm.id_ref,ltm.type_message,lic.menu_component,lic.file_service
													FROM lbincoming lic LEFT JOIN lbtextmaptype ltm ON lic.id_textincome = ltm.id_textincome
													WHERE lic.text_income = :txt_income and lic.is_use = '1'");
$TextTemplate->execute([':txt_income' => $message]);
if($TextTemplate->rowCount() > 0){
	$indexMs = 0;
	while($rowTemplate = $TextTemplate->fetch(PDO::FETCH_ASSOC)){
		$getTableName = $conmysql->prepare("SELECT table_name,condition_key FROM lbmaptypetablename WHERE type_message = :type_message");
		$getTableName->execute([':type_message' => $rowTemplate["type_message"]]);
		$rowTableName = $getTableName->fetch(PDO::FETCH_ASSOC);
		if($rowTemplate["type_message"] == 'image'){
			$getDataTemplate = $conmysql->prepare("SELECT image_url FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeImageMessage($rowDataTemplate["image_url"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'video'){
			$getDataTemplate = $conmysql->prepare("SELECT title, address, latitude,longtitude FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeLocationMessage($rowDataTemplate["title"],$rowDataTemplate["address"],$rowDataTemplate["latitude"],$rowDataTemplate["longtitude"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'location'){
			$getDataTemplate = $conmysql->prepare("SELECT title, address, latitude,longtitude FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeLocationMessage($rowDataTemplate["title"],$rowDataTemplate["address"],$rowDataTemplate["latitude"],$rowDataTemplate["longtitude"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'text'){
			$getDataTemplate = $conmysql->prepare("SELECT text_message  FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeTextMessage($rowDataTemplate["text_message"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'quick_reply'){						
			$getDataTemplate = $conmysql->prepare("SELECT ac.id_action,ac.type,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.data,ac.mode,ac.initial,ac.max,ac.min,ac.text, qm.text AS title
													FROM  lbquickmessagemap qmm
													LEFT JOIN lbaction ac ON ac.id_action = qmm.action_id
													LEFT JOIN lbquickmessage qm ON qm.id_quickmsg = qmm.quickmessage_id
													WHERE qmm.is_use = '1' AND ac.is_use ='1' AND qmm.quickmessage_id = :id_ref");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$groupDataTemplate = array();
			$typeAction = null;
			while($rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC)){
				$arrData = array();
				$arrData["ID_ACTION"] = $rowDataTemplate["id_action"];
				$arrData["TYPE"] = $rowDataTemplate["type"];
				$arrData["TITLE"] = $rowDataTemplate["title"];
				$arrData["URL"] = $rowDataTemplate["url"];
				$arrData["AREA_X"] = $rowDataTemplate["area_x"];
				$arrData["AREA_Y"] = $rowDataTemplate["area_y"];
				$arrData["WIDTH"] = $rowDataTemplate["width"];
				$arrData["HEIGHT"] = $rowDataTemplate["height"];
				$arrData["LABEL"] = $rowDataTemplate["label"];
				$arrData["DATA"] = $rowDataTemplate["data"];
				$arrData["MODE"] = $rowDataTemplate["mode"];
				$arrData["INITIAL"] = $rowDataTemplate["initial"];
				$arrData["MAX"] = $rowDataTemplate["max"];
				$arrData["MIN"] = $rowDataTemplate["min"];
				$arrData["TEXT"] = $rowDataTemplate["text"];
				$typeAction = $rowDataTemplate["type"];
				$groupDataTemplate[] = $arrData;
			}
			
			if($typeAction=='message'){
				$dataTemplate = $lineLib->mergeMessageAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction=='uri'){
				file_put_contents(__DIR__.'/../log/response.txt', json_encode($groupDataTemplate) . PHP_EOL, FILE_APPEND);
				$dataTemplate = $lineLib->mergeUrlAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction=='datetime_picker'){
				$dataTemplate = $lineLib->mergeDetetimePickerAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction =='camera'){
				$dataTemplate = $lineLib->mergeCameraAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction =='camera_roll'){
				$dataTemplate = $lineLib->mergeCameraRollAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction=='postback'){

				$dataTemplate = $lineLib->mergePostbackAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else if($typeAction=='location'){
				$dataTemplate = $lineLib->mergeLocationAction($groupDataTemplate);
				$arrPostData['messages'][0] = $dataTemplate;
			}else {
				file_put_contents(__DIR__.'/../log/response.txt', json_encode($groupDataTemplate) . PHP_EOL, FILE_APPEND);
				//$dataTemplate = $lineLib->mergeTextMessage("ลง else".$rowTemplate["id_ref"].$typeAction);
				$arrPostData['messages'][0] = $dataTemplate;
			}
		}else if($rowTemplate["type_message"] == 'image_carousel'){
			$getDataTemplate = $conmysql->prepare("SELECT co.id_columns,co.	image_url,co.action_id 
											   FROM lbimagecarouselmap tem
											   LEFT JOIN lbimagecarouselcolumns co ON co.id_columns = tem.columns_id
											   WHERE imagecarousel_id = :id_ref AND co.is_use ='1' AND tem.is_use = '1' 
											   ORDER BY tem.update_date DESC");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$data = array();
			while($rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC)){
				$arrData = array();
				$fetchActions = $conmysql->prepare("SELECT id_action,type,text,url,area_x,area_y,width,height,label,data,mode,initial,max,min 
													FROM lbaction 
													WHERE id_action = :action_id AND is_use = '1'");
				$fetchActions->execute([
					':action_id' => $rowDataTemplate["action_id"]
				]);
				$actions = array();
				while($rowAction = $fetchActions->fetch(PDO::FETCH_ASSOC)){
					//$arrAction = array();
					$arrData["ACTION_ID"] = $rowAction["id_action"];
					$arrData["ID"] = $rowAction["id_action"];
					$arrData["TYPE"] = $rowAction["type"];
					$arrData["TEXT"] = $rowAction["text"];
					$arrData["URL"] = $rowAction["url"];
					$arrData["AREA_X"] = $rowAction["area_x"];
					$arrData["AREA_Y"] = $rowAction["area_y"];
					$arrData["WIDTH"] = $rowAction["width"];
					$arrData["HEIGHT"] = $rowAction["height"];
					$arrData["LABEL"] = $rowAction["label"];
					$arrData["DATA"] = $rowAction["data"];
					$arrData["MODE"] = $rowAction["mode"];
					$arrData["INITIAL"] = $rowAction["initial"];
					$arrData["MAX"] = $rowAction["max"];
					$arrData["MIN"] = $rowAction["min"];
					//$actions =  $arrAction;
				}
				$arrData["IMAGE_URL"] = $rowDataTemplate["image_url"];
				//$arrData["ACTION"] = $actions;
				$data[] = $arrData;
			}
			file_put_contents(__DIR__.'/../log/response.txt', json_encode($data) . PHP_EOL, FILE_APPEND);
			$dataTemplate = $lineLib->mergeImageCarouselTemplate($data);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}
	 $indexMs++;
	}
}else{
	$messageErr = $func->getMsgLine('1');
	$dataTemplate = $lineLib->mergeTextMessage($messageErr);
	$arrPostData['messages'][0] = $dataTemplate;
	file_put_contents(__DIR__.'/../log/response.txt', json_encode($message) . PHP_EOL, FILE_APPEND);
}
require_once(__DIR__.'./replyresponse.php');
?>