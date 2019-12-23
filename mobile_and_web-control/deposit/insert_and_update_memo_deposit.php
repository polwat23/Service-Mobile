<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','memo_text','memo_icon_path','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$updateMemoDept = $conmysql->prepare("UPDATE gcmemodept SET memo_text = :memo_text,memo_icon_path = :memo_icon_path
												WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
		if($updateMemoDept->execute([
			':memo_text' => $dataComing["memo_text"],
			':memo_icon_path' => $dataComing["memo_icon_path"],
			':deptaccount_no' => $account_no,
			':seq_no' => $dataComing["seq_no"]
		]) && $updateMemoDept->rowCount() > 0){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$insertMemoDept = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,memo_icon_path,deptaccount_no,seq_no) 
													VALUES(:memo_text,:memo_icon_path,:deptaccount_no,:seq_no)");
			if($insertMemoDept->execute([
				':memo_text' => $dataComing["memo_text"],
				':memo_icon_path' => $dataComing["memo_icon_path"],
				':deptaccount_no' => $account_no,
				':seq_no' => $dataComing["seq_no"]
			])){
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrExecute = [
					':memo_text' => $dataComing["memo_text"],
					':memo_icon_path' => $dataComing["memo_icon_path"],
					':deptaccount_no' => $account_no,
					':seq_no' => $dataComing["seq_no"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $insertMemoDept;
				$arrError["ERROR_CODE"] = 'WS1005';
				$lib->addLogtoTxt($arrError,'memo_error');
				$arrayResult['RESPONSE_CODE'] = "WS1005";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเพิ่มบันทึกช่วยจำได้กรุณาติดต่อสหกรณ์ #WS1005";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot add memo please contact cooperative #WS1005";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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