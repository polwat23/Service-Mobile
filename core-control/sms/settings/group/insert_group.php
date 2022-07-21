<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','group_name','group_member'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup',$conoracle)){
		$id_groupmember  = $func->getMaxTable('id_groupmember' , 'smsgroupmember',$conoracle);	
		$insertSmsGroup = $conoracle->prepare("INSERT INTO smsgroupmember(id_groupmember,group_name,group_member)
												VALUES(:id_groupmember,:group_name,:group_member)");
		if($insertSmsGroup->execute([
			':id_groupmember' => $id_groupmember,
			':group_name' => $dataComing["group_name"],
			':group_member'=> $dataComing["group_member"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มกลุ่มได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
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