<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','source_deptaccount_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		if($dataComing["source_deptaccount_no"] == $dataComing["deptaccount_no"]){
			$arrayResult['RESPONSE_CODE'] = "WS0045";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrarDataAcc = array();
		$getDataAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
												WHERE dpm.deptaccount_no = :deptaccount_no");
		$getDataAcc->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
		$rowDataAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataAcc["DEPTTYPE_DESC"])){
			$checkAllowToTransaction = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
			$checkAllowToTransaction->execute([':member_no' => $payload["member_no"]]);
			if($checkAllowToTransaction->rowCount() > 0){
				$arrarDataAcc["DEPTACCOUNT_NO"] = $dataComing["deptaccount_no"];
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($dataComing["deptaccount_no"],$func->getConstant('dep_format'));
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($dataComing["deptaccount_no"],$func->getConstant('hidden_dep'));
				$arrarDataAcc["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
				$arrarDataAcc["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
				$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0026";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0025";
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