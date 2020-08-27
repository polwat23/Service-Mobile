<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayResult = array();
		$arrayGroupLoan = array();
		$getUcollwho = $conoracle->prepare("SELECT
											LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
											LNTYPE.loantype_desc as TYPE_DESC,
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
											LCM.MEMBER_NO AS MEMBER_NO,
											NVL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT,
											LCM.principal_balance as LOAN_BALANCE,
											(sh.sharestk_amt * 10) as SHARE_AMT
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											LEFT JOIN shsharemaster sh ON LCM.MEMBER_NO = sh.member_no
											WHERE
											LCM.CONTRACT_STATUS > 0
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.REF_COLLNO = :member_no");
		$getUcollwho->execute([':member_no' => $member_no]);
		while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
			$arrayColl = array();
			$contract_no = $rowUcollwho["LOANCONTRACT_NO"];
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$arrayColl["CONTRACT_NO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$arrayColl["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$arrayColl["CONTRACT_NO"] = $contract_no;
			}
			$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
			$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
			$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
			$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
			$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
			$arrayColl["APPROVE_AMT"] = number_format($rowUcollwho["LOANAPPROVE_AMT"],2);
			$checkLoanCollAmt = $conoracle->prepare("SELECT COUNT(REF_COLLNO) as C_REF,COLL_PERCENT FROM lncontcoll 
																		WHERE loancontract_no = :loancontract_no and loancolltype_code = '01' and REF_COLLNO = :ref_collno GROUP BY COLL_PERCENT");
			$checkLoanCollAmt->execute([
				':loancontract_no' => $contract_no,
				':ref_collno' => $member_no
			]);
			$rowLoanColl = $checkLoanCollAmt->fetch(PDO::FETCH_ASSOC);
			$loanCollUseAmt = $rowUcollwho["LOAN_BALANCE"] - $rowUcollwho["SHARE_AMT"];
			if($rowLoanColl["C_REF"] > 1){
				$loanCollUseAmt = $loanCollUseAmt * $rowLoanColl["COLL_PERCENT"];
			}else{
				if($loanCollUseAmt < 0){
					$loanCollUseAmt = 0;
				}
			}
			$arrayColl["USECOLL_AMT"] = number_format($loanCollUseAmt,2);
			$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
			$arrayGroupLoan[] = $arrayColl;
		}
		$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
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