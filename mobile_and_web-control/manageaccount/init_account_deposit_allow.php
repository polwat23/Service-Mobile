<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ManagementAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrDeptAllowed = array();
		$arrAccAllowed = array();
		$arrAllowAccGroup = array();
		$getDeptTypeAllow = $conmysql->prepare("SELECT gca.dept_type_code
												FROM gcconstantallowtransaction gat LEFT JOIN gcconstantaccount gca 
												ON gat.id_accountconstant = gca.id_accountconstant and gca.is_use = '1'
												WHERE gat.is_use = '1'");
		$getDeptTypeAllow->execute();
		if($getDeptTypeAllow->rowCount() > 0 ){
			while($rowDeptAllow = $getDeptTypeAllow->fetch()){
				$arrDeptAllowed[] = $rowDeptAllow["dept_type_code"];
			}
			$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no");
			$InitDeptAccountAllowed->execute([':member_no' => $member_no]);
			while($rowAccountAllowed = $InitDeptAccountAllowed->fetch()){
				$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
			}
			if(sizeof($arrAccAllowed) > 0){
				$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															and dpm.membcat_code = dpt.membcat_code
															WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
															and dpm.deptaccount_no NOT IN(".implode(',',$arrAccAllowed).")
															and dpm.member_no = :member_no");
			}else{
				$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															and dpm.membcat_code = dpt.membcat_code
															WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
															and dpm.member_no = :member_no");

			}
			$getAccountAllinCoop->execute([':member_no' => $member_no]);
			while($rowAccIncoop = $getAccountAllinCoop->fetch()){
				$arrAccInCoop["ACCOUNT_NO"] = $rowAccIncoop["DEPTACCOUNT_NO"];
				$arrAccInCoop["ACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('dep_format',$conmysql));
				$arrAccInCoop["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccIncoop["DEPTACCOUNT_NAME"]);
				$arrAccInCoop["DEPT_TYPE"] = $rowAccIncoop["DEPTTYPE_DESC"];
				$arrAllowAccGroup[] = $arrAccInCoop;
			}
			if(sizeof($arrAllowAccGroup) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT_ALLOW'] = $arrAllowAccGroup;
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
			$arrayResult['RESPONSE_CODE'] = "WS0018";
			$arrayResult['RESPONSE_MESSAGE'] = "Coop is not allow any dept type";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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