<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_code'],$dataComing)){
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
		if(isset($rowDataMember["CARD_PERSON"])){
			$fetchConstantAllowDept = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant and gad.is_use = '1'
															WHERE gat.member_no = :member_no and gat.is_use = '1'");
			$fetchConstantAllowDept->execute([
				':member_no' => $member_no
			]);
			$arrayDeptAllow = array();
			while($rowAllowDept = $fetchConstantAllowDept->fetch()){
				$arrayDeptAllow[] = $rowAllowDept["deptaccount_no"];
			}
			$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
														ON dpm.depttype_code = dpt.depttype_code 
														WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
														dpm.deptaccount_no IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = '0'");
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
			if(sizeof($arrayGroupAccount) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT'] = $arrayGroupAccount;
				$getFormatBank = $conmysql->prepare("SELECT bank_format_account FROM csbankdisplay WHERE bank_code = :bank_code");
				$getFormatBank->execute([':bank_code' => $dataComing["bank_code"]]);
				$rowFormatBank = $getFormatBank->fetch();
				$arrayResult['ACCOUNT_BANK_FORMAT'] = $rowFormatBank["bank_format_account"] ?? "xxx-x-xxxxx-x";
				$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
				$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0016";
				$arrayResult['RESPONSE_MESSAGE'] = "Dont have coop account for bind account";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(403);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>