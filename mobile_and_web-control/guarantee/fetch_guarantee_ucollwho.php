<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getDataMember = $conoracle->prepare("SELECT ID_CARD,BR_NO FROM MEM_H_MEMBER WHERE account_id = :member_no");
		$getDataMember->execute([':member_no' => $member_no]);
		$rowDataMember = $getDataMember->fetch(PDO::FETCH_ASSOC);
		$arrayGroupLoan = array();
		$getUcollwho = $conoracle->prepare("SELECT
											LCC.LCONT_ID as LOANCONTRACT_NO,
											LNTYPE.L_TYPE_NAME as TYPE_DESC,
											PRE.PTITLE_NAME as PRENAME_DESC,MEMB.FNAME as MEMB_NAME,MEMB.LNAME as MEMB_SURNAME,
											LCM.ACCOUNT_ID AS MEMBER_NO,
											NVL(LCM.LCONT_AMOUNT_SAL,0) as LOAN_BALANCE,
											LCM.LCONT_MAX_INSTALL - LCM.LCONT_NUM_INST as LAST_PERIOD,
											LCM.LCONT_MAX_INSTALL as PERIOD
											FROM
											LOAN_T_GUAR LCC LEFT JOIN LOAN_M_CONTACT LCM ON  LCC.LCONT_ID = LCM.LCONT_ID
											LEFT JOIN MEM_H_MEMBER MEMB ON LCM.ACCOUNT_ID = MEMB.ACCOUNT_ID
											LEFT JOIN MEM_M_PTITLE PRE ON MEMB.ptitle_id = PRE.ptitle_id
											LEFT JOIN LOAN_M_TYPE_NAME LNTYPE  ON LCM.L_TYPE_CODE = LNTYPE.L_TYPE_CODE
											WHERE
											LCM.LCONT_STATUS_CONT IN('H','A')
											AND LCC.LG_SAL > 0
											AND LCC.MEM_ID = :id_card and LCC.BR_NO_OTHER = :br_no");
		$getUcollwho->execute([
			':id_card' => $rowDataMember["ID_CARD"],
			':br_no' => $rowDataMember["BR_NO"]
		]);
		while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
			$arrayColl = array();
			$arrayColl["CONTRACT_NO"] = $rowUcollwho["LOANCONTRACT_NO"];
			$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
			$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
			$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
			$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
			$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
			$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["LOAN_BALANCE"],2);
			$arrayColl["LAST_PERIOD"] = $rowUcollwho["LAST_PERIOD"].' / '.$rowUcollwho["PERIOD"];
			$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
			$arrayGroupLoan[] = $arrayColl;
		}
		$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
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