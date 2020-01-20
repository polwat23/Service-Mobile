<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','type_alias','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		if($dataComing["type_alias"] == 'alias_img'){
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET path_alias_img = null WHERE deptaccount_no = :deptaccount_no");
		}else if($dataComing["type_alias"] == 'alias_name'){
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET alias_name = null WHERE deptaccount_no = :deptaccount_no");
		}else{
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET path_alias_img = null,alias_name = null WHERE deptaccount_no = :deptaccount_no");
		}
		if($DeleteAliasDept->execute([
			':deptaccount_no' => $account_no,
		])){
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':deptaccount_no' => $account_no
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $DeleteAliasDept;
			$arrError["ERROR_CODE"] = 'WS1028';
			$lib->addLogtoTxt($arrError,'alias_error');
			$arrayResult['RESPONSE_CODE'] = "WS1028";
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