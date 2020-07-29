<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		
		$fetchMember = $conmysql->prepare("SELECT member_no FROM gcmemberaccount 
												WHERE member_no = :member_no");
		$fetchMember->execute([
				':member_no' => strtolower($lib->mb_str_pad($dataComing["member_no"]))
		]);
		if($fetchMember->rowCount() > 0){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult["RESPONSE"] = "ไม่พบเลขสมาชิกที่ท่านต้องการ";
			$arrayResult["RESULT"] = FALSE;
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