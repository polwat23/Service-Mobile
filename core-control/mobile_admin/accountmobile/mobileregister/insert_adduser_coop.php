<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no', 'ref_memno' ,'password' ,'acc_name' ,'acc_surname'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		
		
		$checkUser = $conmysql->prepare("SELECT * FROM gcmemberaccount where member_no = :member_no");
		$checkUser->execute([
		 ':member_no' => $dataComing["member_no"],
		]);
		if($checkUser->rowCount() > 0){
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งาน เนื่องจากผู้ใช้งานนี้ถูกใช้งานเเล้ว";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		else
		{
			$insertUserCoop = $conmysql->prepare ("INSERT INTO `gcmemberaccount`(`member_no`, `ref_memno`, `password`, `acc_name`, `acc_surname`, `phone_number`, `email`)  
							VALUES (:member_no ,:ref_memno ,:password ,:acc_name , :acc_surname, :phone_number, :email)");
			if($insertUserCoop->execute([
				':member_no' => $dataComing["member_no"],
				':ref_memno' => $dataComing["ref_memno"],
				':password' => password_hash($dataComing["password"],PASSWORD_DEFAULT), 
				':acc_name' => $dataComing["acc_name"],
				':acc_surname'=> $dataComing["acc_surname"],
				':phone_number'=> $dataComing["phone_number"],
				':email'=> $dataComing["email"],
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งาน";
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