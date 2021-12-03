<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBalStatus = $conmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and confirmation = :confirmdate");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':confirmdate' => date('Y-m-d',strtotime($dataComing["balance_date"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["confirm_status"]) && $rowBalStatus["confirm_status"] != ""){
			$arrayResult['RESPONSE_CODE'] = "WS0097";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getMemberInfo = $conmssql->prepare("SELECT mp.PRENAME_SHORT,mb.MEMB_NAME,mb.MEMB_SURNAME,mb.BIRTH_DATE,mb.CARD_PERSON,
													mb.MEMBER_DATE,mup.POSITION_DESC,mg.MEMBGROUP_DESC,mt.MEMBTYPE_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFPOSITION mup ON mb.POSITION_CODE = mup.POSITION_CODE
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													WHERE mb.member_no = :member_no");
		$getMemberInfo->execute([':member_no' => $member_no]);
		$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
		
		$arrHeader = array();
		$arrDetail = array();
		$arrHeader["full_name"] = $rowMemberInfo["PRENAME_SHORT"].$rowMemberInfo["MEMB_NAME"]." ".$rowMemberInfo["MEMB_SURNAME"];
		$arrHeader["member_no"] = $member_no;
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowMemberInfo["BALANCE_DATE"])),'d M Y');
		$arrConfirmGroup = array();
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "SHARE";
		$arrConfirm["CONFIRM_DESC"] = "ทุนเรือนหุ้น";
		$arrConfirm["CONFIRM_SUB_VALUE"] = "37500";
		$arrConfirm["CONFIRM_VALUE"] = "375000";
		$arrConfirm["CONFIRM_DATA"] = "37,500.00 หุ้น  จำนวนเงิน 375,000.00";
		$arrConfirmGroup[] = $arrConfirm;
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "EMERLOAN";
		$arrConfirm["CONFIRM_DESC"] = "เงินกู้ฉุกเฉิน";
		$arrConfirm["CONFIRM_VALUE"] = "375500";
		$arrConfirm["CONFIRM_DATA"] = "จำนวนเงิน 375,500.00";
		$arrConfirmGroup[] = $arrConfirm;
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "LOAN";
		$arrConfirm["CONFIRM_DESC"] = "เงินกู้สามัญ";
		$arrConfirm["CONFIRM_VALUE"] = "5375500";
		$arrConfirm["CONFIRM_DATA"] = "จำนวนเงิน 5,375,500.00";
		$arrConfirmGroup[] = $arrConfirm;
		
		$arrayResult['CONFIRM_LIST'] = $arrConfirmGroup;
		$arrDetail['CONFIRM_LIST'] = $arrConfirmGroup;
		$arrDetail['CONFIRM_FLAG'] = $dataComing["confirm_flag"];
		$arrDetail['CONFIRM_REASON'] = $dataComing["remark"];
		
		/*$FlagComfirm = $conmysql->prepare("INSERT INTO confirm_balance(member_no,fullname,confirmation,confirm_status,reason,membgroup_code,department_code,position_code,comfirmdate,ip)
											VALUES(:member_no,:name,:balance_date,:confirm_flag,:remark,:membgroup,:depart_code,' ',NOW(),:ip)");*/
		/*if($FlagComfirm->execute([
			':member_no' => $member_no,
			':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
			':confirm_flag' => $dataComing["confirm_flag"],
			':remark' => 'MobileApp / '.$dataComing["remark"],
			':membgroup' => $rowname["MEMBGROUP_CODE"],
			':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
			':ip' => $dataComing["ip_address"]
		])){*/
		if(true){
			include('form_confirm_balance.php');
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['CONFIRM_FLAG'] = $dataComing["confirm_flag"];
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1038",
				":error_desc" => "Update ลงตาราง  cmconfirmmaster ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $member_no,
					':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':confirm_flag' => $dataComing["confirm_flag"],
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':membgroup' => $rowname["MEMBGROUP_CODE"],
					':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
					':ip' => $dataComing["ip_address"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
				':member_no' => $member_no,
				':name' => $rowname["PRENAME_DESC"].$rowname["MEMB_NAME"].' '.$rowname["MEMB_SURNAME"],
				':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				':confirm_flag' => $dataComing["confirm_flag"],
				':remark' => 'MobileApp / '.$dataComing["remark"],
				':membgroup' => $rowname["MEMBGROUP_CODE"],
				':depart_code' => isset($rowname["DEPART_GROUP"]) && $rowname["DEPART_GROUP"] != "" ? $rowname["DEPART_GROUP"] : ' ',
				':ip' => $dataComing["ip_address"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1038";
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