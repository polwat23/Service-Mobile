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
			$fetchMsg = $conmysql->prepare("SELECT id_quickmsg,text FROM lbquickmessage WHERE is_use ='1'");
			$fetchMsg->execute();
				
			while($rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC)){
				$actions = array();
				$fetchAction = $conmysql->prepare("SELECT ac.id_action,ac.type,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.data,ac.mode,ac.initial,ac.max,ac.min FROM  lbquickmessagemap qmm
													LEFT JOIN lbaction ac ON ac.id_action = qmm.action_id
													WHERE qmm.is_use = '1' AND ac.is_use ='1' AND qmm.quickmessage_id = :quickmessage_id");
				$fetchAction->execute([
					':quickmessage_id' => $rowMsg["id_quickmsg"]
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
					$type =  $rowAction["type"];
					$actions[]= $arrAction;
				}
				$arrMsg = array();
				$arrMsg["ID_QUICKMSG"] = $rowMsg["id_quickmsg"];
				$arrMsg["TEXT"] = $rowMsg["text"];
				$arrMsg["TYPE"] = $type;
				$arrMsg["TYPE_MESSAGE"] =  $dataComing["res_type"];
				$arrMsg["ACTIONS"] = $actions;
				$arrMsg["UPDATE_DATE"] = $rowMsg["update_date"];
				$arrayGroup[] = $arrMsg;
			}
		}else if($dataComing["res_type"] == "image_carousel"){
			$fetctImageCarousel = $conmysql->prepare("SELECT id_imagecarousel,update_date,update_by FROM lbimagecarouseltemplate WHERE is_use ='1' ORDER BY update_date DESC");
			$fetctImageCarousel->execute();
			while($rowImageCarousel = $fetctImageCarousel->fetch(PDO::FETCH_ASSOC)){
				$arrMsg = array();
				$fetctColumn = $conmysql->prepare("SELECT co.id_columns,co.	image_url,co.action_id 
												   FROM lbimagecarouselmap tem
												   LEFT JOIN lbimagecarouselcolumns co ON co.id_columns = tem.columns_id
												   WHERE imagecarousel_id = :imagecarousel_id AND co.is_use ='1' AND tem.is_use = '1' 
												   ORDER BY tem.update_date DESC");

				$fetctColumn->execute([
					':imagecarousel_id' => $rowImageCarousel["id_imagecarousel"]
				]);
				$column = array();
				while($rowColumn = $fetctColumn->fetch(PDO::FETCH_ASSOC)){
					$fetchActions = $conmysql->prepare("SELECT id_action,type,text,url,area_x,area_y,width,height,label,data,mode,initial,max,min 
														FROM lbaction 
														WHERE id_action = :action_id AND is_use = '1'");
					$fetchActions->execute([
						':action_id' => $rowColumn["action_id"]
					]);
					$actions = array();
					$type = null;
					$arrColumn = array();
					while($rowAction = $fetchActions->fetch(PDO::FETCH_ASSOC)){
						$arrColumn = array();
						$arrColumn["ACTION_ID"] = $rowAction["id_action"];
						$arrColumn["ID"] = $rowAction["id_action"];
						$arrColumn["TYPE"] = $rowAction["type"];
						$arrColumn["TEXT"] = $rowAction["text"];
						$arrColumn["URL"] = $rowAction["url"];
						$arrColumn["AREA_X"] = $rowAction["area_x"];
						$arrColumn["AREA_Y"] = $rowAction["area_y"];
						$arrColumn["WIDTH"] = $rowAction["width"];
						$arrColumn["HEIGHT"] = $rowAction["height"];
						$arrColumn["LABEL"] = $rowAction["label"];
						$arrColumn["DATA"] = $rowAction["data"];
						$arrColumn["MODE"] = $rowAction["mode"];
						$arrColumn["INITIAL"] = $rowAction["initial"];
						$arrColumn["MAX"] = $rowAction["max"];
						$arrColumn["MIN"] = $rowAction["min"];
						$quickmessagemap_id = $rowAction["quickmessagemap_id"];
					}
					$arrColumn["ID_COLUMNS"] =  $rowColumn["id_columns"];
					$arrColumn["IMAGE_URL"] =  $rowColumn["image_url"];
					$column[] = $arrColumn;
				}
				$arrMsg["ID_IMAGECAROUSEL"] = $rowImageCarousel["id_imagecarousel"];
				$arrMsg["TYPE_MESSAGE"] = $dataComing["res_type"];;
				$arrMsg["COLUMN"] = $column;
				$arrMsg["UPDATE_BY"] = $rowImageCarousel["update_by"];
				$arrMsg["UPDATE_DATE"] = $rowImageCarousel["update_date"];
				$arrayGroup[] = $arrMsg;
			}
		}
		$arrayResult["RES_DATA"] = $arrayGroup;
		$arrayResult["RES_DAdfdfdfTA"] = "dfdfdfdf";
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