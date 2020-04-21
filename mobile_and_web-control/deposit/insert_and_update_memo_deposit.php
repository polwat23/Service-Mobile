<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		if(($dataComing["memo_text_emoji_"] == "" || empty($dataComing["memo_text_emoji_"])) && ($dataComing["memo_icon_path"] == "" || empty($dataComing["memo_icon_path"]))){
			$arrayResult['RESPONSE_CODE'] = "WS4004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			echo json_encode($arrayResult);
			exit();
		}
		$updateMemoDept = $conmysql->prepare("UPDATE gcmemodept SET memo_text = :memo_text,memo_icon_path = :memo_icon_path
												WHERE deptaccount_no = :deptaccount_no and ref_no = :seq_no");
		if($updateMemoDept->execute([
			':memo_text' => $dataComing["memo_text_emoji_"] == "" ? null : $dataComing["memo_text_emoji_"],
			':memo_icon_path' => $dataComing["memo_icon_path"] == "" ? null : $dataComing["memo_icon_path"],
			':deptaccount_no' => $account_no,
			':seq_no' => $dataComing["seq_no"]
		]) && $updateMemoDept->rowCount() > 0){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$insertMemoDept = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,memo_icon_path,deptaccount_no,ref_no) 
													VALUES(:memo_text,:memo_icon_path,:deptaccount_no,:seq_no)");
			if($insertMemoDept->execute([
				':memo_text' => $dataComing["memo_text_emoji_"] == "" ? null : $dataComing["memo_text_emoji_"],
				':memo_icon_path' => $dataComing["memo_icon_path"] == "" ? null : $dataComing["memo_icon_path"],
				':deptaccount_no' => $account_no,
				':seq_no' => $dataComing["seq_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrExecute = [
					':memo_text' => $dataComing["memo_text_emoji_"],
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
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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