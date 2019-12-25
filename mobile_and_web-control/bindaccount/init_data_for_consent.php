<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
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
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.is_use = '1'");
			$fetchConstantAllowDept->execute([
				':member_no' => $member_no
			]);
			$arrayDeptAllow = array();
			while($rowAllowDept = $fetchConstantAllowDept->fetch()){
				$arrayDeptAllow[] = $rowAllowDept["deptaccount_no"];
			}
			$arrAccBeenBind = array();
			$InitDeptAccountBeenBind = $conmysql->prepare("SELECT deptaccount_no_coop FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status NOT IN('8','-9')");
			$InitDeptAccountBeenBind->execute([':member_no' => $member_no]);
			while($rowAccountBeenbind = $InitDeptAccountBeenBind->fetch()){
				$arrAccBeenBind[] = $rowAccountBeenbind["deptaccount_no_coop"];
			}
			if(sizeof($arrAccBeenBind) > 0){
				$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
														ON dpm.depttype_code = dpt.depttype_code 
														WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
														dpm.deptaccount_no IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = '0'
														and dpm.deptaccount_no NOT IN(".implode(',',$arrAccBeenBind).")");
			}else{
				$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
														ON dpm.depttype_code = dpt.depttype_code 
														WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
														dpm.deptaccount_no IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = '0'");
			}
			$fetchDataAccount->execute([
				':member_no' => $member_no,
				':membcat_code' => $rowDataMember["MEMBCAT_CODE"]
			]);
			$arrayGroupAccount = array();
			while($rowDataAccount = $fetchDataAccount->fetch()){
				$arrayAccount = array();
				$arrayAccount["DEPTTYPE_DESC"] = $rowDataAccount["DEPTTYPE_DESC"];
				$arrayAccount["ACCOUNT_NO"] = $lib->formataccount($rowDataAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrayAccount["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccount["DEPTACCOUNT_NAME"]);
				$arrayGroupAccount[] = $arrayAccount;
			}
			if(sizeof($arrayGroupAccount) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT'] = $arrayGroupAccount;
				$getFormatBank = $conmysql->prepare("SELECT bank_format_account FROM csbankdisplay WHERE bank_code = :bank_code");
				$getFormatBank->execute([':bank_code' => $dataComing["bank_code"]]);
				$rowFormatBank = $getFormatBank->fetch();
				$arrayResult['ACCOUNT_BANK_FORMAT'] = $rowFormatBank["bank_format_account"] ?? $config["ACCOUNT_BANK_FORMAT"];
				$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
				$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0005";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบบัญชีที่สามารถผูกได้";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Not found account for bind";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลผู้ใช้";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Not found membership";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>