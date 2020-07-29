<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyDetail')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$date_process = $func->getConstant('date_process_kp');
		$recv_now = (date('Y') + 543).date('m');
		$dateNow = date('d');
		if($recv_now == trim($dataComing["recv_period"])){
			if($dateNow >= $date_process){
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
											ISNULL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}else{
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
											ISNULL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}
		}else{
			if(trim($dataComing["recv_period"]) > $recv_now){
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
											ISNULL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}else{
				$qureyKpDetail = "SELECT 
										kut.keepitemtype_desc as TYPE_DESC,
										kut.keepitemtype_grp as TYPE_GROUP,
										case kut.keepitemtype_grp 
											WHEN 'DEP' THEN kpd.description
											WHEN 'LON' THEN kpd.loancontract_no
										ELSE kpd.description END as PAY_ACCOUNT,
										kpd.period,
										ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
										ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
										ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
										ISNULL(kpd.interest_payment,0) AS INT_BALANCE
										FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
										kpd.keepitemtype_code = kut.keepitemtype_code
										WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}	
		}
		$arrGroupDetail = array();
		$getDetailKP = $conmssql->prepare($qureyKpDetail);
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
				$contract_no = $rowDetail["PAY_ACCOUNT"];
				if(mb_stripos($contract_no,'.') === FALSE){
					$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
					if(mb_strlen($contract_no) == 10){
						$arrDetail["PAY_ACCOUNT"] = $loan_format;
					}else if(mb_strlen($contract_no) == 11){
						$arrDetail["PAY_ACCOUNT"] = $loan_format.'-'.mb_substr($contract_no,10);
					}
				}else{
					$arrDetail["PAY_ACCOUNT"] = $contract_no;
				}
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
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
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "�� Argument �����ú "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "��� ".$filename." �� Argument �����ú���� "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>