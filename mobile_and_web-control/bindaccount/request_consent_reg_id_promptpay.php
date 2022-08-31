<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','citizen_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$checkAccBankBeenbind = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status IN('0','1')");
		$checkAccBankBeenbind->execute([':member_no' => $payload["member_no"]]);
		if($checkAccBankBeenbind->rowCount() > 0){
			$arrayResult['RESPONSE_CODE'] = "WS0036";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$sigma_key = $lib->generate_token();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
												FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												WHERE MB.member_no = :member_no");
		$fetchMemberName->execute([
			':member_no' => $member_no
		]);
		$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
		$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
		$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,citizen_id,bank_account_name,bank_account_name_en,bank_code,bind_date,bindaccount_status,id_token,account_payfee) 
														VALUES(:sigma_key,:member_no,:citizen_id,:citizen_id,:citizen_id,:bank_account_name,:bank_account_name,'999',NOW(),'1',:id_token,:acc_payfee)");
		if($insertPendingBindAccount->execute([
			':sigma_key' => $sigma_key,
			':member_no' => $payload["member_no"],
			':citizen_id' => $dataComing["citizen_id"],
			':bank_account_name' => $account_name_th,
			':id_token' => $payload["id_token"],
			':acc_payfee' => isset($dataComing["account_payfee"]) ? preg_replace('/-/','',$dataComing["account_payfee"]) : null
		])){
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '1',
				':coop_account_no' => null
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			
			$arrayResult['RESPONSE_CODE'] = "WS1022";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '-9',
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $arrayResult['RESPONSE_MESSAGE'],
				':coop_account_no' => null,
				':data_bind_error' => json_encode([
					':sigma_key' => $sigma_key,
					':member_no' => $payload["member_no"],
					':citizen_id' => $dataComing["citizen_id"],
					':bank_account_name' => $account_name_th,
					':bank_account_name_en' => $account_name_th,
					':id_token' => $payload["id_token"],
					':acc_payfee' => isset($dataComing["account_payfee"]) ? preg_replace('/-/','',$dataComing["account_payfee"]) : null
				],JSON_UNESCAPED_UNICODE),
				':query_error' => $insertPendingBindAccount->queryString,
				':query_flag' => '-9'
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$message_error = "ผูกบัญชีไม่ได้เพราะ Insert ลง gcbindaccount ไม่ได้ "."\n"."Query => ".$insertPendingBindAccount->queryString."\n"."Param =>". json_encode([
				':sigma_key' => $sigma_key,
				':member_no' => $payload["member_no"],
				':citizen_id' => $dataComing["citizen_id"],
				':bank_account_name' => $account_name_th,
				':bank_account_name_en' => $account_name_th,
				':id_token' => $payload["id_token"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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