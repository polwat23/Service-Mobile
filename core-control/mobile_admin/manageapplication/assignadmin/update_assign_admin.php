<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no_group'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$conmysql->beginTransaction();
		foreach($dataComing["member_no_group"] as $member_grp){
			$checkMemberStatus = $conmysql->prepare("SELECT COUNT(member_no) as COUNT_MEMBER FROM gcmemberaccount
										 WHERE member_no = :member_no");
			$checkMemberStatus->execute([
				':member_no' => $member_grp["member_no"]
			]);
			
			$rowMember = $checkMemberStatus->fetch(PDO::FETCH_ASSOC);
			
			if($rowMember["COUNT_MEMBER"] > 0){
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
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "เลขทะเบียนสมาชิกนี้ยังไม่ได้สมัครใช้งานแอพพลิเคชั่น";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
		}
		$conmysql->commit();
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
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
