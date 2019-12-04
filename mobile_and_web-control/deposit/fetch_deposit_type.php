<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_DEPOSIT"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_DEPOSIT"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrAllAccount = array();
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no");
		$getSumAllAccount->execute([':member_no' => $member_no]);
		$rowSumbalance = $getSumAllAccount->fetch();
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = dp.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code and dp.membcat_code = dt.membcat_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1");
		$getAccount->execute([':member_no' => $member_no]);
		while($rowAccount = $getAccount->fetch()){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccount["DEPTACCOUNT_NAME"]);
			$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
			$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
			$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		if(sizeof($arrAllAccount) > 0 || isset($new_token)){
			$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>