<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extramonthlypaymentmember')){
		$arrayGroup = array();
		$arrayAllGroup = array();
		
		$fetchAllGroup = $conoracle->prepare("SELECT TRIM(MG.MEMBGROUP_CODE) AS MEMBGROUP_CODE, MG.MEMBGROUP_CONTROL,MG.MEMBGROUP_DESC
											FROM MBUCFMEMBGROUP MG");
		$fetchAllGroup->execute();
		while($rowAllGroup = $fetchAllGroup->fetch(PDO::FETCH_ASSOC)){
			$arrayAllGroup[$rowAllGroup["MEMBGROUP_CODE"]]["MEMBGROUP_CODE"] = $rowAllGroup["MEMBGROUP_CODE"];
			$arrayAllGroup[$rowAllGroup["MEMBGROUP_CODE"]]["MEMBGROUP_DESC"] = $rowAllGroup["MEMBGROUP_DESC"];
		}
		
		$fetchUserGroup = $conmysql->prepare("SELECT id_extrapayment, membgroup_code, is_use FROM gcextrapaymentmembergroup WHERE is_use = '1'");
		$fetchUserGroup->execute();
		while($rowUserGroup = $fetchUserGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EXTRAPAYMENT"] = $rowUserGroup["id_extrapayment"];
			$arrGroupUserAcount["MEMBGROUP_CODE"] = $rowUserGroup["membgroup_code"];
			$arrGroupUserAcount["MEMBGROUP_DESC"] = $arrayAllGroup[$rowUserGroup["membgroup_code"]]["MEMBGROUP_DESC"];
			$arrGroupUserAcount["IS_USE"] = $rowUserGroup["is_use"] == '1';
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["ALLOW_MEMBGROUP"] = $arrayGroup;
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