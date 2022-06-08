<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$fetchBranchCoop = $conmysql->prepare("SELECT
												branch_id,
												branch_name,
												create_date,
												update_date,
												update_by,
												create_by,
                                                is_use
											FROM
											webcoopbranch 
											WHERE
											is_use <> '-9'");
	$fetchBranchCoop->execute();
	while($rowDeptGroup = $fetchBranchCoop->fetch(PDO::FETCH_ASSOC)){
		$arrBranch["ID"] = $rowDeptGroup["branch_id"];
		$arrBranch["NAME"] = $rowDeptGroup["branch_name"];;
		$arrBranch["UPDATE_BY"] = $rowDeptGroup["update_by"];
		$arrBranch["CREATE_BY"] = $rowDeptGroup["create_by"];
		$arrBranch["IS_USE"] = $rowDeptGroup["is_use"];
		$arrBranch["CREATE_DATE"] = $lib->convertdate($rowDeptGroup["create_date"],'d m Y',true); 
		$arrBranch["UPDATE_DATE"] = $lib->convertdate($rowDeptGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrBranch;
	}
	$arrayResult["BRANCH_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>