<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'QRCodeScanner')){
		
		if($dataComing["type"] == "linebotregister"){
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			//เช็คเฉพาะเจ้าหน้าที่ 
			$checkMemberStaff = $conoracle->prepare("SELECT
																mb.member_no,
																mp.prename_short,
																mb.memb_name,
																mb.memb_surname,
																mt.membtype_desc
														FROM
																mbmembmaster mb
																LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
																LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
														where  mb.RESIGN_STATUS = '0'
														and mb.MEMBCAT_CODE = '10'
														and mb.MEMBTYPE_CODE = '18'
														and mb.member_no = :member_no");
			$checkMemberStaff->execute([':member_no' => $member_no]);
			$rowStaff = $checkMemberStaff->fetch(PDO::FETCH_ASSOC);
			
			
			$checkUserForTest = $conmysql->prepare("SELECT member_no FROM lbmeberfortest WHERE member_no = :member_no");
			$checkUserForTest->execute([':member_no' => $member_no]);
			$rowUserTest = $checkUserForTest->fetch(PDO::FETCH_ASSOC);
			
			if(empty($rowStaff) && $rowStaff ==""){
				if(empty($rowUserTest) && $rowUserTest==""){
					$arrayResult['RESPONSE_MESSAGE'] = "ขออภัยในความไม่สะดวก ขณะนี้อยู่ในช่วงการทดสอบ ท่านไม่ได้รับสิทธิ์ในการทดสอบ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			
			}
			
			if(isset($dataComing["expire_date"])){
				if(date('YmdHis',strtotime($dataComing["expire_date"])) <= date('YmdHis')){
					$arrayResult['RESPONSE_CODE'] = "WS0131";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
			$checkMemberRegistered = $conmysql->prepare("SELECT line_token FROM gcmemberaccount WHERE member_no = :member_no");
			$checkMemberRegistered->execute([':member_no' => $payload["member_no"]]);
			$rowToken = $checkMemberRegistered->fetch(PDO::FETCH_ASSOC);
			if(isset($rowToken["line_token"]) && $rowToken["line_token"] != ""){
				$arrayResult['RESPONSE_CODE'] = "WS0130";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}else{
				$updateLine = $conmysql->prepare("UPDATE gcmemberaccount SET line_token = :line_token WHERE member_no = :member_no");
				if($updateLine->execute([
					':line_token' => $dataComing["line_id"],
					':member_no' => $payload["member_no"]
				])){
					$messageResponse = "ผูกบัญชีสำเร็จแล้ว มาใช้งานกันเถอะ";
					$dataPrepare = $lineLib->prepareMessageText($messageResponse);
					$arrPostData["messages"] = $dataPrepare;
					$arrPostData["to"] = $dataComing["line_id"];
					$dataSendLib = $lineLib->sendPushLineBot($arrPostData);
					$arrayResult['RESULT'] = TRUE;
					$arrayResult['CALLBACK_URL'] = "line://ti/p/".$configLine["LINE_ID"];
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS1045";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
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
