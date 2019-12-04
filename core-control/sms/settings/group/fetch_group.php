<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup')){
		$arrGroupAll = array();
		if(isset($dataComing["id_group"])){
			$fetchGroup = $conmysql->prepare("SELECT id_groupmember,group_name,group_member FROM smsgroupmember
												WHERE is_use = '1' and id_groupmember = :id_group");
			$fetchGroup->execute([':id_group' => $dataComing["id_group"]]);
			$rowGroup = $fetchGroup->fetch();
			$arrGroupAll["ID_GROUP"] = $rowGroup["id_groupmember"];
			$arrGroupAll["GROUP_NAME"] = $rowGroup["group_name"];
			$arrGroupAll["GROUP_MEMBER"] = explode(',',$rowGroup["group_member"]);
		}else{
			$fetchGroup = $conmysql->prepare("SELECT id_groupmember,group_name,group_member FROM smsgroupmember
												WHERE is_use = '1'");
			$fetchGroup->execute();
			while($rowGroup = $fetchGroup->fetch()){
				$arrGroup = array();
				$arrGroup["ID_GROUP"] = $rowGroup["id_groupmember"];
				$arrGroup["GROUP_NAME"] = $rowGroup["group_name"];
				$arrGroup["GROUP_MEMBER"] = explode(',',$rowGroup["group_member"]);
				$arrGroupAll[] = $arrGroup;
			}
		}
		$arrayResult['GROUP'] = $arrGroupAll;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>