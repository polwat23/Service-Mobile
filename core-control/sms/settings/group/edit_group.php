<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','group_name','group_member','id_group'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup')){
		$EditSmsGroup = $conmysql->prepare("UPDATE smsgroupmember SET group_name = :group_name,group_member = :group_member
												WHERE id_groupmember = :id_group");
		if($EditSmsGroup->execute([
			':group_name' => $dataComing["group_name"],
			':group_member'=> $dataComing["group_member"],
			':id_group' => $dataComing["id_group"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขกลุ่มได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>