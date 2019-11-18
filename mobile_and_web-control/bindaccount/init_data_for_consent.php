<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchDataMember = $conoracle->prepare("SELECT card_person,membcat_code FROM mbmembmaster WHERE member_no = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch();
		$fetchConstantAllowDept = $conmysql->prepare("SELECT dept_type_code FROM gcconstantallowtransaction WHERE is_use = '1' and member_cate_code = :membcat_code");
		$fetchConstantAllowDept->execute([
			':membcat_code' => $rowDataMember["MEMBCAT_CODE"]
		]);
		$arrayDeptAllow = array();
		while($rowAllowDept = $fetchConstantAllowDept->fetch()){
			$arrayDeptAllow[] = $rowAllowDept["dept_type_code"];
		}
		$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
													ON dpm.depttype_code = dpt.depttype_code 
													WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
													dpm.depttype_code IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = '0'");
		$fetchDataAccount->execute([
			':member_no' => $member_no,
			':membcat_code' => $rowDataMember["MEMBCAT_CODE"]
		]);
		$arrayGroupAccount = array();
		while($rowDataAccount = $fetchDataAccount->fetch()){
			$arrayAccount = array();
			$arrayAccount["DEPTTYPE_DESC"] = $rowDataAccount["DEPTTYPE_DESC"];
			$arrayAccount["ACCOUNT_NO"] = $lib->formataccount($rowDataAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format',$conmysql));
			$arrayAccount["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccount["DEPTACCOUNT_NAME"]);
			$arrayGroupAccount[] = $arrayAccount;
		}
		$arrayResult['ACCOUNT'] = $arrayGroupAccount;
		$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
		$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
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