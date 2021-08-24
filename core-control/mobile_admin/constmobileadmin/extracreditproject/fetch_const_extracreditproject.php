<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extracreditproject')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_extra_credit, extra_credit_name, extra_credit_desc,extra_credit_html, loantype_code FROM gcconstantextracreditproject WHERE is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_EXTRA_CREDIT"] = $rowMenuMobile["id_extra_credit"];
			$arrConstans["EXTRA_CREDIT_NAME"] = $rowMenuMobile["extra_credit_name"];
			$arrConstans["EXTRA_CREDIT_DESC"] = $rowMenuMobile["extra_credit_desc"];
			$arrConstans["EXTRA_CREDIT_HTML"] = $rowMenuMobile["extra_credit_html"];
			$arrConstans["LOANTYPE_CODE"] = $rowMenuMobile["loantype_code"];
			$arrayGroup[] = $arrConstans;
		}
		
		$arrLoanType = array();
		$fetchLoanType = $conmssql->prepare("SELECT lnt.LOANTYPE_DESC,lnt.LOANTYPE_CODE,lnd.INTEREST_RATE,lnt.LOANGROUP_CODE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
																	ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
																	WHERE getdate() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
		$fetchLoanType->execute();
		while($rowLoanType = $fetchLoanType->fetch(PDO::FETCH_ASSOC)){
			$arrData = array();
			$arrData["LOANTYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
			$arrData["LOANTYPE_CODE"] = $rowLoanType["LOANTYPE_CODE"];
			$arrData["INTEREST_RATE"] = number_format($rowLoanType["INTEREST_RATE"],2);
			$arrData["LOANGROUP_CODE"] = $rowLoanType["LOANGROUP_CODE"];
			$arrLoanType[] = $arrData;
		}
		
		$arrayResult["LOAN_TYPE"] = $arrLoanType;
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