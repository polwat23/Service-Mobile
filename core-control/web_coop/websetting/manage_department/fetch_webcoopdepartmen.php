<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$fetchbalanceSheetGroup = $conmysql->prepare("SELECT
												dept_id,
												dept_name,
												create_date,
												update_date,
												update_by,
												create_by,
                                                is_use
											FROM
											webcoopdepartment 
											WHERE
											is_use <> '-9' AND type = :type");
	$fetchbalanceSheetGroup->execute([':type' => $dataComing["type"]]);

	
	while($rowDeptGroup = $fetchbalanceSheetGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGroupDept["ID"] = $rowDeptGroup["dept_id"];
		$arrGroupDept["NAME"] = $rowDeptGroup["dept_name"];;
		$arrGroupDept["UPDATE_BY"] = $rowDeptGroup["update_by"];
		$arrGroupDept["CREATE_BY"] = $rowDeptGroup["create_by"];
		$arrGroupDept["IS_USE"] = $rowDeptGroup["is_use"];
		$arrGroupDept["CREATE_DATE"] = $lib->convertdate($rowDeptGroup["create_date"],'d m Y',true); 
		$arrGroupDept["UPDATE_DATE"] = $lib->convertdate($rowDeptGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGroupDept;
	}
	$arrayResult["WEBCOOPDEPARTTMENT_DATA"] = $arrayGroup;
	
	
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>