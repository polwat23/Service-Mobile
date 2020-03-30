<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageaccbeenbind')){
		$bindaccount = array();
		$fetchidUserallowAcctrantion = $conmysql->prepare("SELECT id_userallowacctran,deptaccount_no,member_no,limit_transaction_amt,gcconstantaccountdept.dept_type_desc AS 'dept_type'
														   FROM gcuserallowacctransaction
														   INNER JOIN gcconstantaccountdept
														   ON gcconstantaccountdept.id_accountconstant = gcuserallowacctransaction.id_accountconstant");
		$fetchidUserallowAcctrantion->execute();
		while($rowBindAccount = $fetchidUserallowAcctrantion->fetch(PDO::FETCH_ASSOC)){
			$bindaccount["ID_USERALLOWACCTRAN"] = $rowBindAccount["id_userallowacctran"];
			$bindaccount["MEMBER_NO"] = $rowBindAccount["member_no"];
			$bindaccount["DEPTACCOUNT_NO_COOP"] = $rowBindAccount["deptaccount_no"];
			$bindaccount["DEPTACCOUNT_NO_COOP_FORMAT"] = $lib->formataccount($rowBindAccount["deptaccount_no"],$func->getConstant('dep_format'));
			$bindaccount["LIMIT_TRANSACTION_AMT_FORMAT"] = number_format($rowBindAccount["limit_transaction_amt"],2);
			$bindaccount["LIMIT_TRANSACTION_AMT"] = $rowBindAccount["limit_transaction_amt"];
			$bindaccount["DEPT_TYPE"] = $rowBindAccount["dept_type"];
			
			
			$fetchBindAcount = $conmysql->prepare("SELECT 
														id_bindaccount,
														member_no,
														deptaccount_no_coop,
														deptaccount_no_bank,
														mobile_no,
														bank_account_name,
														bank_account_name_en,
														consent_date,
														bind_date,
														unbind_date,
														bindaccount_status,
														csbankdisplay.bank_short_name AS 'bank_name'
													FROM gcbindaccount 
												    INNER JOIN csbankdisplay
												    ON csbankdisplay.bank_code = gcbindaccount.bank_code 
													WHERE deptaccount_no_coop='$rowBindAccount[deptaccount_no]'");
			$fetchBindAcount->execute();
			$dataBindAcount = $fetchBindAcount -> fetch(PDO::FETCH_ASSOC);
			$bindaccount["ID_BINDACCOUNT"] = $dataBindAcount["id_bindaccount"];
			$bindaccount["DEPTACCOUNT_NO_BANK"] = $dataBindAcount["deptaccount_no_bank"];
			$bindaccount["DEPTACCOUNT_NO_BANK_FORMAT"] =$lib->formataccount($dataBindAcount["deptaccount_no_bank"],$func->getConstant('dep_format'));
			$bindaccount["BANK_ACCOUNT_NAME"] = $dataBindAcount["bank_account_name"];
			$bindaccount["BANK_ACCOUNT_NAME_EN"] = $dataBindAcount["bank_account_name_en"];
			$bindaccount["BANK_NAME"] = $dataBindAcount["bank_name"];
			$bindaccount["BIND_DATE"] = $dataBindAcount["bind_date"]==null?"-":$lib->convertdate($dataBindAcount["bind_date"],'d m Y',true); 
			$bindaccount["CONSENT_DATE"] = $dataBindAcount["consent_date"]==null?"-":$lib->convertdate($dataBindAcount["consent_date"],'d m Y',true); 
			$bindaccount["UNBIN_DATE"] = $dataBindAcount["unbind_date"];
			$bindaccount["BINDACCOUNT_STATUS"] = $dataBindAcount["bindaccount_status"];
			$arrayBindaccount[] = $bindaccount;
		}
		$arrayResult['BINACCOUNT_DATA'] = $arrayBindaccount;
		$arrayResult['RESULT'] = TRUE;
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

