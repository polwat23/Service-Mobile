<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveGuarantee')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrContract = array();
		
		$getReqloanColl = $conmssql->prepare("select ln.MEMBER_NO , ln.LOANREQUEST_DOCNO  
											  FROM  lnreqloancoll lnc  LEFT JOIN   lnreqloan ln ON lnc.loanrequest_docno = ln.loanrequest_docno
											  WHERE lnc.ref_collno  = :member_no AND loanrequest_status ='1'");
		$getReqloanColl->execute([':member_no' => $member_no]);
		while($rowReqloanColl = $getReqloanColl->fetch(PDO::FETCH_ASSOC)){
			$arrayColl = array();
			$getreqLoan = $conmssql->prepare("SELECT ln.MEMBER_NO , mp.PRENAME_DESC , mb.MEMB_NAME , mb.MEMB_SURNAME, ln.loanrequest_docno as REQ_NO , 
											lnt.loantype_desc as TYPE_DESC, ln.loanrequest_amt  as APPROVE_AMT 
											FROM lnreqloan ln  LEFT JOIN lnloantype lnt ON ln.loantype_code = lnt.loantype_code
											LEFT JOIN mbmembmaster mb ON ln.member_no = mb.member_no
											LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE ln.loanrequest_status = 1  AND ln.member_no= :member_no AND ln.loanrequest_docno = :loanrequest_docno ");
			$getreqLoan->execute([':member_no' => $rowReqloanColl["MEMBER_NO"],
								  ':loanrequest_docno' => $rowReqloanColl["LOANREQUEST_DOCNO"]
			]);
			$rowreqLoan = $getreqLoan->fetch(PDO::FETCH_ASSOC);
			$arrContract["MEMBER_NO"] = $rowreqLoan["MEMBER_NO"];
			$arrContract["REQ_NO"] = $rowreqLoan["REQ_NO"];
			$arrContract["FULL_NAME"] = $rowreqLoan["PRENAME_DESC"].$rowreqLoan["MEMB_NAME"] .' '.$rowreqLoan["MEMB_SURNAME"];
			$arrContract["TYPE_DESC"] = $rowreqLoan["TYPE_DESC"];
			$arrContract["APPROVE_AMT"] = $rowreqLoan["APPROVE_AMT"];
	
			$arrayAvarTar = $func->getPathpic($rowreqLoan["MEMBER_NO"]);
			$arrContract["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
			$arrContract["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
			$arrayColl[] = $arrContract;
		}
		$arrayResult['REQ_LIST'] = $arrayColl;
		$arrayResult['RESULT'] = TRUE;
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
