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
		
		$getBalanceMaster = $conmssql->prepare("SELECT * FROM (SELECT mp.PRENAME_DESC,cfm.MEMB_NAME,cfm.MEMB_SURNAME,cfm.MEMBGROUP_CODE,
												md.SHORT_NAME1 as DEPARTMENT,md.SHORT_NAME2 as DEPART_GROUP,cfm.BALANCE_DATE,cfm.CONFIRM_FLAG
												FROM cmconfirmmaster cfm LEFT JOIN mbmembmaster mb ON cfm.MEMBER_NO = mb.MEMBER_NO
												LEFT JOIN mbucfdepartment md ON mb.department_code = md.department_code
												LEFT JOIN mbucfprename mp ON cfm.PRENAME_CODE = mp.PRENAME_CODE
												WHERE cfm.member_no = :member_no
												ORDER BY cfm.balance_date DESC) WHERE rownum <= 1");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrDetail = array();
		$arrHeader["full_name"] = $rowMemberInfo["PRENAME_SHORT"].$rowMemberInfo["MEMB_NAME"]." ".$rowMemberInfo["MEMB_SURNAME"];
		$arrHeader["member_no"] = $member_no;
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
		$getBalanceDetail = $conmssql->prepare("SELECT (CASE WHEN cfb.CONFIRMTYPE_CODE = 'DEP'
												THEN dp.DEPTTYPE_DESC
												WHEN cfb.CONFIRMTYPE_CODE = 'LON'
												THEN ln.LOANTYPE_DESC
												WHEN cfb.CONFIRMTYPE_CODE = 'ASS'
												THEN 'กองทุนสวัสดิการสมาชิก'
												ELSE 'ทุนเรือนหุ้น' END) AS DEPTTYPE_DESC,
												(CASE WHEN cfb.CONFIRMTYPE_CODE = 'DEP' OR cfb.CONFIRMTYPE_CODE = 'LON'
												THEN cfb.REF_MASTNO
												ELSE '' END) as DEPTACCOUNT_NO,cfb.BALANCE_AMT,cfb.CONFIRMTYPE_CODE
												FROM cmconfirmbalance cfb LEFT JOIN dpdepttype dp ON cfb.SHRLONTYPE_CODE = dp.DEPTTYPE_CODE
												LEFT JOIN lnloantype ln ON cfb.SHRLONTYPE_CODE = ln.LOANTYPE_CODE
												WHERE cfb.member_no = :member_no and cfb.BALANCE_DATE = to_date(:balance_date,'YYYY-MM-DD')
												ORDER BY cfb.CONFIRMTYPE_CODE ASC");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$formatDept = $func->getConstant('dep_format');
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$arrBalDetail["TYPE_DESC"] = $rowBalDetail["DEPTTYPE_DESC"];
			if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
				if(array_search($rowBalDetail["DEPTTYPE_DESC"],array_column($arrDetail,'TYPE_DESC')) === False){
					$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
					$arrDetail[] = $arrBalDetail;
				}else{
					$arrDetail[array_search($rowBalDetail["DEPTTYPE_DESC"],array_column($arrDetail,'TYPE_DESC'))]["BALANCE_AMT"] += $rowBalDetail["BALANCE_AMT"];
				}
			}else{
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
				$arrBalDetail["DEPTACCOUNT_NO"] = $rowBalDetail["DEPTACCOUNT_NO"];
				$arrDetail[] = $arrBalDetail;
			}
		}
		foreach($arrDetail as $key => $value){
			$arrDetail[$key]["BALANCE_AMT"] = number_format($value["BALANCE_AMT"],2);
		}
		if(isset($rowBalMaster["MEMB_NAME"]) && sizeof($arrDetail) > 0 || true){
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
				
			include('form_confirm_balance.php');
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			if($arrayPDF["RESULT"]){
				$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สมาชิก ".$member_no." ตามที่ทาง 
				สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d m Y').
				' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
				
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
				$arrayResult['IS_CONFIRM'] = FALSE;
				$arrayResult['IS_SKIP_REPORT'] = FALSE;
				$arrayResult['IS_OTP'] = TRUE;
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