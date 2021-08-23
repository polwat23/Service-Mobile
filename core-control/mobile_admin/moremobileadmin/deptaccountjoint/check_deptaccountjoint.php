<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','deptaccount_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','deptaccountjoint')){
		$arrayGroupAll = array();

		$fetchDeptInfo = $conmssql->prepare("SELECT DEPTACCOUNT_NO, MEMBER_NO, DEPTACCOUNT_NAME FROM DPDEPTMASTER WHERE DEPTACCOUNT_NO = :deptaccount_no");
		$fetchDeptInfo->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"]
		]);
		$rowDeptInfo = $fetchDeptInfo->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDeptInfo["DEPTACCOUNT_NO"])){
			$fetchDeptJoint = $conmysql->prepare("SELECT deptaccount_no, update_date FROM gcdeptaccountjoint WHERE is_joint = '1' AND deptaccount_no = :deptaccount_no");
			$fetchDeptJoint->execute([
				':deptaccount_no' => $dataComing["deptaccount_no"]
			]);
			$rowDeptJoint = $fetchDeptJoint->fetch(PDO::FETCH_ASSOC);
			$arrayGroupAll["DEPTACCOUNT_NO"] = $rowDeptInfo["DEPTACCOUNT_NO"];
			$arrayGroupAll["MEMBER_NO"] = $rowDeptInfo["MEMBER_NO"];
			$arrayGroupAll["DEPTACCOUNT_NAME"] = $rowDeptInfo["DEPTACCOUNT_NAME"];
			$arrayGroupAll["IS_JOINT"] = isset($rowDeptJoint["deptaccount_no"]) ? TRUE : FALSE;
			
			$arrayResult["DEPTACCOUNT"] = $arrayGroupAll;
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลบัญชี กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
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