<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','deptaccount_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','deptaccountjoint')){

		$fetchDeptJoint = $conmysql->prepare("SELECT deptaccount_no, update_date FROM gcdeptaccountjoint WHERE deptaccount_no = :deptaccount_no");
		$fetchDeptJoint->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"]
		]);
		$rowDeptJoint = $fetchDeptJoint->fetch(PDO::FETCH_ASSOC);

		if(isset($rowDeptJoint["deptaccount_no"])){
			$insertIntoInfo = $conmysql->prepare("UPDATE gcdeptaccountjoint SET is_joint='1' WHERE deptaccount_no = :deptaccount_no");
			if($insertIntoInfo->execute([
				':deptaccount_no' => $dataComing["deptaccount_no"],
			])){
				$arrayStruc = [
					':menu_name' => 'deptaccountjoint',
					':username' => $payload["username"],
					':use_list' => 'insert deptaccountjoint',
					':details' => "Update ".$dataComing["deptaccount_no"]." 0 => 1"
				];
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มบัญชีร่วมไม่สำเร็จ กรุณาลองใหม่อีกครั้งในภายหลัง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$insertIntoInfo = $conmysql->prepare("INSERT INTO gcdeptaccountjoint(deptaccount_no) VALUES (:deptaccount_no)");
			if($insertIntoInfo->execute([
				':deptaccount_no' => $dataComing["deptaccount_no"],
			])){
				$arrayStruc = [
					':menu_name' => 'deptaccountjoint',
					':username' => $payload["username"],
					':use_list' => 'insert deptaccountjoint',
					':details' => "Insert ".$dataComing["deptaccount_no"]
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มบัญชีร่วมไม่สำเร็จ กรุณาลองใหม่อีกครั้งในภายหลัง";
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