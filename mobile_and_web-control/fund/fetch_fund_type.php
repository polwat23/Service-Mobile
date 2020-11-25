<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpYear = array();
		$arrGroupAss = array();
		$yearAss = 0;
		
		//member_info
		$member_date_count = 0;
		$member_age_count = 0;
		$share_last_period = 0;
		$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
													mb.member_date,mb.position_desc,mg.membgroup_desc,mt.membtype_desc
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													WHERE mb.member_no = :member_no and mb.member_status = '1' and branch_id = :branch_id");
		$memberInfo->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$member_date_count = ($lib->count_duration($rowMember["MEMBER_DATE"],"m"))/12;
		$member_date_count = ($lib->count_duration($rowMember["MEMBER_DATE"],"m"))/12;
		
		
		//share
		$getSharemasterinfo = $conoracle->prepare("SELECT sharestk_amt as SHARE_AMT,LAST_PERIOD
													FROM shsharemaster WHERE member_no = :member_no and branch_id = :branch_id");
		$getSharemasterinfo->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		$share_amt = 0;
		if($rowMastershare){
			$share_amt = $rowMastershare["SHARE_AMT"];
			$share_last_period = $rowMastershare["LAST_PERIOD"];
		}
		
		
		//สวัสดิการค่าทำศพ
		$fetchAssAsDead = $conoracle->prepare("SELECT start_time, end_time, money_amt from  ASTIMERANGEMONEY 
												WHERE RANGE_CODE = 'asdead'
												AND :year_count > start_time AND :year_count <= end_time");
		$fetchAssAsDead->execute([':year_count' => $member_date_count]);
		while($rowAssAsDead = $fetchAssAsDead->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssAsDead["MONEY_AMT"],2);
			$arrAss["PAY_DATE"] = null;
			$arrAss["ASSISTTYPE_CODE"] = "ASDEAD";
			$arrAss["ASSISTTYPE_DESC"] = 'สวัสดิการเงินค่าทำศพ';
			//$arrAss["ASSCONTRACT_NO"] = null
			$arrGroupAss[] = $arrAss;
		}
		
		
		//สวัสดิการเงินสงเคราะห์ถึงแก่กรรม 
		$fetchAsDeadCoffin = $conoracle->prepare("SELECT start_time, end_time, money_amt from  ASTIMERANGEMONEY 
											WHERE RANGE_CODE = 'asdeadcoffin'
											AND :year_count >= start_time AND :year_count <= end_time");
		$fetchAsDeadCoffin->execute([':year_count' => $member_date_count]);
		while($rowAsDeadCoffin = $fetchAsDeadCoffin->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAsDeadCoffin["MONEY_AMT"],2);
			$arrAss["PAY_DATE"] = null;
			$arrAss["ASSISTTYPE_CODE"] = "ASDEADCOFFIN";
			$arrAss["ASSISTTYPE_DESC"] = 'สวัสดิการเงินสงเคราะห์ถึงแก่กรรม';
			//$arrAss["ASSCONTRACT_NO"] = null
			$arrGroupAss[] = $arrAss;
		}
		
		//สวัสดิการคู่สมรส/ทายาทเสียชีวิต 
		$fetchAssCouple = $conoracle->prepare("SELECT envvalue from CMENVIRONMENTVAR where ENVCODE = 'couple'");
		$fetchAssCouple->execute();
		while($rowAssCouple = $fetchAssCouple->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssCouple["ENVVALUE"],2);
			$arrAss["PAY_DATE"] = null;
			$arrAss["ASSISTTYPE_CODE"] = "COUPLE";
			$arrAss["ASSISTTYPE_DESC"] = 'สวัสดิการคู่สมรส/ทายาทเสียชีวิต';
			//$arrAss["ASSCONTRACT_NO"] = null
			$arrGroupAss[] = $arrAss;
		}
		
		//สวัสดิการเงินช่วยเหลือค่ารักษาพยาบาล 
		$fetchAssHospital = $conoracle->prepare("SELECT envvalue, envcode from CMENVIRONMENTVAR 
											WHERE envcode IN ('hospital','hospitalrate','hospitalrateday','hospitalday')");
		$fetchAssHospital->execute();
		/*
		while($rowAssHospital = $fetchAssHospital->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssHospital["ENVVALUE"],2);
			$arrAss["PAY_DATE"] = null;
			$arrAss["ASSISTTYPE_CODE"] = $rowAssHospital["ENVCODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssHospital["ENVCODE"] == "hospital" ? "สวัสดิการเงินช่วยเหลือค่ารักษาพยาบาล " : $rowAssHospital["ENVCODE"] == "hospital" ? "สวัสดิการเงินช่วยเหลือค่ารักษาพยาบาล " : ;
			//$arrAss["ASSCONTRACT_NO"] = null
			$arrGroupAss[] = $arrAss;
		}
		*/
		
		//บำเหน็จ 25 
		if($member_date_count >= 25 && $share_amt > 0){
			$fetchAsgrt25 = $conoracle->prepare("SELECT start_time , end_time, money_amt from  ASTIMERANGEMONEY 
												where RANGE_CODE = 'asgrt25'
												AND :share_amt >= start_time AND :year_count < end_time");
			$fetchAsgrt25->execute([':share_amt' => $share_amt]);
			while($rowAsgrt25 = $fetchAsgrt25->fetch(PDO::FETCH_ASSOC)){
				$arrAss = array();
				$arrAss["ASSIST_RECVAMT"] = number_format($rowAssCouple["MONEY_AMT"],2);
				$arrAss["PAY_DATE"] = null;
				$arrAss["ASSISTTYPE_CODE"] = "COUPLE";
				$arrAss["ASSISTTYPE_DESC"] = 'บำเหน็จ 25';
				//$arrAss["ASSCONTRACT_NO"] = null
				$arrGroupAss[] = $arrAss;
			}
		}
		
		
		//บำเหน็จ 60 
		if($member_date_count >= 25 && $share_last_period > 0){
			$fetchAsgrt60 = $conoracle->prepare("SELECT start_time , end_time, money_amt from  ASTIMERANGEMONEY 
												where RANGE_CODE = 'asgrt25'
												AND :share_last_period >= start_time AND :year_count < end_time");
			$fetchAsgrt60->execute([':share_last_period' => $share_last_period]);
			while($rowAsgrt60 = $fetchAsgrt60->fetch(PDO::FETCH_ASSOC)){
				$arrAss = array();
				$arrAss["ASSIST_RECVAMT"] = number_format($rowAssCouple["MONEY_AMT"],2);
				$arrAss["PAY_DATE"] = null;
				$arrAss["ASSISTTYPE_CODE"] = "COUPLE";
				$arrAss["ASSISTTYPE_DESC"] = 'บำเหน็จ 60';
				//$arrAss["ASSCONTRACT_NO"] = null
				$arrGroupAss[] = $arrAss;
			}
		}
		
		$fetchAssGrpYear = $conoracle->prepare("SELECT assist_year as ASSIST_YEAR,sum(ASSIST_AMT) as ASS_RECEIVED FROM assreqmaster 
												WHERE member_no = :member_no GROUP BY assist_year ORDER BY assist_year DESC");
		$fetchAssGrpYear->execute([':member_no' => $member_no]);
		while($rowAssYear = $fetchAssGrpYear->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["ASSIST_YEAR"] = $rowAssYear["ASSIST_YEAR"];
			$arrayYear["ASS_RECEIVED"] = number_format($rowAssYear["ASS_RECEIVED"],2);
			if($yearAss < $rowAssYear["ASSIST_YEAR"]){
				$yearAss = $rowAssYear["ASSIST_YEAR"];
			}
			$arrayGrpYear[] = $arrayYear;
		}
		if(isset($dataComing["ass_year"]) && $dataComing["ass_year"] != ""){
			$yearAss = $dataComing["ass_year"];
		}
		$fetchAssType = $conoracle->prepare("SELECT ast.ASSISTTYPE_DESC,ast.ASSISTTYPE_CODE,asm.ASSIST_DOCNO as ASSCONTRACT_NO,asm.ASSIST_AMT,asm.PAY_DATE
												FROM assreqmaster asm LEFT JOIN 
												assucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE and asm.coop_id = ast.coop_id 
												WHERE asm.member_no = :member_no 
												and asm.req_status = 1 and asm.assist_year = :year and asm.ref_slipno IS NOT NULL");
		$fetchAssType->execute([
			':member_no' => $member_no,
			':year' => $yearAss
		]);
		
		while($rowAssType = $fetchAssType->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssType["ASSIST_AMT"],2);
			$arrAss["PAY_DATE"] = $lib->convertdate($rowAssType["PAY_DATE"],'d m Y');
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrGroupAss[] = $arrAss;
		}
		$arrayResult["IS_STM"] = FALSE;
		$arrayResult["YEAR"] = $arrayGrpYear;
		$arrayResult["ASSIST"] = $arrGroupAss;
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