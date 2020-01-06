<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_token','member_no','pin'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin
										WHERE member_no = :member_no");
		if($repassword->execute([
				':pin' => $newpin,
				':member_no' => $member_no
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถรีเซ็ต PIN ได้ กรุณาติดต่อผู้พัฒนา";
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