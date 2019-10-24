<?php
require_once('../autoload.php');

$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
if($status_token){
	if(isset($dataComing["recv_period"])){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token === 'expired'){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "SQL409";
				$arrayResult['RESPONSE'] = "Invalid RefreshToken is not correct or RefreshToken was expired";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}else{
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'PaymentMonthlyDetail')){
			if($payload["member_no"] == 'dev@mode'){
				$member_no = $config["MEMBER_NO_DEV_KEEPINGMONTH"];
			}else if($payload["member_no"] == 'salemode'){
				$member_no = $config["MEMBER_NO_SALE_KEEPINGMONTH"];
			}else{
				$member_no = $payload["member_no"];
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
														WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period)");
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
}else{
	$arrayResult['RESPONSE_CODE'] = "HEADER500";
	$arrayResult['RESPONSE'] = "Authorization token invalid";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>