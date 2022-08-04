<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','deptaccount_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','deptaccountjoint')){
		$arrayGroupAll = array();

		$fetchDeptInfo = $conmssql->prepare("SELECT dp.ACC_TYPE as depttype_code,dt.ACC_DESC as depttype_desc,dp.account_no as deptaccount_no,dp.account_id as MEMBER_NO,
											dp.account_name as deptaccount_name,dp.BALANCE as BALANCE,
											dp.LAST_DATE as LAST_OPERATE_DATE
											FROM BK_H_SAVINGACCOUNT dp LEFT JOIN BK_M_ACC_TYPE dt ON dp.ACC_TYPE = dt.ACC_TYPE
											WHERE dp.account_no = :deptaccount_no");
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