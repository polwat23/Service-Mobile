<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $payload["ref_memno"];
		$arrayResult = array();
		$arrayGroupLoan = array();
		
		$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["balance_status"] ||"0" ;
		if($Contractno == "0"){
			$getUcollwho = $conoracle->prepare("SELECT 
											LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
											LCC.REF_COLLNO,
											LCC.COLL_AMT,   
											LCC.BASE_AMT, 
											LNTYPE.loantype_desc as TYPE_DESC,
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_ENAME,
											LCM.MEMBER_NO AS MEMBER_NO,
											NVL(LCM.principal_balance,0) as LOAN_BALANCE,
											LCM.LAST_PERIODPAY as LAST_PERIOD,
											LCM.period_installment as PERIOD
											FROM
											LCCONTCOLL LCC LEFT JOIN LCCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lCCFLOANTYPE LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											WHERE LCM.CONTRACT_STATUS = 1
											AND LCC.LOANCOLLTYPE_CODE IN('01','02','03','04')
											AND LCC.REF_COLLNO = :member_no");
			$getUcollwho->execute([':member_no' => $member_no]);
			while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
				$arrayColl = array();
				$arrayColl["CONTRACT_NO"] = $rowUcollwho["LOANCONTRACT_NO"];
				$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
				$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
				$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
				$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
				$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
				$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["COLL_AMT"],2);
				$arrayColl["LAST_PERIOD"] = $rowUcollwho["LAST_PERIOD"].' / '.$rowUcollwho["PERIOD"];
				$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"];
				$arrayGroupLoan[] = $arrayColl;
			}
			$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');                                                       
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0114";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
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