<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($dataComing["unique_id"]) && isset($payload["member_no"]) 
	&& isset($payload["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
		$is_accessToken = $api->check_accesstoken($access_token,$conmysql);
		$new_token = null;
		if(!$is_accessToken){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib,$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
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
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'LoanInfo')){
			if($payload["member_no"] == 'dev@mode'){
				$member_no = $config["MEMBER_NO_DEV_LOAN"];
			}else if($payload["member_no"] == 'salemode'){
				$member_no = $config["MEMBER_NO_SALE_LOAN"];
			}else{
				$member_no = $payload["member_no"];
			}
			$arrAllLoan = array();
			$getSumAllContract = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lncontmaster WHERE member_no = :member_no");
			$getSumAllContract->execute([':member_no' => $member_no]);
			$rowSumloanbalance = $getSumAllContract->fetch();
			$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
			$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
												ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment,period_payamt as PERIOD,
												LAST_PERIODPAY as LAST_PERIOD,
												(SELECT max(entry_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
												FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
												WHERE ln.member_no = :member_no and ln.contract_status = 1");
			$getContract->execute([':member_no' => $member_no]);
			while($rowContract = $getContract->fetch()){
				$arrGroupContract = array();
				$arrContract = array();
				$arrContract["CONTRACT_NO"] = $lib->formatcontract($rowContract["LOANCONTRACT_NO"],$func->getConstant('loan_format',$conmysql));
				$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
				$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
				$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
				$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
				$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
				$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
				$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
				$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
				if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
					($arrGroupContract['CONTRACT'])[] = $arrContract;
					$arrAllLoan[] = $arrGroupContract;
				}else{
					($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
				}
			}
			$arrayResult['DETAIL_LOAN'] = $arrAllLoan;
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
}else{
	$arrayResult['RESPONSE_CODE'] = "HEADER500";
	$arrayResult['RESPONSE'] = "Authorization token invalid";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>