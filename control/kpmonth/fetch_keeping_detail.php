<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["member_no"]) && isset($dataComing["recv_period"]) 
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
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'PaymentMonthlyDetail')){
		if($dataComing["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_KEEPINGMONTH"];
		}else if($dataComing["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_KEEPINGMONTH"];
		}else{
			$member_no = $dataComing["member_no"];
		}
		$arrGroupDetail = array();
		$getDetailKP = $conoracle->prepare("SELECT * FROM (
												SELECT 
												kut.keepitemtype_desc as TYPE_DESC,
												kut.keepitemtype_grp as TYPE_GROUP,
												case kut.keepitemtype_grp WHEN 'DEP' THEN 
													kpd.description
												WHEN 'LON' THEN 
													kpd.loancontract_no
												ELSE null END as PAY_ACCOUNT,
												kpd.period,
												NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
												NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
												NVL(kpd.principal_payment,0) AS PRN_BALANCE,
												NVL(kpd.interest_payment,0) AS INT_BALANCE
												FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
												kpd.keepitemtype_code = kut.keepitemtype_code
												WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period)
											UNION 
											(	SELECT 
												kut.keepitemtype_desc as TYPE_DESC,
												kut.keepitemtype_grp as TYPE_GROUP,
												case kut.keepitemtype_grp WHEN 'DEP' THEN 
													kpd.description
												WHEN 'LON' THEN 
													kpd.loancontract_no
												ELSE null END as PAY_ACCOUNT,
												kpd.period,
												NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
												NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
												NVL(kpd.principal_payment,0) AS PRN_BALANCE,
												NVL(kpd.interest_payment,0) AS INT_BALANCE
												FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
												kpd.keepitemtype_code = kut.keepitemtype_code
												WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											)");
		$getDetailKP->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowDetail = $getDetailKP->fetch()){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];			
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formatcontract($rowDetail["PAY_ACCOUNT"],$func->getConstant('loan_format',$conmysql));
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format',$conmysql));
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$arrayResult['DETAIL'] = $arrGroupDetail;
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