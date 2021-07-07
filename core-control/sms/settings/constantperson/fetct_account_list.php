<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		$arrayGroup = array();
		//fetch smsConstantDept
		$fetchConstant = $conoracle->prepare("SELECT id_smscsperson as id_constantperson,smscsp_member as member_no,smscsp_mindeposit as mindeposit,
												smscsp_minwithdraw as minwithdraw,is_use
												FROM smsconstantperson");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["ID_CONSTANTPERSON"];
			$arrConstans["CONSTANT_MEMBERNO"] = $rowMenuMobile["MEMBER_NO"];
			$arrConstans["MINDEPOSIT"] = $rowMenuMobile["MINDEPOSIT"];
			$arrConstans["MINWITHDRAW"] = $rowMenuMobile["MINWITHDRAW"];
			$arrConstans["IS_USE"] = $rowMenuMobile["IS_USE"];
			$arrayGroup[] = $arrConstans;
		}
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