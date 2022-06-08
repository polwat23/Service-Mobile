<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	
	$fetchmenuknowledgebaseGroup = $conmysql->prepare("SELECT
												icon,
												id_coremenu,
												coremenu_name,
												is_use,
												create_by,
												create_date,
												update_date,
												update_by
											FROM
												webcoopcoremenuknowledgebase
											WHERE is_use <> '-9'
											");
	$fetchmenuknowledgebaseGroup->execute();
	
	while($rowmenuknowledgebaseGroup = $fetchmenuknowledgebaseGroup->fetch(PDO::FETCH_ASSOC)){
		$icon = array();
		$arrNewsFile["url"] = $rowmenuknowledgebaseGroup["icon"];;
		$arrNewsFile["status"] = "old";
		$icon[] = $arrNewsFile;
		
		$arrGroupStatement["ID_COREMENU"] = $rowmenuknowledgebaseGroup["id_coremenu"];
		$arrGroupStatement["ICON"] = $rowmenuknowledgebaseGroup["icon"];
		$arrGroupStatement["COREMENU_MAME"] = $rowmenuknowledgebaseGroup["coremenu_name"];
        $arrGroupStatement["IS_USE"] = $rowmenuknowledgebaseGroup["is_use"];
		$arrGroupStatement["UPDATE_BY"] = $rowmenuknowledgebaseGroup["update_by"];
		$arrGroupStatement["CREATE_BY"] = $rowmenuknowledgebaseGroup["create_by"];
		$arrGroupStatement["CREATE_DATE"] = $lib->convertdate($rowmenuknowledgebaseGroup["create_date"],'d m Y',true); 
		$arrGroupStatement["UPDATE_DATE"] = $lib->convertdate($rowmenuknowledgebaseGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGroupStatement;
	}
	$arrayResult["MENU_KNOWLEDGE_BASE_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>