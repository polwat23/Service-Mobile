<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGroupBNF = array();
		$getBeneficiary = $conoracle->prepare("SELECT mg.gain_name,mg.gain_surname,mc.gain_concern as gain_concern,
												mp.PRENAME_DESC as PRENAME_SHORT,mg.gain_address,mgm.condition as REMARK
												FROM mbgainmaster mgm LEFT JOIN mbgaindetail mg ON mgm.member_no = mg.member_no
												LEFT JOIN mbucfgainconcern mc ON mg.CONCERN_CODE = mc.CONCERN_CODE
												LEFT JOIN mbucfprename mp ON mg.prename_code = mp.prename_code
												WHERE mg.member_no = :member_no and mg.branch_id = :branch_id and mg.gain_status = '1' ORDER BY mg.seq_no ASC");
		$getBeneficiary->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_SHORT"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
			$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["GAIN_ADDR"]);
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
			$arrBenefit["TYPE_PERCENT"] = 'text';
			$arrBenefit["PERCENT_TEXT"] = isset($rowBenefit["REMARK"]) && $rowBenefit["REMARK"] != "" ? $rowBenefit["REMARK"] : "แบ่งให้เท่า ๆ กัน";
			$arrBenefit["PERCENT"] = filter_var($rowBenefit["REMARK"], FILTER_SANITIZE_NUMBER_INT);
			$arrGroupBNF[] = $arrBenefit;
		}
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
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