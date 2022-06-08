<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$arrGropLevel = array();
	$fetchbalanceSheetGroup = $conmysql->prepare("SELECT
												id_post,
												post_name,
												create_date,
												update_date,
												update_by,
												create_by,
                                                is_use,
												level
										
											FROM
												webcooposition
											WHERE
												is_use <> '-9' and type = :type
											ORDER BY level");
	$fetchbalanceSheetGroup->execute([':type' => $dataComing["type"]]);
	while($rowDeptGroup = $fetchbalanceSheetGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGrouppossition["ID"] = $rowDeptGroup["id_post"];
		$arrGrouppossition["NAME"] = $rowDeptGroup["post_name"];;
		$arrGrouppossition["UPDATE_BY"] = $rowDeptGroup["update_by"];
		$arrGrouppossition["CREATE_BY"] = $rowDeptGroup["create_by"];
		$arrGrouppossition["IS_USE"] = $rowDeptGroup["is_use"];
		$arrGrouppossition["LEVEL"] = $rowDeptGroup["level"];
		$arrGrouppossition["CREATE_DATE"] = $lib->convertdate($rowDeptGroup["create_date"],'d m Y',true); 
		$arrGrouppossition["UPDATE_DATE"] = $lib->convertdate($rowDeptGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGrouppossition;
	}
	$fetchLevel = $conmysql->prepare("SELECT DISTINCT level
									  FROM
									    webcooposition
									  WHERE type = :type AND is_use <> '-9'");
	$fetchLevel->execute([':type' => $dataComing["type"]]);
	while($rowLevelGroup = $fetchLevel->fetch(PDO::FETCH_ASSOC)){
		$arrLevel = $rowLevelGroup["level"];
		$arrGropLevel[] = $arrLevel;
	}
	$arrayResult["WEBCOOPOSITION_DATA"] = $arrayGroup;
	$arrayResult["LEVEL"] = $arrGropLevel;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>