<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'TransactionWithdrawDeposit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop FROM gcbindaccount 
												WHERE member_no = :member_no and bindaccount_status = '1'");
		$fetchBindAccount->execute([':member_no' => $member_no]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch()){
				$arrAccBind = array();
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["DEPTACCOUNT_NO"] = $rowAccBind["deptaccount_no_coop"];
				$arrAccBind["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_coop"],$func->getConstant('dep_format',$conmysql));
				$arrAccBind["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_coop"],$func->getConstant('hidden_dep',$conmysql));
				$getDataAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc,dpm.prncbal
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													and dpm.membcat_code = dpt.membcat_code
													WHERE dpm.deptaccount_no = :deptaccount_no");
				$getDataAcc->execute([':deptaccount_no' => $rowAccBind["deptaccount_no_coop"]]);
				$rowDataAcc = $getDataAcc->fetch();
				if(isset($rowDataAcc["DEPTTYPE_DESC"])){
					$arrAccBind["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
					$arrAccBind["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
					$arrAccBind["BALANCE"] = $rowDataAcc["PRNCBAL"];
					$arrAccBind["BALANCE_FORMAT"] = number_format($rowDataAcc["PRNCBAL"],2);
					$arrGroupAccBind[] = $arrAccBind;
				}
			}
			if(sizeof($arrGroupAccBind) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT'] = $arrGroupAccBind;
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