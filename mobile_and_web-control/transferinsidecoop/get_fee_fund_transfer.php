<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$to_deptaccount_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$getMemberNo = $conmysql->prepare("SELECT member_no FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no");
		$getMemberNo->execute([':deptaccount_no' => $to_deptaccount_no]);
		$rowMember_noDest = $getMemberNo->fetch(PDO::FETCH_ASSOC);
		$member_no_dest = $configAS[$rowMember_noDest["member_no"]] ?? $rowMember_noDest["member_no"];
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["FromCoopAccountNo"] = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$arrDataAPI["ToMemberID"] = substr($member_no_dest,-6);
		$arrDataAPI["ToCoopAccountNo"] = $to_deptaccount_no;
		$arrDataAPI["TransferAmount"] = $dataComing["amt_transfer"];
		$arrDataAPI["UserRequestDate"] = date('c');
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/CheckTransferFee",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$arrayResult['FEE_AMT'] = preg_replace('/,/', '', $arrResponseAPI->coopFee);
			$arrayResult['FEE_AMT_FORMAT'] = $arrResponseAPI->coopFee;
			$arrayResult['TRANS_REF_CODE'] = $arrResponseAPI->transferRefCode;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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