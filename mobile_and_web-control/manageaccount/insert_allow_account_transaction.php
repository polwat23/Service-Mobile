<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','id_accountconstant'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if(is_string($dataComing["deptaccount_no"]) && is_string($dataComing["id_accountconstant"])){
			$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,limit_transaction_amt,id_accountconstant) 
													VALUES(:deptaccount_no,:member_no,:limit_transaction_amt,:id_accountconstant)");
			if($insertDeptAllow->execute([
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':member_no' => $payload["member_no"],
				':limit_transaction_amt' => 500000.00,//$func->getConstant("limit_withdraw"),
				':id_accountconstant' => $dataComing["id_accountconstant"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1023",
					":error_desc" => "อนุญาตบัญชีทำธุรกรรมไม่ได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถอนุญาตบัญชีทำธุรกรรมได้ได้เพราะ Insert ลง gcuserallowacctransaction ไม่ได้"."\n"."Query => ".$insertDeptAllow->queryString."\n"."Param => ". json_encode([
					':deptaccount_no' => $dataComing["deptaccount_no"],
					':member_no' => $payload["member_no"],
					':id_accountconstant' => $dataComing["id_accountconstant"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$limit_trans = 500000.00;
			$bulkInsertData = array();
			foreach($dataComing["deptaccount_no"] as  $key => $deptaccount_no){
				$bulkInsertData[] = "('".$deptaccount_no."','".$payload["member_no"]."','".$limit_trans."',".$dataComing["id_accountconstant"][$key].")";
			}
			$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,limit_transaction_amt,id_accountconstant) 
													VALUES".implode(",",$bulkInsertData));
			if($insertDeptAllow->execute()){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1023",
					":error_desc" => "อนุญาตบัญชีทำธุรกรรมไม่ได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถอนุญาตบัญชีทำธุรกรรมได้ได้เพราะ Insert ลง gcuserallowacctransaction ไม่ได้"."\n"."Query => ".$insertDeptAllow->queryString."\n"."Param => ". json_encode($bulkInsertData);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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