<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBalanceMaster = $conoracle->prepare("SELECT mp.PRENAME_DESC,cfm.MEMB_NAME,cfm.MEMB_SURNAME,cfm.MEMBGROUP_CODE,
												md.SHORT_NAME1 as DEPARTMENT,md.SHORT_NAME2 as DEPART_GROUP 
												FROM cmconfirmmaster cfm LEFT JOIN mbmembmaster mb ON cfm.MEMBER_NO = mb.MEMBER_NO
												LEFT JOIN mbucfdepartment md ON mb.department_code = md.department_code
												LEFT JOIN mbucfprename mp ON cfm.PRENAME_CODE = mp.PRENAME_CODE
												WHERE cfm.member_no = :member_no and EXTRACT(YEAR FROM cfm.BALANCE_DATE) = EXTRACT(YEAR FROM SYSDATE)");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$getBalanceDetail = $conoracle->prepare("SELECT (CASE WHEN cfb.CONFIRMTYPE_CODE = 'DEP'
												THEN dp.DEPTTYPE_DESC
												ELSE 'ทุนเรือนหุ้น' END) AS DEPTTYPE_DESC,(CASE WHEN cfb.CONFIRMTYPE_CODE = 'DEP'
												THEN cfb.REF_MASTNO
												ELSE '' END) as DEPTACCOUNT_NO,cfb.BALANCE_AMT
												FROM cmconfirmbalance cfb LEFT JOIN dpdepttype dp ON cfb.SHRLONTYPE_CODE = dp.DEPTTYPE_CODE
												WHERE cfb.member_no = :member_no and EXTRACT(YEAR FROM cfb.BALANCE_DATE) = EXTRACT(YEAR FROM SYSDATE)");
		$getBalanceDetail->execute([':member_no' => $member_no]);
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			
		}
		$arrayResult['RESULT'] = TRUE;
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
	echo json_encode($arrayResult);
	exit();
}
?>