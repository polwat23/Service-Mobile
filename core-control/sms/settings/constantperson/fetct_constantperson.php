<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		$arrayGroup = array();
		//fetch smsConstantDept
		$smsConstantMinDept = 0;
		$fetchSMSConstantDept = $conmysql->prepare("SELECT smscs_value as constant_value
												FROM smsconstantsystem WHERE is_use = '1' AND smscs_name = 'limit_dept_send_free'");
		$fetchSMSConstantDept->execute();
		while($rowSMSConstantDept = $fetchSMSConstantDept->fetch(PDO::FETCH_ASSOC)){
			$smsConstantMinDept = $rowSMSConstantDept["constant_value"];
		}
		
		//fetch smsConstantWithdraw
		$smsConstantMinWithdraw = 0;
		$fetchSMSConstantWithdraw = $conmysql->prepare("SELECT smscs_value as constant_value
												FROM smsconstantsystem WHERE is_use = '1' AND smscs_name = 'limit_withdraw_send_free'");
		$fetchSMSConstantWithdraw->execute();
		while($rowSMSConstantWithdraw = $fetchSMSConstantWithdraw->fetch(PDO::FETCH_ASSOC)){
			$smsConstantMinWithdraw = $rowSMSConstantWithdraw["constant_value"];
		}
		
		$fetchConstant = $conmysql->prepare('SELECT id_smscsperson as id_constantperson,smscsp_account as account,smscsp_mindeposit as mindeposit,
												smscsp_minwithdraw as minwithdraw,is_use,is_mindeposit, is_minwithdraw
												FROM smsconstantperson WHERE smscsp_account in ('.implode(',',$dataComing["acc_list"]).')');
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["id_constantperson"];
			$arrConstans["DEPTACCOUNT_NO"] = $rowMenuMobile["account"];
			$arrConstans["MINDEPOSIT"] = $rowMenuMobile["mindeposit"];
			$arrConstans["MINWITHDRAW"] = $rowMenuMobile["minwithdraw"];
			$arrConstans["IS_USE"] = $rowMenuMobile["is_use"];
			$arrConstans["IS_MINDEPOSIT"] = $rowMenuMobile["is_mindeposit"];
			$arrConstans["IS_MINWITHDRAW"] = $rowMenuMobile["is_minwithdraw"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_MIN_DEPT"] = $smsConstantMinDept;
		$arrayResult["CONSTANT_MIN_WITHDRAW"] = $smsConstantMinWithdraw;
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
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