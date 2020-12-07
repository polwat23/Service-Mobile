<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpYear = array();
		$arrGroupAss = array();
		$yearAss = 0;
		$fetchAssGrpYear = $conoracle->prepare("SELECT EXTRACT(YEAR FROM PAY_DATE) as ASSIST_YEAR,sum(assist_amt) as ASS_RECEIVED FROM asreqmaster 
												WHERE member_no = :member_no and coopbranch_id = :branch_id and req_status IN('31','32')
												GROUP BY EXTRACT(YEAR FROM PAY_DATE) ORDER BY ASSIST_YEAR DESC");
		$fetchAssGrpYear->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		while($rowAssYear = $fetchAssGrpYear->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["ASSIST_YEAR"] = $rowAssYear["ASSIST_YEAR"] + 543;
			$arrayYear["ASS_RECEIVED"] = number_format($rowAssYear["ASS_RECEIVED"],2);
			if($yearAss < $rowAssYear["ASSIST_YEAR"]){
				$yearAss = $rowAssYear["ASSIST_YEAR"];
			}
			$arrayGrpYear[] = $arrayYear;
		}
		if(isset($dataComing["ass_year"]) && $dataComing["ass_year"] != ""){
			$yearAss = $dataComing["ass_year"] - 543;
		}
		$assistretryRemain = 0;
		$fetchAssType = $conoracle->prepare("SELECT asm.ASSIST_DOCNO as ASSCONTRACT_NO,CASE WHEN asm.REQ_STATUS IN('31','32') THEN ASSIST_AMT ELSE 0 END as ASSIST_AMT,
												asm.APPROVE_DATE as PAY_DATE,asm.ASSISTAPPROVE_AMT as APPROVE_AMT,TRIM(asm.ASSISTTYPE_CODE) as ASSISTTYPE_CODE,
												ast.ASSISTTYPE_DESC,aur.STATUS_DESC 
												FROM asreqmaster asm  LEFT JOIN asucfassisttype ast
												ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE
												LEFT JOIN asucfreqstatus aur ON asm.REQ_STATUS = aur.REQ_STATUS
												WHERE asm.ASSISTTYPE_CODE IS NOT NULL and asm.member_no = :member_no
												and asm.coopbranch_id = :branch_id and asm.REQ_STATUS NOT IN('-9','0','99')
												and EXTRACT(YEAR FROM asm.PAY_DATE) = :year");
		$fetchAssType->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"],
			':year' => $yearAss
		]);
		$arrGroupAss = array();
		while($rowAssType = $fetchAssType->fetch(PDO::FETCH_ASSOC)){
			if($rowAssType["ASSISTTYPE_CODE"] == '80'){
				$assistretryRemain += $rowAssType["ASSIST_AMT"];
			}
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssType["ASSIST_AMT"],2);
			$arrAss["APPROVE_AMT"] = number_format($rowAssType["APPROVE_AMT"],2);
			$arrAss["PAY_DATE"] = $lib->convertdate($rowAssType["PAY_DATE"],'d m Y');
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrOtherInfoAssist = array();
			$arrOtherInfoAssist["LABEL"] = "สถานะการจ่าย";
			$arrOtherInfoAssist["VALUE"] = $rowAssType["STATUS_DESC"];
			$arrAss["LIST_ASSIST"][] = $arrOtherInfoAssist;
			$arrGroupAss[] = $arrAss;
		}

		//member_info
		$member_date_count = 0;
		$member_age_count = 0;
		$share_last_period = 0;
		$memberInfo = $conoracle->prepare("SELECT mb.birth_date,mb.member_date,sh.sharestk_amt as SHARE_AMT,sh.LAST_PERIOD
													FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
													and mb.branch_id = sh.branch_id
													WHERE mb.member_no = :member_no and mb.member_status = '1' and mb.branch_id = :branch_id");
		$memberInfo->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$member_date_count = $lib->count_duration($rowMember["MEMBER_DATE"],"y");
		$birth_date = $lib->count_duration($rowMember["BIRTH_DATE"],"y");
		$share_amt = $rowMember["SHARE_AMT"];
		$share_last_period = $rowMember["LAST_PERIOD"];
		
		//สวัสดิการค่าทำศพ
		$fetchAssAsDead = $conoracle->prepare("SELECT money_amt from  ASTIMERANGEMONEY 
												WHERE RANGE_CODE = 'asdead'
												AND :year_count > start_time AND :year_count <= end_time");
		$fetchAssAsDead->execute([':year_count' => $member_date_count]);
		while($rowAssAsDead = $fetchAssAsDead->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["PAYPER_AMT"] = number_format($rowAssAsDead["MONEY_AMT"],2);
			$arrAss["ASSISTTYPE_DESC"] = 'สวัสดิการค่าทำศพ';
			$arrRightGroupAss[] = $arrAss;
		}
		//สวัสดิการเงินสงเคราะห์ถึงแก่กรรม 
		$arrAss = array();
		$arrListAssist = array();
		$fetchAsDeadCoffin = $conoracle->prepare("SELECT money_amt from  ASTIMERANGEMONEY 
											WHERE RANGE_CODE = 'asdeadcoffin'
											AND :year_count BETWEEN start_time AND end_time");
		$fetchAsDeadCoffin->execute([':year_count' => $member_date_count]);
		while($rowAsDeadCoffin = $fetchAsDeadCoffin->fetch(PDO::FETCH_ASSOC)){
			$arrSubAssist = array();
			$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "จ่ายรายละ" : "Pay per man";
			$arrSubAssist["VALUE"] = number_format($rowAsDeadCoffin["MONEY_AMT"],2).($lang_locale == 'th' ? " บาท" : " Baht");
			$arrListAssist[] = $arrSubAssist;
			$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "รับไปแล้ว" : "Received";
			$arrSubAssist["VALUE"] = number_format($assistretryRemain,2).($lang_locale == 'th' ? " บาท" : " Baht");
			$arrListAssist[] = $arrSubAssist;
			$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "คงเหลือรอจ่าย" : "Remain can receive";
			$arrSubAssist["VALUE"] = number_format($rowAsDeadCoffin["MONEY_AMT"] - $assistretryRemain,2).($lang_locale == 'th' ? " บาท" : " Baht");
			$arrListAssist[] = $arrSubAssist;
		}
		$arrAss["ASSISTTYPE_DESC"] = "สวัสดิการเงินสงเคราะห์ถึงแก่กรรม";
		$arrAss["TYPE_DISPLAY"] = "array";
		$arrAss["LIST_ASSIST"] = $arrListAssist;
		$arrRightGroupAss[] = $arrAss;
		//สวัสดิการคู่สมรส/ทายาทเสียชีวิต 
		$fetchAssCouple = $conoracle->prepare("SELECT envvalue from CMENVIRONMENTVAR where ENVCODE = 'couple'");
		$fetchAssCouple->execute();
		while($rowAssCouple = $fetchAssCouple->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["APPROVE_AMT"] = number_format($rowAssCouple["ENVVALUE"],2);
			$arrAss["ASSISTTYPE_DESC"] = 'สวัสดิการคู่สมรส/ทายาทเสียชีวิต';
			$arrRightGroupAss[] = $arrAss;
		}
		
		//สวัสดิการเงินช่วยเหลือค่ารักษาพยาบาล 
		$arrAss = array();
		$arrListAssist = array();
		$fetchAssHospital = $conoracle->prepare("SELECT envvalue, envcode from CMENVIRONMENTVAR 
											WHERE envcode IN ('hospital','hospitalrate','hospitalrateday','hospitalday')");
		$fetchAssHospital->execute();
		while($rowAssHospital = $fetchAssHospital->fetch(PDO::FETCH_ASSOC)){
			$arrSubAssist = array();
			if($rowAssHospital["ENVCODE"] == 'hospital'){
				$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "เหมาจ่ายต่อคืน" : "Pay per night";
				$arrSubAssist["VALUE"] = number_format($rowAssHospital["ENVVALUE"],2).($lang_locale == 'th' ? " บาท" : " Baht");
				$arrListAssist[] = $arrSubAssist;
			}else if($rowAssHospital["ENVCODE"] == 'hospitalrate'){
				$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "อัตราเหมาจ่ายสูงสุดต่อครั้ง" : "Maximum rate of payment per times";
				$arrSubAssist["VALUE"] =  number_format($rowAssHospital["ENVVALUE"],2).($lang_locale == 'th' ? " บาท" : " Baht");
				$arrListAssist[] = $arrSubAssist;
			}else if($rowAssHospital["ENVCODE"] == 'hospitalrateday'){
				$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "จำนวนครั้งขอเงินช่วยเหลือได้สูงสุดต่อปี" : "Maximum times of money assist per year";
				$arrSubAssist["VALUE"] = $rowAssHospital["ENVVALUE"].($lang_locale == 'th' ? " ครั้ง" : " Times");
				$arrListAssist[] = $arrSubAssist;
			}else if($rowAssHospital["ENVCODE"] == 'hospitalday'){
				$arrSubAssist["LABEL"] = $lang_locale == 'th' ? "ขอเงินช่วยเหลือภายใน" : "Request money assist in";
				$arrSubAssist["VALUE"] =  $rowAssHospital["ENVVALUE"].($lang_locale == 'th' ? " วัน" : " Days");
				$arrListAssist[] = $arrSubAssist;
			}
		}
		$arrAss["ASSISTTYPE_DESC"] = "สวัสดิการเงินช่วยเหลือค่ารักษาพยาบาล";
		$arrAss["TYPE_DISPLAY"] = "array";
		$arrAss["LIST_ASSIST"] = $arrListAssist;
		$arrRightGroupAss[] = $arrAss;
		//บำเหน็จ 25 
		if($member_date_count >= 25 && $share_amt > 0){
			$fetchAsgrt25 = $conoracle->prepare("SELECT money_amt from ASTIMERANGEMONEY 
												where RANGE_CODE = 'asgrt25'
												AND :share_amt >= start_time AND :share_amt < end_time");
			$fetchAsgrt25->execute([':share_amt' => $share_amt]);
			while($rowAsgrt25 = $fetchAsgrt25->fetch(PDO::FETCH_ASSOC)){
				$arrAss = array();
				$arrAss["PAYPER_AMT"] = number_format($rowAsgrt25["MONEY_AMT"],2);
				$arrAss["ASSISTTYPE_DESC"] = "บำเหน็จ 25 ( เป็นสมาชิก สอ.มศว ครบ 25 ปี )";
				$arrRightGroupAss[] = $arrAss;
			}
		}
		
		//บำเหน็จ 60 
		if($member_date_count >= 60 && $share_last_period > 0){
			$fetchAsgrt60 = $conoracle->prepare("SELECT money_amt from ASTIMERANGEMONEY 
												where RANGE_CODE = 'asgrt60'
												AND :share_last_period >= start_time AND :share_last_period < end_time");
			$fetchAsgrt60->execute([':share_last_period' => $share_last_period]);
			while($rowAsgrt60 = $fetchAsgrt60->fetch(PDO::FETCH_ASSOC)){
				$arrAss = array();
				$arrAss["PAYPER_AMT"] = number_format($rowAsgrt60["MONEY_AMT"],2);
				$arrAss["ASSISTTYPE_DESC"] = 'บำเหน็จ 60 (สมาชิกอายุครบ 60 ปี )';
				$arrRightGroupAss[] = $arrAss;
			}
		}
		//บำเหน็จเกื้อกูลสมาชิกอาวุโส (เป็นสมาชิก 20 ปีขึ้นไป)
		if($member_date_count >= 20){
			$fetchAssRetry = $conoracle->prepare("SELECT envvalue from CMENVIRONMENTVAR where ENVCODE = :member_age");
			$fetchAssRetry->execute([':member_age' => $birth_date]);
			while($rowAssRetry = $fetchAssRetry->fetch(PDO::FETCH_ASSOC)){
				$arrAss = array();
				$arrAss["APPROVE_AMT"] = number_format($rowAssRetry["ENVVALUE"],2);
				$arrAss["ASSISTTYPE_DESC"] = 'บำเหน็จเกื้อกูลสมาชิกอาวุโส (เป็นสมาชิก 20 ปีขึ้นไป)';
				$arrRightGroupAss[] = $arrAss;
			}
		}
		//สวัสดิการทุนการศึกษา
		$arrayResult["IS_STM"] = FALSE;
		$arrayResult["YEAR"] = $arrayGrpYear;
		$arrayResult["ASSIST"] = $arrGroupAss;
		$arrayResult["RIGHTS_ASSIST"] = $arrRightGroupAss;
		$arrayResult["RESULT"] = TRUE;
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