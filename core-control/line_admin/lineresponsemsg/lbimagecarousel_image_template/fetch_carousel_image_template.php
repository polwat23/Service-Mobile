<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineimagecarouseltemplatemsg')){
		$arrayGroup = array();
		$fetctImageCarousel = $conmysql->prepare("SELECT id_imagecarousel,update_date,update_by FROM lbimagecarouseltemplate WHERE is_use ='1' ORDER BY update_date DESC");
		$fetctImageCarousel->execute();
		while($rowImageCarousel = $fetctImageCarousel->fetch(PDO::FETCH_ASSOC)){
			$arrImageCarousel = array();
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
					$select_action_data = [];
					if($rowAction["type"]=='message'){
						$select_action_data = ['inputActionLabel', 'inputActionData', 'inputActionDisplayText', 'inputActionText'];
					}else if($rowAction["type"]=="uri"){
						$select_action_data = ['inputActionLabel', 'inputActionUri', 'inputActionAltUri.Desktop'];
					}else if($rowAction["type"]=="postback"){
						$select_action_data =  ['inputActionLabel', 'inputActionData', 'inputActionDisplayText', 'inputActionText'];
					}else if($rowAction["type"]=="datetime_picker"){
						$select_action_data = ['inputActionLabel', 'inputActionData', 'inputActionMode', 'inputActionInitial', 'inputActionMax', 'inputActionMin'];
					}
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
					$arrColumn["SELECT_ACTION_DATA"] = $select_action_data;
					$quickmessagemap_id = $rowAction["quickmessagemap_id"];
				}
				$arrColumn["ID_COLUMNS"] =  $rowColumn["id_columns"];
				$arrColumn["IMAGE_URL"] =  $rowColumn["image_url"];
				
				$column[] = $arrColumn;
			}
			$arrImageCarousel["ID_IMAGECAROUSEL"] = $rowImageCarousel["id_imagecarousel"];
			$arrImageCarousel["COLUMN"] = $column;
			$arrImageCarousel["UPDATE_BY"] = $rowImageCarousel["update_by"];
			$arrImageCarousel["UPDATE_DATE"] = $rowImageCarousel["update_date"];
			$arrayGroup[] = $arrImageCarousel;
		}
		$arrayResult["IMGCARSELTEMPLATE_DATA"] = $arrayGroup;
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