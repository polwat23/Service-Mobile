<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptmixmin',$conoracle)){
		$arrayGroup = array();
		$fetchDepttypeUsed = $conoracle->prepare("SELECT ID, TRIM(MENU_COMPONENT) as MENU_COMPONENT, AMOUNT, COUNT, TIMES, SIGN_FLAG
											FROM GCCONSTANTLIMITTX WHERE IS_USE = '1' ORDER BY ID ASC");
		$fetchDepttypeUsed->execute();
		while($rowDepttypeUse = $fetchDepttypeUsed->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
			$arrayDepttype["MENU_COMPONENT"] = $rowDepttypeUse["MENU_COMPONENT"];
			if($rowDepttypeUse["MENU_COMPONENT"] == 'TransferSelfDepInsideCoop'){
				$arrayDepttype["DESCRIPTION"] = 'ทำรายการโอนฝาก-ถอน ภายในบัญชีตนเอง';
			}else if($rowDepttypeUse["MENU_COMPONENT"] == 'TransferDepInsideCoop'){
				$arrayDepttype["DESCRIPTION"] = 'ทำรายการโอนฝาก-ถอน ภายในบัญชีคนอื่น';
			}else if($rowDepttypeUse["MENU_COMPONENT"] == 'TransactionWithdrawDeposit'){
				$arrayDepttype["DESCRIPTION"] = 'ถอนโอนบัญชีออมทรัพย์กับธนาคาร';
			}else if($rowDepttypeUse["MENU_COMPONENT"] == 'TransferDepBuyShare'){
				$arrayDepttype["DESCRIPTION"] = 'ถอนโอนเพื่อระดมหุ้น';
			}else if($rowDepttypeUse["MENU_COMPONENT"] == 'TransferDepPayLoan'){
				$arrayDepttype["DESCRIPTION"] = 'ถอนโอนเพื่อชำระหนี้';
			}
			$arrayDepttype["ID"] = $rowDepttypeUse["ID"];
			$arrayDepttype["AMOUNT"] = $rowDepttypeUse["AMOUNT"];
			$arrayDepttype["COUNT"] = $rowDepttypeUse["COUNT"];
			$arrayDepttype["TIMES"] = $rowDepttypeUse["TIMES"];
			$arrayDepttype["SIGN_FLAG"] = $rowDepttypeUse["SIGN_FLAG"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["DEPT_TYPE"] = $arrayGroup;
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