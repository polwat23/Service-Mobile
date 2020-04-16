<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
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
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			foreach($arrResponseAPI->accountDetail as $accData){
				if($accData->coopAccountNo == $account_no){
					$arrayHeaderAcc["BALANCE"] = number_format($accData->accountBalance,2);
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		
		$arrayHeaderAcc["DATA_TIME"] = date('H:i');
		$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path,seq_no FROM gcmemodept 
											WHERE deptaccount_no = :account_no");
		$getMemoDP->execute([
			':account_no' => $account_no
		]);
		$arrMemo = array();
		while($rowMemo = $getMemoDP->fetch(PDO::FETCH_ASSOC)){
			$arrMemo[] = $rowMemo;
		}
		$arrHeaderAPISTM[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPISTM["MemberID"] = substr($member_no,-6);
		$arrDataAPISTM["CoopAccountNo"] = $account_no;
		$arrDataAPISTM["FromDate"] = date('c',strtotime($date_before));
		$arrDataAPISTM["ToDate"] = date('c',strtotime($date_now));
		$arrResponseAPISTM = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/InquiryBalance",$arrDataAPISTM,$arrHeaderAPISTM);
		if(!$arrResponseAPISTM["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPISTM = json_decode($arrResponseAPISTM);
		if($arrResponseAPISTM->responseCode == "200"){
			foreach($arrResponseAPISTM->inquieryBalanceDetail as $accData){
				$arrSTM = array();
				$arrSTM["TYPE_TRAN"] = $accData->trxDesc;
				$arrSTM["SIGN_FLAG"] = $accData->trxOperate == '+' ? "1" : "-1";
				$arrSTM["SEQ_NO"] = $accData->trxSeqno;
				$arrSTM["OPERATE_DATE"] = $lib->convertdate($accData->trxDate,'D m Y');
				$arrSTM["TRAN_AMOUNT"] = str_replace('-','',$accData->totalAmount);
				if(array_search($accData->trxSeqno,array_column($arrMemo,'seq_no')) === False){
					$arrSTM["MEMO_TEXT"] = null;
					$arrSTM["MEMO_ICON_PATH"] = null;
				}else{
					$arrSTM["MEMO_TEXT"] = $arrMemo[array_search($accData->trxSeqno,array_column($arrMemo,'seq_no'))]["memo_text"] ?? null;
					$arrSTM["MEMO_ICON_PATH"] = $arrMemo[array_search($accData->trxSeqno,array_column($arrMemo,'seq_no'))]["memo_icon_path"] ?? null;
				}
				$arrayGroupSTM[] = $arrSTM;
			}
		}
		$arrayResult["HEADER"] = $arrayHeaderAcc;
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
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