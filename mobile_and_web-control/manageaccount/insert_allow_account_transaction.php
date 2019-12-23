<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','id_accountconstant'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,id_accountconstant) 
												VALUES(:deptaccount_no,:member_no,:id_accountconstant)");
		if($insertDeptAllow->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"],
			':member_no' => $member_no,
			':id_accountconstant' => $dataComing["id_accountconstant"]
		])){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':member_no' => $member_no,
				':id_accountconstant' => $dataComing["id_accountconstant"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertDeptAllow;
			$arrError["ERROR_CODE"] = 'WS1023';
			$lib->addLogtoTxt($arrError,'manageaccount_error');
			$arrayResult['RESPONSE_CODE'] = "WS1023";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอนุญาติบัญชีเพื่อทำธุรกรรมได้ กรุณาติดต่อสหกรณ์ #WS1023";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot allow account for transaction please contact cooperative #WS1023";
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