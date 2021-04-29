<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extramonthlypaymentmembers')){
		$arrayGroup = array();
		$fetchUserGroup = $conoracle->prepare("SELECT TRIM(MG.MEMBGROUP_CODE) AS MEMBGROUP_CODE, MG.MEMBGROUP_CONTROL,MG.MEMBGROUP_DESC,MGC.MEMBGROUP_CONTROLDESC 
											FROM MBUCFMEMBGROUP MG JOIN MBUCFMEMBGROUPCONTROL MGC ON MG.MEMBGROUP_CONTROL = MGC.MEMBGROUP_CONTROL 
											ORDER BY MG.MEMBGROUP_CONTROL,MG.MEMBGROUP_CODE");
		$fetchUserGroup->execute();
		while($rowUserGroup = $fetchUserGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrayGroup[$rowUserGroup["MEMBGROUP_CONTROL"]]["MEMBGROUP_CONTROL"] = $rowUserGroup["MEMBGROUP_CONTROL"];
			$arrayGroup[$rowUserGroup["MEMBGROUP_CONTROL"]]["MEMBGROUP_DESC"] = $rowUserGroup["MEMBGROUP_DESC"];
			
			$arrGroupUserAcount["MEMBGROUP_CODE"] = $rowUserGroup["MEMBGROUP_CODE"];
			$arrGroupUserAcount["MEMBGROUP_DESC"] = $rowUserGroup["MEMBGROUP_CODE"].' - '.$rowUserGroup["MEMBGROUP_DESC"];
			$arrGroupUserAcount["MEMBGROUP_CONTROL"] = $rowUserGroup["MEMBGROUP_CONTROL"];
			
			$arrayGroup[$rowUserGroup["MEMBGROUP_CONTROL"]]["GROUP_LIST"][] = $arrGroupUserAcount;
		}
		$arrayResult["MEMBGROUP_LIST"] = $arrayGroup;
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