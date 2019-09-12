<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"])
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["account_no"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "SQL409";
			$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}else{
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'DepositStatement')){
		$arrayResult = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmdeposit',$conmysql);
		$arrayResult['LIMIT_DURATION'] = $limit;
		if(isset($dataComing["date_start"])){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		$date_now = date('Y-m-d');
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$getStatement = $conoracle->prepare("SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
											dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT
											FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
											ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
											WHERE dsm.deptaccount_no = :account_no and dsm.ENTRY_DATE
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ORDER BY dsm.SEQ_NO DESC");
		$getStatement->execute([
			':account_no' => $account_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getStatement->fetch()){
			$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path FROM mdbmemodept 
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
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>