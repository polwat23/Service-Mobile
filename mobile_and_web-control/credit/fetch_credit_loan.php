<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_CREDIT"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_CREDIT"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupCredit = array();
		$loantype_notshow = $func->getConstant('loantype_notshow');
		$fetchCredit = $conoracle->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,
											(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(mb.salary_amount,15000)*lc.multiple_salary ) AS CREDIT_AMT
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
											shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
											WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
											AND LT.LOANTYPE_CODE NOT IN (".$loantype_notshow.")
											AND TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time AND lc.endmember_time
											AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
											AND NVL(mb.salary_amount,15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
											GROUP BY lt.loantype_desc,lc.maxloan_amt,(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(mb.salary_amount,15000)*lc.multiple_salary)");
		$fetchCredit->execute([':member_no' => $member_no]);
		while($rowCredit = $fetchCredit->fetch()){
			$arrCredit = array();
			if($rowCredit["CREDIT_AMT"] > $rowCredit["MAXLOAN_AMT"]){
				$loan_amt = $rowCredit["MAXLOAN_AMT"];
			}else{
				$loan_amt = $rowCredit["CREDIT_AMT"];
			}
			$arrCredit["LOAN_DESC"] = $rowCredit["LOANTYPE_DESC"];
			$arrCredit["LOAN_AMT"] = number_format($loan_amt,2);
			$arrCredit["MAXLOAN_AMT"] = number_format($rowCredit["MAXLOAN_AMT"],2);
			$arrGroupCredit[] = $arrCredit;
		}
		$arrayResult["CREDIT"] = $arrGroupCredit;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>