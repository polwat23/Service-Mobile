<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$fetchCitizenID = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
		$fetchCitizenID->execute([
			':member_no' => $dataComing["member_no"]
		]);
		$rowcitizenid = $fetchCitizenID->fetch();
		$new_password = password_hash($rowcitizenid["CARD_PERSON"], PASSWORD_DEFAULT);
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET temppass = :newpassword,account_status = '-9'
										WHERE member_no = :member_no");
		if($repassword->execute([
				':newpassword' => $new_password,
				':member_no' => $dataComing["member_no"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถรีเซ็ตรหัสผ่านได้ กรุณาติดต่อผู้พัฒนา";
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