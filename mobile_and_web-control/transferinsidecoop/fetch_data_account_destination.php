<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'TransferDepInsideCoop')){
		$arrarDataAcc = array();
		$getDataAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
												and dpm.membcat_code = dpt.membcat_code
												WHERE dpm.deptaccount_no = :deptaccount_no");
		$getDataAcc->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
		$rowDataAcc = $getDataAcc->fetch();
		if(isset($rowDataAcc["DEPTTYPE_DESC"])){
			$checkAllowToTransaction = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction 
															WHERE deptaccount_no = :deptaccount_no and is_use = '1'");
			$checkAllowToTransaction->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
			if($checkAllowToTransaction->rowCount() > 0){
				$arrarDataAcc["DEPTACCOUNT_NO"] = $dataComing["deptaccount_no"];
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($dataComing["deptaccount_no"],$func->getConstant('dep_format',$conmysql));
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($dataComing["deptaccount_no"],$func->getConstant('hidden_dep',$conmysql));
				$arrarDataAcc["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
				$arrarDataAcc["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
				$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0019";
				$arrayResult['RESPONSE_MESSAGE'] = "Account is not allow to fund transfer";
				$arrayResult['RESULT'] = FALSE;
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