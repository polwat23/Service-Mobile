<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		$arrayGroup = array();
		//fetch smsConstantDept
		$smsConstantMinDept = 500;
		//fetch smsConstantWithdraw
		$smsConstantMinWithdraw = 500;
		
		$fetchConstant = $conoracle->prepare('SELECT id_smscsperson as id_constantperson,smscsp_account as account,smscsp_mindeposit as mindeposit,
												smscsp_minwithdraw as minwithdraw,is_use,is_mindeposit, is_minwithdraw,smscsp_pay_type as pay_type
												FROM smsconstantperson WHERE smscsp_account in ('.implode(',',$dataComing["acc_list"]).') ORDER BY smscsp_account ASC');
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["ID_CONSTANTPERSON"];
			$arrConstans["DEPTACCOUNT_NO"] = $rowMenuMobile["ACCOUNT"];
			$arrConstans["MINDEPOSIT"] = $rowMenuMobile["MINDEPOSIT"];
			$arrConstans["MINWITHDRAW"] = $rowMenuMobile["MINWITHDRAW"];
			$arrConstans["IS_USE"] = $rowMenuMobile["IS_USE"];
			$arrConstans["PAY_TYPE"] = $rowMenuMobile["PAY_TYPE"];
			$arrConstans["IS_MINDEPOSIT"] = $rowMenuMobile["IS_MINDEPOSIT"];
			$arrConstans["IS_MINWITHDRAW"] = $rowMenuMobile["IS_MINWITHDRAW"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_MIN_DEPT"] = $smsConstantMinDept;
		$arrayResult["CONSTANT_MIN_WITHDRAW"] = $smsConstantMinWithdraw;
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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