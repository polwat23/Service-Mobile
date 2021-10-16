<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup',$conoracle)){
		$arrGroupAll = array();
		if(isset($dataComing["id_group"])){
			$fetchGroup = $conoracle->prepare("SELECT id_groupmember,group_name,group_member FROM smsgroupmember
												WHERE is_use = '1' and id_groupmember = :id_group");
			$fetchGroup->execute([':id_group' => $dataComing["id_group"]]);
			$rowGroup = $fetchGroup->fetch(PDO::FETCH_ASSOC);
			$arrGroupAll["ID_GROUP"] = $rowGroup["ID_GROUPMEMBER"];
			$arrGroupAll["GROUP_NAME"] = $rowGroup["GROUP_NAME"];
			$arrGroupAll["GROUP_MEMBER"] = explode(',',$rowGroup["GROUP_MEMBER"]);
		}else{
			$fetchGroup = $conoracle->prepare("SELECT id_groupmember,group_name,group_member FROM smsgroupmember
												WHERE is_use = '1'");
			$fetchGroup->execute();
			while($rowGroup = $fetchGroup->fetch(PDO::FETCH_ASSOC)){
				$arrGroup = array();
				$arrGroup["ID_GROUP"] = $rowGroup["ID_GROUPMEMBER"];
				$arrGroup["GROUP_NAME"] = $rowGroup["GROUP_NAME"];
				$arrGroup["GROUP_MEMBER"] = explode(',',$rowGroup["GROUP_MEMBER"]);
				$arrGroupAll[] = $arrGroup;
			}
		}
		$arrayResult['GROUP'] = $arrGroupAll;
		$arrayResult['RESULT'] = TRUE;
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