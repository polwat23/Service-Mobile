<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $configAS["MEMBER_NO_DEV_INSURANCE"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $configAS["MEMBER_NO_SALE_INSURANCE"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchinSureInfo = $conoracle->prepare("SELECT ist.INSURETYPE_DESC,isit.INSITEMTYPE_DESC,isit.SIGN_FLAG,issm.PREMIUM_PAYMENT
												FROM insinsuremaster ism LEFT JOIN insinsuretype ist ON ism.insuretype_code = ist.insuretype_code
												LEFT JOIN insinsurestatement issm ON ism.insurance_no = issm.insurance_no
												LEFT JOIN insucfinsitemtype isit ON issm.insitemtype_code = isit.insitemtype_code
												WHERE ism.insurance_status = '1' and ism.member_no = :member_no ORDER BY issm.SEQ_NO DESC");
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
		$arrayResult['INSURE'] = $arrGroupAllIns;
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