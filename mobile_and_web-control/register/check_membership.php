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
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	if($member_no != '002053' && $member_no != '002142' && $member_no != '002327' && $member_no != '002369' && $member_no != '002430'
	&& $member_no != '002571' && $member_no != '002684' && $member_no != '003629' && $member_no != '003630' && $member_no != '004039'
	&& $member_no != '004330' && $member_no != '004535' && $member_no != '004713' && $member_no != '005076' && $member_no != '005171'
	&& $member_no != '005367' && $member_no != '005379' && $member_no != '005411' && $member_no != '005608' && $member_no != '005745'
	&& $member_no != '005875' && $member_no != '005918' && $member_no != '008167' && $member_no != '008457' && $member_no != '008877'
	&& $member_no != '008878' && $member_no != '009364' && $member_no != '009568' && $member_no != '009810' && $member_no != '009811'
	&& $member_no != '011004' && $member_no != '011074' && $member_no != '012277' && $member_no != '013358' && $member_no != '013550'
	&& $member_no != '013744' && $member_no != '015396' && $member_no != '015457' && $member_no != '015949' && $member_no != '016477'
	&& $member_no != '016714' && $member_no != '000257' && $member_no != '000534' && $member_no != '000803' && $member_no != '001880'
	&& $member_no != '002287' && $member_no != '002772' && $member_no != '003249' && $member_no != '003885' && $member_no != '004626'
	&& $member_no != '006727' && $member_no != '007504' && $member_no != '003822' && $member_no != '005167' && $member_no != '007142'
	&& $member_no != '003704'){
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
	$checkMember = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
	$checkMember->execute([':member_no' => $member_no]);
	if($checkMember->rowCount() > 0){
		$arrayResult['RESPONSE_CODE'] = "WS0020";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}else{
		$checkValid = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mb.resign_status,mp.prename_desc,trim(mb.card_person) as card_person
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no and mb.member_status = '1'");
		$checkValid->execute([
			':member_no' => $member_no
		]);
		$rowMember = $checkValid->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMember["MEMB_NAME"])){
			if($rowMember["RESIGN_STATUS"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0051";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($rowMember["CARD_PERSON"] != $dataComing["id_card"]){
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