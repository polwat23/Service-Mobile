<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$DeleteMemoDept = $conmysql->prepare("DELETE FROM gcmemodept WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
		if($DeleteMemoDept->execute([
			':deptaccount_no' => $account_no,
			':seq_no' => $dataComing["seq_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':deptaccount_no' => $account_no,
				':seq_no' => $dataComing["seq_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $DeleteMemoDept;
			$arrError["ERROR_CODE"] = 'WS1004';
			$lib->addLogtoTxt($arrError,'memo_error');
			$arrayResult['RESPONSE_CODE'] = "WS1004";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถลบบันทึกช่วยจำได้กรุณาติดต่อสหกรณ์ #WS1004";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot delete memo please contact cooperative #WS1004";
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