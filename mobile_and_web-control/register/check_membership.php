<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','id_card','api_token','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	$member_no = $dataComing["member_no"];
	$checkMember = $conmssql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
	$checkMember->execute([':member_no' => $member_no]);
	$rowCheckMember = $checkMember->fetch(PDO::FETCH_ASSOC);
	if(isset($rowCheckMember["member_no"])){
		$arrayResult['RESPONSE_CODE'] = "WS0020";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}else{
		$checkValid = $conmssqlcoop->prepare("SELECT firstname as MEMB_NAME,lastname as MEMB_SURNAME,status as RESIGN_STATUS
											,prefixname as PRENAME_DESC,rtrim(ltrim(id_number)) as CARD_PERSON
											FROM cocooptation
											WHERE member_id = :member_no");
		$checkValid->execute([
			':member_no' => $member_no
		]);
		$rowMember = $checkValid->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMember["MEMB_NAME"])){
			if($rowMember["RESIGN_STATUS"] == 'RE'){
				$arrayResult['RESPONSE_CODE'] = "WS0051";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if(substr(preg_replace('/-/','',$rowMember["CARD_PERSON"]),9,4) != $dataComing["id_card"]){
				$arrayResult['RESPONSE_CODE'] = "WS0060";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['MEMBER_NO'] = $member_no;
			$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
			$arrayResult['MEMBER_FULLNAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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