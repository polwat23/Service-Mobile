<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrResign = array();
		$insertCount = 0;
		
		//alluser
		$fetchUserNotRegis = $conoracle->prepare("SELECT memb.member_no FROM mbmembmaster memb
		JOIN shsharemaster shr ON memb.member_no = shr.member_no WHERE shr.sharestk_amt <=  0 OR memb.resign_status = '1'");
		$fetchUserNotRegis->execute();
		
		while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
			$arrResign[] = $rowUserNotRegis["MEMBER_NO"];
			if(count($arrResign) == 1000){
				$updateUserStatus = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status, account_status = '-6' WHERE member_no in ('".implode("','",$arrResign)."') and account_status IN('1','-8','-9')");
				if($updateUserStatus->execute()){
					$arrResign = array();
					$insertCount++;
				}else{
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['RESPONSE'] = "ประมวลผลไม่สมบูรณ์ กรุณาลองใหม่อีกครั้งหรือติดต่อผู้พัฒนา";
					require_once('../../../include/exit_footer.php');
				}
			}
		}
		
		
		if(count($arrResign) > 0){
			$updateUserStatus = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status, account_status = '-6' WHERE member_no in ('".implode("','",$arrResign)."') and account_status IN('1','-8','-9')");
			if($updateUserStatus->execute()){
				
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ประมวลผลไม่สมบูรณ์ กรุณาลองใหม่อีกครั้งหรือติดต่อผู้พัฒนา";
				require_once('../../../include/exit_footer.php');
			}
		}

		$arrayResult["UPDATE_COUNT"] = ($insertCount * 1000) + count($arrResign);
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>