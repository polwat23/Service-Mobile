<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'InsureInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_INSURANCE"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_INSURANCE"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchinSureInfo = $conoracle->prepare("SELECT ist.INSURETYPE_DESC,isit.INSITEMTYPE_DESC,isit.SIGN_FLAG,issm.PREMIUM_PAYMENT
												FROM insinsuremaster ism LEFT JOIN insinsuretype ist ON ism.insuretype_code = ist.insuretype_code
												LEFT JOIN insinsurestatement issm ON ism.insurance_no = issm.insurance_no
												LEFT JOIN insucfinsitemtype isit ON issm.insitemtype_code = isit.insitemtype_code
												WHERE ism.insurance_status = '1' and ism.member_no = :member_no");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch()){
			$arrayInsure = array();
			$arrGroupIns = array();
			$arrayInsure["PAYMENT"] = number_format($rowInsure["PREMIUM_PAYMENT"],2);
			$arrayInsure["SIGN_FLAG"] = $rowInsure["SIGN_FLAG"];
			$arrayInsure["INS_STM_TYPE"] = $rowInsure["INSITEMTYPE_DESC"];
			$arrGroupIns["INS_TYPE"] = $rowInsure["INSURETYPE_DESC"];
			if(array_search($rowInsure["INSURETYPE_DESC"],array_column($arrGroupAllIns,'INS_TYPE')) === False){
				($arrGroupIns['STATEMENT'])[] = $arrayInsure;
				$arrGroupAllIns[] = $arrGroupIns;
			}else{
				($arrGroupAllIns[array_search($rowInsure["INSURETYPE_DESC"],array_column($arrGroupAllIns,'INS_TYPE'))]["STATEMENT"])[] = $arrayInsure;
			}
		}
		if(sizeof($arrGroupIns) > 0 || isset($new_token)){
			$arrayResult['INSURE'] = $arrGroupAllIns;
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
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>