<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $configAS["MEMBER_NO_DEV_DEPOSIT"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $configAS["MEMBER_NO_SALE_DEPOSIT"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrayResult = array();
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmdeposit');
		$arrayResult['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$fetchLastStmAcc = $conoracle->prepare("SELECT * from (SELECT dps.deptaccount_no FROM dpdeptmaster dpm LEFT JOIN dpdeptslip dps ON dpm.deptaccount_no = dps.deptaccount_no 
												WHERE dpm.member_no = :member_no and deptgroup_code IS NOT NULL ORDER BY dps.deptslip_date DESC,dps.deptslip_no DESC) where rownum <= 1");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowAccountLastSTM = $fetchLastStmAcc->fetch();
		$account_no = preg_replace('/-/','',$rowAccountLastSTM["DEPTACCOUNT_NO"]);
		
		$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = :account_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code and dp.membcat_code = dt.membcat_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1 and dp.deptaccount_no = :account_no");
		$getAccount->execute([
			':member_no' => $member_no,
			':account_no' => $account_no
		]);
		$rowAccount = $getAccount->fetch();
		$arrAccount = array();
		$fetchAlias = $conmysql->prepare("SELECT alias_name,path_alias_img FROM gcdeptalias WHERE deptaccount_no = :account_no");
			$fetchAlias->execute([
				':account_no' => $account_no
			]);
		$rowAlias = $fetchAlias->fetch();
		$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"];
		if(isset($rowAlias["path_alias_img"])){
			$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
			$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp';
		}else{
			$arrAccount["ALIAS_PATH_IMG"] = null;
		}
		$account_no_format = $lib->formataccount($account_no,$func->getConstant('dep_format'));
		$arrAccount["DEPTACCOUNT_NO"] = $account_no_format;
		$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
		$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccount["DEPTACCOUNT_NAME"]);
		$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
		$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
		$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
		$arrAccount["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_dept');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO > ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO < 999999";
		}
		$getStatement = $conoracle->prepare("SELECT * FROM (SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
											dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT
											FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
											ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
											WHERE dsm.deptaccount_no = :account_no and dsm.OPERATE_DATE
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
											ORDER BY dsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
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
		$arrayResult["HEADER"] = $arrAccount;
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