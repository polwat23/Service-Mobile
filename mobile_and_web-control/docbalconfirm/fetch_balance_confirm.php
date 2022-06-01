<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
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
		
		$getBalanceMaster = $conmssql->prepare("SELECT TOP 1 BALANCE_DATE FROM YRCONFIRMMASTER WHERE MEMBER_NO = :member_no ORDER BY BALANCE_DATE DESC");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrDetail = array();
		$arrHeader["full_name"] = $rowMemberInfo["PRENAME_SHORT"].$rowMemberInfo["MEMB_NAME"]." ".$rowMemberInfo["MEMB_SURNAME"];
		$arrHeader["member_no"] = $member_no;
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
		$arrHeader["date_confirm_raw"] = date('Ymd',strtotime($rowBalMaster["BALANCE_DATE"]));
		
		$getBalStatus = $conmysql->prepare("SELECT confirm_date,confirm_flag,confirmlon_list, confirmshr_list, balance_date, remark, url_path FROM gcconfirmbalancelist WHERE member_no = :member_no and balance_date = :balance_date and is_use = '1'");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["balance_date"]) && $rowBalStatus["balance_date"] != ""){
			$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สมาชิก ".$member_no." ตามที่ทาง 
			สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d m Y').
			' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
			$arrayResult['REPORT_URL'] = $rowBalStatus["url_path"];
			$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalStatus["balance_date"]));
			$arrayResult['CONFIRM_DATE'] = $lib->convertdate($rowBalStatus["confirm_date"],'d m Y',true);
			$arrayResult['CONFIRMLON_LIST'] = $rowBalStatus["confirmlon_list"];
			$arrayResult['CONFIRMSHR_LIST'] = $rowBalStatus["confirmshr_list"];
			$arrayResult['CONFIRM_FLAG'] = json_decode($rowBalStatus["confirm_flag"]);
			$arrayResult['REMARK'] = $rowBalStatus["remark"];
			$arrayResult['IS_CONFIRM'] = TRUE;
			$arrayResult['IS_SKIP_REPORT'] = TRUE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$getBalanceDetail = $conmssql->prepare("SELECT SEQ_NO,FROM_SYSTEM,BIZZACCOUNT_NO,BALANCE_AMT,BIZZTYPE_CODE FROM YRCONFIRMSTATEMENT WHERE MEMBER_NO = :member_no AND BALANCE_DATE = :balance_date");
			$getBalanceDetail->execute([
				':member_no' => $member_no,
				':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
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
			if(isset($rowBalMaster["BALANCE_DATE"])){
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
					
				include('form_confirm_balance.php');
				$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
				if($arrayPDF["RESULT"]){
					$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สมาชิก ".$member_no." ตามที่ทาง 
					สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d m Y').
					' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
					
					$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
					$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
					$arrayResult['IS_CONFIRM'] = FALSE;
					$arrayResult['IS_SKIP_REPORT'] = TRUE;
					$arrayResult['IS_OTP'] = FALSE;
					$arrayResult['RESULT'] = TRUE;
					
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0044";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['IS_CONFIRM'] = FALSE;
				$arrayResult['RESULT'] = TRUE;
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