<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$menuName = "manageuseraccount";
		$list_name = "reset password";

		$dateNow = date('Y-m-d H:i:s',strtotime($dateOperC));
		$fetchCitizenID = $conoracle->prepare("SELECT TRIM(card_person) as CARD_PERSON FROM mbmembmaster WHERE TRIM(member_no) = :member_no");
		$fetchCitizenID->execute([
			':member_no' => $dataComing["member_no"]
		]);
		$rowcitizenid = $fetchCitizenID->fetch(PDO::FETCH_ASSOC);
		$new_password = $rowcitizenid["CARD_PERSON"];
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET temppass_is_md5 = '0',prev_acc_status = account_status,temppass = :newpassword,account_status = '-9',counter_wrongpass = 0 WHERE member_no = :member_no");
		if($repassword->execute([
				':newpassword' => password_hash($new_password,PASSWORD_DEFAULT),
				':member_no' => $dataComing["member_no"]
		])){
			$arrayStruc = [
				':menu_name' => $menuName,
				':username' => $payload["username"],
				':use_list' => $list_name,
				':details' => $dataComing["member_no"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
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