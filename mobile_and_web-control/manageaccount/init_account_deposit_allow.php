<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $configAS["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $configAS["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrDeptAllowed = array();
		$arrAccAllowed = array();
		$arrAllowAccGroup = array();
		$getDeptTypeAllow = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept
												WHERE is_use = '1'");
		$getDeptTypeAllow->execute();
		if($getDeptTypeAllow->rowCount() > 0 ){
			while($rowDeptAllow = $getDeptTypeAllow->fetch()){
				$arrDeptAllowed[] = $rowDeptAllow["dept_type_code"];
			}
			$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no");
			$InitDeptAccountAllowed->execute([':member_no' => $payload["member_no"]]);
			while($rowAccountAllowed = $InitDeptAccountAllowed->fetch()){
				$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
			}
			if(sizeof($arrAccAllowed) > 0){
				$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.depttype_code,dpm.membcat_code
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															and dpm.membcat_code = dpt.membcat_code
															WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
															and dpm.deptaccount_no NOT IN(".implode(',',$arrAccAllowed).")
															and dpm.member_no = :member_no and dpm.deptclose_status = 0");
			}else{
				$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.depttype_code,dpm.membcat_code
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															and dpm.membcat_code = dpt.membcat_code
															WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
															and dpm.member_no = :member_no and dpm.deptclose_status = 0");

			}
			$getAccountAllinCoop->execute([':member_no' => $member_no]);
			while($rowAccIncoop = $getAccountAllinCoop->fetch()){
				$arrAccInCoop["DEPTACCOUNT_NO"] = $rowAccIncoop["DEPTACCOUNT_NO"];
				$arrAccInCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccInCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$arrAccInCoop["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccIncoop["DEPTACCOUNT_NAME"]);
				$arrAccInCoop["DEPT_TYPE"] = $rowAccIncoop["DEPTTYPE_DESC"];
				$getIDDeptTypeAllow = $conmysql->prepare("SELECT id_accountconstant FROM gcconstantaccountdept
														WHERE dept_type_code = :depttype_code and member_cate_code = :membcat_code");
				$getIDDeptTypeAllow->execute([
					':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"],
					':membcat_code' => $rowAccIncoop["MEMBCAT_CODE"]
				]);
				$rowIDDeptTypeAllow = $getIDDeptTypeAllow->fetch();
				$arrAccInCoop["ID_ACCOUNTCONSTANT"] = $rowIDDeptTypeAllow["id_accountconstant"];
				$arrAllowAccGroup[] = $arrAccInCoop;
			}
			$arrayResult['ACCOUNT_ALLOW'] = $arrAllowAccGroup;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0024";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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