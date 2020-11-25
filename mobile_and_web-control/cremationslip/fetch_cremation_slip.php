<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationSlip')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$limit_period = $func->getConstant('limit_kpmonth');
		$arrayGroupCremation = array();
		
		// สมาคมฌาปนกิจสงเคราะห์ สอ.มศว จำกัด
		$getCremationSWU = $conoracle->prepare("select wcdm.deptaccount_no,wcdm.wfaccount_name,wcrm.recv_period,wcrm.receipt_no,wcds.deptslip_date,wcds.entry_date,wcds.operate_date,wcds.deptslip_amt, to_char( wcds.deptslip_date,'yyyy')+543 as YEAR
											from scobkmsvwf.wcdeptmaster wcdm 
											join scobkmsvwf.wcrecievemonth wcrm on wcrm.member_no  = wcdm.member_no
											join scobkmsvwf.WCDEPTSLIP wcds on wcrm.receipt_no = wcds.deptslip_no
											WHERE TRIM(wcdm.member_no) = :member_no and wcrm.wcitemtype_code = 'WPF'
											ORDER BY YEAR desc");
		$getCremationSWU->execute([
			':member_no' => $member_no
		]);
		$listSWU = array();
		$dataSWU = array();
		while($rowCremationSWU = $getCremationSWU->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrCremation["WFMEMBER_NO"] = $rowCremationSWU["DEPTACCOUNT_NO"];
			$arrCremation["WFACCOUNT_NAME"] = $rowCremationSWU["WFACCOUNT_NAME"];
			$arrCremation["RECV_PERIOD"] = null;
			//$rowCremationSWU["RECV_PERIOD"]
			$arrCremation["RECEIPT_NO"] = $rowCremationSWU["RECEIPT_NO"];
			$arrCremation["ENTRY_DATE"] = $rowCremationSWU["ENTRY_DATE"];
			$arrCremation["DEPTSLIP_DATE"] = $rowCremationSWU["DEPTSLIP_DATE"];
			$arrCremation["OPERATE_DATE"] = $rowCremationSWU["OPERATE_DATE"];
			$arrCremation["DEPTSLIP_AMT"] = $rowCremationSWU["DEPTSLIP_AMT"];
			$arrCremation["YEAR"] = $rowCremationSWU["YEAR"];
			$listSWU[] = $arrCremation;
		}
		$dataSWU["SLIP_DESC"] = "สมาคมฌาปนกิจสงเคราะห์ สอ.มศว จำกัด";
		$dataSWU["SLIP_TYPE"] = "SWU";
		$dataSWU["LIST"] = array();
		$dataSWU["LIST"] = $listSWU;
		$arrayGroupCremation[] = $dataSWU;
		
		// ครูไทย
		$getCremationSSOT = $conoracle->prepare("select mb.card_person,ssot.prename || ssot.deptaccount_name || ' ' || ssot.deptaccount_sname as member_name, trim( ssot.deptslipbranch_no ) as deptslipbranch_no,
											ssot.FEE_YEAR,ssot.fee_amt,ssot.deptslip_date,ssot.wfmember_no , to_char( ssot.deptslip_date,'yyyy')+543 as YEAR
											from mbmembmaster mb
											join scobkmsvwf.wcrecieve_ssot ssot on ssot.card_person = mb.card_person
											WHERE TRIM(mb.member_no) = :member_no
											ORDER BY YEAR desc");
		$getCremationSSOT->execute([
			':member_no' => $member_no
		]);
		$listSSOT = array();
		$dataSSOT = array();
		while($rowCremationSSOT = $getCremationSSOT->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrCremation["WFMEMBER_NO"] = $rowCremationSSOT["WFMEMBER_NO"];
			$arrCremation["WFACCOUNT_NAME"] = $rowCremationSSOT["WFACCOUNT_NAME"];
			$arrCremation["RECV_PERIOD"] = null;
			$arrCremation["RECEIPT_NO"] = $rowCremationSSOT["DEPTSLIPBRANCH_NO"];
			$arrCremation["ENTRY_DATE"] = null;
			$arrCremation["DEPTSLIP_DATE"] = $rowCremationSSOT["DEPTSLIP_DATE"];
			$arrCremation["OPERATE_DATE"] = null;
			$arrCremation["DEPTSLIP_AMT"] = $rowCremationSSOT["FEE_YEAR"]+$rowCremationSSOT["FEE_AMT"];
			$arrCremation["YEAR"] = $rowCremationSSOT["YEAR"];
			$listSSOT[] = $arrCremation;
		}
		$dataSSOT["SLIP_DESC"] = "สมาคมฌาปนกิจสงเคราะห์ ครูไทย จำกัด";
		$dataSSOT["SLIP_TYPE"] = "SSOT";
		$dataSSOT["LIST"] = array();
		$dataSSOT["LIST"] = $listSSOT;
		$arrayGroupCremation[] = $dataSSOT;
		
		// ftsc
		$getCremationFTSC = $conoracle->prepare("select mb.card_person,ftsc.prename || ftsc.deptaccount_name || ' ' || ftsc.deptaccount_sname as member_name, trim( ftsc.deptslipbranch_no ) as deptslipbranch_no,
											ftsc.FEE_YEAR,ftsc.fee_amt,ftsc.deptslip_date,ftsc.wfmember_no,ftsc.deptslip_no, to_char( ftsc.deptslip_date,'yyyy')+543 as YEAR
											from mbmembmaster mb
											join scobkmsvwf.wcrecieve_ftsc ftsc on TRIM(ftsc.card_person) = TRIM(mb.card_person)
											WHERE TRIM(mb.member_no) = :member_no
											ORDER BY YEAR desc");
		$getCremationFTSC->execute([
			':member_no' => $member_no
		]);
		$listFTSC = array();
		$dataFTSC = array();
		while($rowCremationFTSC = $getCremationFTSC->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrCremation["WFMEMBER_NO"] = $rowCremationFTSC["WFMEMBER_NO"];
			$arrCremation["WFACCOUNT_NAME"] = $rowCremationFTSC["WFACCOUNT_NAME"];
			$arrCremation["RECV_PERIOD"] = null;
			$arrCremation["RECEIPT_NO"] = $rowCremationFTSC["DEPTSLIPBRANCH_NO"];
			$arrCremation["ENTRY_DATE"] = null;
			$arrCremation["DEPTSLIP_DATE"] = $rowCremationFTSC["DEPTSLIP_DATE"];
			$arrCremation["OPERATE_DATE"] = null;
			$arrCremation["DEPTSLIP_AMT"] = $rowCremationFTSC["FEE_YEAR"]+$rowCremationFTSC["FEE_AMT"];
			$arrCremation["YEAR"] = $rowCremationFTSC["YEAR"];
			$listFTSC[] = $arrCremation;
		}
		$dataFTSC["SLIP_DESC"] = "สมาคมฌาปนกิจสงเคราะห์ ชสอ. จำกัด";
		$dataFTSC["SLIP_TYPE"] = "FTSC";
		$dataFTSC["LIST"] = array();
		$dataFTSC["LIST"] = $listFTSC;
		$arrayGroupCremation[] = $dataFTSC;
		
		$arrayResult['CREMATION_LIST'] = $arrayGroupCremation;
		$arrayResult['branch_id'] = $payload["branch_id"];
		$arrayResult['member_no'] = $member_no;
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