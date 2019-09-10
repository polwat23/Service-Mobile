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
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'DepositInfo')){
		if($dataComing["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_DEPOSIT"];
		}else if($dataComing["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_DEPOSIT"];
		}else{
			$member_no = $dataComing["member_no"];
		}
		$arrAllAccount = array();
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no");
		$getSumAllAccount->execute([':member_no' => $member_no]);
		$rowSumbalance = $getSumAllAccount->fetch();
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(entry_date) FROM dpdeptstatement WHERE deptaccount_no = dp.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1");
		$getAccount->execute([':member_no' => $member_no]);
		while($rowAccount = $getAccount->fetch()){
			$arrAccount = array();
			$arrGroupAccount = array();
			$arrAccount["DEPTACCOUNT_NO"] = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format',$conmysql));
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
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
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