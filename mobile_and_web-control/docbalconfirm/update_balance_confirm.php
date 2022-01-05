<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBalStatus = $conmysql->prepare("SELECT balance_date FROM gcconfirmbalancelist WHERE member_no = :member_no and balance_date = :balance_date and is_use = '1'");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => $dataComing["balance_date"]
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["balance_date"]) && $rowBalStatus["balance_date"] != ""){
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
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($dataComing["balance_date"])),'d M Y');
		$arrHeader["date_confirm_raw"] = date('Ymd',strtotime($dataComing["balance_date"]));
		$getBalanceDetail = $conmssql->prepare("SELECT SEQ_NO,FROM_SYSTEM,BIZZACCOUNT_NO,BALANCE_AMT,BIZZTYPE_CODE FROM YRCONFIRMSTATEMENT WHERE MEMBER_NO = :member_no AND BALANCE_DATE = :balance_date");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' => $dataComing["balance_date"]
		]);
		$share_amt = 0;
		$loan_01_amt = 0;
		$loan_02_amt = 0;
		$count = 0;
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			if($rowBalDetail["FROM_SYSTEM"] == "SHR"){
				$share_amt += $rowBalDetail["BALANCE_AMT"];
			}else if($rowBalDetail["FROM_SYSTEM"] == "LON"){
				if(substr($rowBalDetail["BIZZTYPE_CODE"],0,1) == "1"){
					$loan_01_amt += $rowBalDetail["BALANCE_AMT"];
				}else if(substr($rowBalDetail["BIZZTYPE_CODE"],0,1) == "2"){
					$loan_02_amt += $rowBalDetail["BALANCE_AMT"];
				}
			}
		}
		$arrConfirmGroup = array();
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "SHARE";
		$arrConfirm["CONFIRM_DESC"] = "ทุนเรือนหุ้น";
		$arrConfirm["CONFIRM_SUB_VALUE"] = $share_amt / 10;
		$arrConfirm["CONFIRM_VALUE"] = $share_amt;
		$arrConfirm["CONFIRM_DATA"] = number_format($share_amt / 10,2)." หุ้น  จำนวนเงิน ".number_format($share_amt,2);
		$arrConfirmGroup[] = $arrConfirm;
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "EMERLOAN";
		$arrConfirm["CONFIRM_DESC"] = "เงินกู้ฉุกเฉิน";
		$arrConfirm["CONFIRM_VALUE"] = $loan_01_amt;
		$arrConfirm["CONFIRM_DATA"] = "จำนวนเงิน ".number_format($loan_01_amt,2);
		$arrConfirmGroup[] = $arrConfirm;
		$arrConfirm = array();
		$arrConfirm["CONFIRM_TYPE"] = "LOAN";
		$arrConfirm["CONFIRM_DESC"] = "เงินกู้สามัญ";
		$arrConfirm["CONFIRM_VALUE"] = $loan_02_amt;
		$arrConfirm["CONFIRM_DATA"] = "จำนวนเงิน ".number_format($loan_02_amt,2);
		$arrConfirmGroup[] = $arrConfirm;
		
		$arrayResult['CONFIRM_LIST'] = $arrConfirmGroup;
		$arrDetail['CONFIRM_LIST'] = $arrConfirmGroup;
		$arrDetail['CONFIRM_REASON'] = $dataComing["remark"];
		$arrDetail['CONFIRM_FLAG'] = $dataComing["confirm_flag"];
		
		include('form_confirm_balance.php');
		$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail,true);
		$FlagComfirm = $conmysql->prepare("INSERT INTO gcconfirmbalancelist(member_no, confirmlon_list, confirmshr_list, balance_date, url_path, remark, confirm_flag) 
						VALUES (:member_no, :confirmlon_list, :confirmshr_list, :balance_date, :url_path, :remark, :confirm_flag)");
		if($FlagComfirm->execute([
			':member_no' => $member_no,
			':confirmlon_list' => $dataComing['lon_confirm_root_'],
			':confirmshr_list' => $dataComing['shr_confirm_root_'],
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
			':url_path' => $config["URL_SERVICE"].$arrayPDF["PATH"],
			':remark' => $dataComing["remark"],
			':confirm_flag' => json_encode($dataComing["confirm_flag"]),
		])){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1038",
				":error_desc" => "Update ลงตาราง  gcconfirmbalancelist ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $member_no,
					':confirmlon_list' => $dataComing['lon_confirm_root_'],
					':confirmshr_list' => $dataComing['shr_confirm_root_'],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':url_path' => $config["URL_SERVICE"].$arrayPDF["PATH"],
					':remark' => $dataComing["remark"],
					':confirm_flag' => json_encode($dataComing["confirm_flag"]),
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Update ลงตาราง  gcconfirmbalancelist ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
				':member_no' => $member_no,
				':confirmlon_list' => $dataComing['lon_confirm_root_'],
				':confirmshr_list' => $dataComing['shr_confirm_root_'],
				':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				':url_path' => $config["URL_SERVICE"].$arrayPDF["PATH"],
				':remark' => $dataComing["remark"],
				':confirm_flag' => json_encode($dataComing["confirm_flag"]),
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