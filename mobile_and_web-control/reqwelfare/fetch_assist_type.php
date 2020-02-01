<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $configAS["MEMBER_NO_DEV_ASSIST"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $configAS["MEMBER_NO_SALE_ASSIST"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrAssistGrp = array();
		$fetchCateGrp = $conoracle->prepare("SELECT MEMBCAT_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$fetchCateGrp->execute([':member_no' => $member_no]);
		$rowCateGrp = $fetchCateGrp->fetch();
		$fetchAssistType = $conmysql->prepare("SELECT welfare_type_code,welfare_type_desc FROM gcconstantwelfare WHERE 
												(member_cate_code = :membcat_code OR member_cate_code = 'AL') and is_use = '1'");
		$fetchAssistType->execute([':membcat_code' => $rowCateGrp["MEMBCAT_CODE"]]);
		while($rowAssistType = $fetchAssistType->fetch()){
			$arrAssist = array();
			$arrAssist["WELFARE_CODE"] = $rowAssistType["welfare_type_code"];
			$arrAssist["WELFARE_DESC"] = $rowAssistType["welfare_type_desc"];
			$arrAssistGrp[] = $arrAssist;
		}
		$arrayResult['WELFARE_TYPE'] = $arrAssistGrp;
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