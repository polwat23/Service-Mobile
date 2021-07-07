<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageaccbeenbind')){
		$arrayBindaccount = array();
		$fetchBindAcount = $conoracle->prepare("SELECT 
														gba.id_bindaccount,
														gba.member_no,
														gba.deptaccount_no_coop,
														gba.deptaccount_no_bank,
														gba.bank_account_name,
														gba.bank_account_name_en,
														gba.bind_date,
														cs.bank_name AS bank_name
													FROM gcbindaccount gba
												    LEFT JOIN csbankdisplay cs
												    ON cs.bank_code = gba.bank_code 
													WHERE gba.bindaccount_status = '1'");
		$fetchBindAcount->execute();
		$formatDept = $func->getConstant('dep_format');
		while($dataBindAcount = $fetchBindAcount->fetch(PDO::FETCH_ASSOC)){
			$bindaccount = array();
			$bindaccount["MEMBER_NO"] = $dataBindAcount["MEMBER_NO"];
			$bindaccount["DEPTACCOUNT_NO_COOP"] = $dataBindAcount["DEPTACCOUNT_NO_COOP"];
			$bindaccount["DEPTACCOUNT_NO_COOP_FORMAT"] = $lib->formataccount($dataBindAcount["DEPTACCOUNT_NO_COOP"],$formatDept);
			$bindaccount["ID_BINDACCOUNT"] = $dataBindAcount["ID_BINDACCOUNT"];
			$bindaccount["DEPTACCOUNT_NO_BANK"] = $dataBindAcount["DEPTACCOUNT_NO_BANK"];
			$bindaccount["DEPTACCOUNT_NO_BANK_FORMAT"] =$lib->formataccount($dataBindAcount["DEPTACCOUNT_NO_BANK"],$formatDept);
			$bindaccount["BANK_ACCOUNT_NAME"] = $dataBindAcount["BANK_ACCOUNT_NAME"];
			$bindaccount["BANK_ACCOUNT_NAME_EN"] = $dataBindAcount["BANK_ACCOUNT_NAME_EN"];
			$bindaccount["BANK_NAME"] = $dataBindAcount["BANK_NAME"];
			$bindaccount["BIND_DATE"] = $lib->convertdate($dataBindAcount["BIND_DATE"],'d m Y',true); 
			$arrayBindaccount[] = $bindaccount;
		}
		$arrayResult['BINACCOUNT_DATA'] = $arrayBindaccount;
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

