<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$arrayResult = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmdeposit');
		$arrayResult['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$CountRowNext = $dataComing["amt_row_next"] ?? 20;
		$oldRowOffer = $dataComing["amt_old_row_offer"] ?? 0;
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$getStatement = $conoracle->prepare("SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
											dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT
											FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
											ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
											WHERE dsm.deptaccount_no = :account_no and dsm.OPERATE_DATE
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ORDER BY dsm.SEQ_NO DESC
											OFFSET ".$oldRowOffer." ROWS FETCH NEXT ".$CountRowNext." ROWS ONLY");
		$getStatement->execute([
			':account_no' => $account_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getStatement->fetch()){
			$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path FROM gcmemodept 
											WHERE deptaccount_no = :account_no and seq_no = :seq_no");
			$getMemoDP->execute([
				':account_no' => $account_no,
				':seq_no' => $rowStm["SEQ_NO"]
			]);
			$rowMemo = $getMemoDP->fetch();
			$arrSTM = array();
			$arrSTM["TYPE_TRAN"] = $rowStm["TYPE_TRAN"];
			$arrSTM["SIGN_FLAG"] = $rowStm["SIGN_FLAG"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["TRAN_AMOUNT"] = number_format($rowStm["TRAN_AMOUNT"],2);
			$arrSTM["MEMO_TEXT"] = $rowMemo["memo_text"];
			$arrSTM["MEMO_ICON_PATH"] = $rowMemo["memo_icon_path"];
			$arrayGroupSTM[] = $arrSTM;
		}
		if(sizeof($arrayGroupSTM) > 0 || isset($new_token)){
			$arrayResult["STATEMENT"] = $arrayGroupSTM;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
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