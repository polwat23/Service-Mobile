<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyDetail')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupDetail = array();
		$getDetailKP = $conoracle->prepare("SELECT * FROM (
												SELECT 
													kut.keepitemtype_desc as TYPE_DESC,
													kut.keepitemtype_grp as TYPE_GROUP,
													case kut.keepitemtype_grp 
														WHEN 'DEP' THEN kpd.description
														WHEN 'LON' THEN kpd.loancontract_no
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
													case kut.keepitemtype_grp 
														WHEN 'DEP' THEN kpd.description
														WHEN 'LON' THEN kpd.loancontract_no
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
		while($rowDetail = $getDetailKP->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];			
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$arrayResult['DETAIL'] = $arrGroupDetail;
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