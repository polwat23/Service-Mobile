<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["member_no"]) 
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "SQL409";
			$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}else{
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'LoanCredit')){
		if($dataComing["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_DEPOSIT"];
		}else if($dataComing["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_DEPOSIT"];
		}else{
			$member_no = $dataComing["member_no"];
		}
		$arrGroupCredit = array();
		$loantype_notshow = $func->getConstant('loantype_notshow',$conmysql);
		$fetchCredit = $conoracle->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,
											(sm.sharestk_amt*sh.unitshare_value*lc.percentshare ) + (NVL(mb.salary_amount,15000)*lc.percentsalary ) AS CREDIT_AMT
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
											shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
											WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
											AND LT.LOANTYPE_CODE NOT IN ( ".$loantype_notshow." )
											AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
											AND NVL(mb.salary_amount,15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
											AND Ft_Calage(mb.member_date,sysdate,8) BETWEEN lc.startmember_time AND lc.endmember_time");
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
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>