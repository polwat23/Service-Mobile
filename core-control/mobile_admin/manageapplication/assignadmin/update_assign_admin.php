<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no_group'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$conmysql->beginTransaction();
		foreach($dataComing["member_no_group"] as $member_grp){
			$updatemenu = $conmysql->prepare("UPDATE gcmemberaccount SET user_type = :member_type
										 WHERE member_no = :member_no");
			if($updatemenu->execute([
				':member_type' => $member_grp["member_type"],
				':member_no' => $member_grp["member_no"]
			])){
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถตั้งแอดมินได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		$conmysql->commit();
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
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
