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
		
		$fetchConstant = $conmysql->prepare("SELECT id_smscsperson as id_constantperson,smscsp_member as member_no,smscsp_mindeposit as mindeposit,
												smscsp_minwithdraw as minwithdraw,is_use
												FROM smsconstantperson");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["id_constantperson"];
			$arrConstans["CONSTANT_MEMBERNO"] = $rowMenuMobile["member_no"];
			$arrConstans["MINDEPOSIT"] = $rowMenuMobile["mindeposit"];
			$arrConstans["MINWITHDRAW"] = $rowMenuMobile["minwithdraw"];
			$arrConstans["IS_USE"] = $rowMenuMobile["is_use"];
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