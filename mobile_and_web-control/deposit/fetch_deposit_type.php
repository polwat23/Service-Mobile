<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllAccount = array();
		$getSumAllAccount = $conmssqlcoop->prepare("select SUM(dpt.balance) as SUM_BALANCE from codeposit_master dpm LEFT JOIN codeposit_transaction dpt 
													ON dpm.deposit_id = dpt.deposit_id
													and dpt.transaction_seq = (SELECT MAX(transaction_seq) FROM codeposit_transaction WHERE deposit_id = dpm.deposit_id) 
													and dpt.Transaction_subseq = '0'
													where  dpm.status  = 'A' and dpm.member_id = :member_no");
		$getSumAllAccount->execute([':member_no' => $member_no]);
		$rowSumbalance = $getSumAllAccount->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$getAccount = $conmssqlcoop->prepare("SELECT  dt.deposit_type as DEPTTYPE_CODE,dt.description as DEPTTYPE_DESC,dm.deposit_id as DEPTACCOUNT_NO,
										dm.description as DEPTACCOUNT_NAME,dpt.balance as BALANCE,
										(SELECT max(transaction_date) FROM codeposit_transaction WHERE deposit_id = dm.deposit_id) as LAST_OPERATE_DATE
										FROM  codeposit_master dm  LEFT JOIN codeposit_type dt ON dm.deposit_type = dt.deposit_type
										LEFT JOIN codeposit_transaction dpt ON dm.deposit_id = dpt.deposit_id  
										and dpt.transaction_seq = (SELECT MAX(transaction_seq) FROM codeposit_transaction WHERE deposit_id = dm.deposit_id)
										AND dpt.transaction_subseq = 0
										WHERE dm.member_id = :member_no and dm.status = 'A' order by dm.deposit_id ASC");
		$getAccount->execute([':member_no' => $member_no]);
		while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $rowAccount["DEPTACCOUNT_NO"];
			$arrayHeaderAcc = array();
			if($dataComing["channel"] == 'web'){
				if(file_exists(__DIR__.'/../../resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'.jpg')){
					$arrGroupAccount["COVER_IMG"] = $config["URL_SERVICE"].'resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'.jpg?v='.date('Ym');
				}else{
					$arrGroupAccount["COVER_IMG"] = null;
				}
			}
			$fetchAlias = $conmssql->prepare("SELECT alias_name,path_alias_img,CONVERT(char(10),update_date,20) as update_date FROM gcdeptalias WHERE DEPTACCOUNT_NO = :account_no");
			$fetchAlias->execute([
				':account_no' => $rowAccount["DEPTACCOUNT_NO"]
			]);
			$rowAlias = $fetchAlias->fetch(PDO::FETCH_ASSOC);
			$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"] ?? null;
			if(isset($rowAlias["path_alias_img"])){
				$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
				$arrAccount["ALIAS_PATH_IMG_WEBP"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp?v='.$rowAlias["update_date"];
				$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$rowAlias["path_alias_img"].'?v='.$rowAlias["update_date"];
			}else{
				$arrAccount["ALIAS_PATH_IMG"] = null;
				$arrAccount["ALIAS_PATH_IMG_WEBP"]  = null;
			}
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["DEPTACCOUNT_NO_HIDDEN"] =$account_no;
			$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccount["DEPTACCOUNT_NAME"]));
			$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
			$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
			$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
			$arrAccount["IS_UPDATE_ALIAS_NAME"] = false;
			$arrAccount["IS_UPDATE_ALIAS_IMG"] = false;
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			$arrGroupAccount['DEPT_TYPE_CODE'] = $rowAccount["DEPTTYPE_CODE"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>