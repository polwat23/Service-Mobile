<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_bindaccount'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchDataMember = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch();
		if(isset($rowDataMember["CARD_PERSON"])){
			$fetchAccountWaitBind = $conmysql->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank,mobile_no FROM gcbindaccount WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '8'");
			$fetchAccountWaitBind->execute([':id_bindaccount' => $dataComing["id_bindaccount"]]);
			if($fetchAccountWaitBind->rowCount() > 0){
				$rowAccountWait = $fetchAccountWaitBind->fetch();
				$fetchConstantAllowDept = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
																LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
																WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.is_use = '1'");
				$fetchConstantAllowDept->execute([
					':deptaccount_no' => $rowAccountWait["deptaccount_no_coop"]
				]);
				if($fetchConstantAllowDept->rowCount() > 0){
					$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
															ON dpm.depttype_code = dpt.depttype_code 
															WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = '0'");
					$fetchDataAccount->execute([
						':deptaccount_no' => $rowAccountWait["deptaccount_no_coop"]
					]);
					$rowDataAccount = $fetchDataAccount->fetch();
					if(isset($rowDataAccount["DEPTACCOUNT_NO"])){
						$arrayResult["DEPTTYPE_DESC"] = $rowDataAccount["DEPTTYPE_DESC"];
						$arrayResult["ACCOUNT_NO"] = $lib->formataccount($rowDataAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrayResult["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccount["DEPTACCOUNT_NAME"]);
						$getFormatBank = $conmysql->prepare("SELECT bank_format_account FROM csbankdisplay WHERE bank_code = :bank_code");
						$getFormatBank->execute([':bank_code' => $dataComing["bank_code"]]);
						$rowFormatBank = $getFormatBank->fetch();
						$arrayResult['ACCOUNT_BANK_FORMAT'] = $rowFormatBank["bank_format_account"] ?? "xxx-x-xxxxx-x";
						$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
						$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
						$arrayResult["MOBILE_NO"] = $lib->formatphone($rowAccountWait["mobile_no"]);
						if(isset($new_token)){
							$arrayResult['NEW_TOKEN'] = $new_token;
						}
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0032";
						$arrayResult['RESPONSE_MESSAGE'] = "Not found account";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0019";
					$arrayResult['RESPONSE_MESSAGE'] = "This account not allow to bindaccount";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				http_response_code(204);
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