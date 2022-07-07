<?php
require_once('../autoload.php');

$dbhost = "127.0.0.1";
$dbuser = "root";
$dbpass = "EXAT2022";
$dbname = "mobile_exat_test";

$conmysql = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
$conmysql->exec("set names utf8mb4");


$dbnameOra = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.1.201)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = iorcl)
			)
		  )";
$conoracle = new PDO("oci:dbname=".$dbnameOra.";charset=utf8", "iscotest", "iscotest");
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','id_accountconstant'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,id_accountconstant) 
												VALUES(:deptaccount_no,:member_no,:id_accountconstant)");
		if($insertDeptAllow->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"],
			':member_no' => $payload["member_no"],
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