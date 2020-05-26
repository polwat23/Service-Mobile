<?php
require_once('../../../autoload.php');


if($lib->checkCompleteArgument(['unique_id','member_no','account_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$menuName = "manageuseraccount";
		$list_name = null;
		if($dataComing["account_status"]=='1'){
			$list_name = "lock account";
		}else{
			$list_name = "unlock account";
		}
		$updateStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = :account_status,counter_wrongpass = 0
									 WHERE member_no = :member_no");
		if($updateStatus->execute([
			':account_status' => $dataComing["account_status"],
			':member_no' => $dataComing["member_no"]
		])){
			$arrayStruc = [
				':menu_name' => $menuName,
				':username' => $payload["username"],
				':use_list' => $list_name,
				':details' => $dataComing[member_no]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			
			$arrayResult["test"] = $arrayStruc;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถล็อคหรือปลดล็อคบัญชีได้ กรุณาติดต่อผู้พัฒนา";
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