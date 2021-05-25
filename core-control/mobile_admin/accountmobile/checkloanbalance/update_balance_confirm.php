<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','status_lock'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		
		$member_no = null;
		$checkMembno = $conmysql->prepare("SELECT member_no FROM gcconstantbalanceconfirm WHERE  member_no = :member_no");
		$checkMembno->execute([':member_no' => $dataComing["member_no"]]);
		$rowUser = $checkMembno->fetch(PDO::FETCH_ASSOC);
		$member_no  = $rowUser["member_no"];
		
		if(isset($member_no)){
			$update_phone = $conmysql->prepare("UPDATE gcconstantbalanceconfirm SET balance_status = :status_lock WHERE  member_no = :member_no");
			if($update_phone->execute([
				':status_lock' => $dataComing["status_lock"],
				':member_no' => $dataComing["member_no"]			
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ โปรดติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$insertBalance = $conmysql->prepare ("INSERT INTO gcconstantbalanceconfirm(member_no, balance_status)  VALUES (:member_no ,:status_lock)");
			if($insertBalance->execute([
				':member_no' => $dataComing["member_no"],
				':status_lock' => $dataComing["status_lock"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ โปรดติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}	
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