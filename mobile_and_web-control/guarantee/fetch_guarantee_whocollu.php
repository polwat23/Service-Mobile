<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayResult = array();
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$arrGroupAll = array();
			$arrGroupAllMember = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conoracle->prepare("SELECT NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN lncontcoll lnc ON lnm.loancontract_no = lnc.loancontract_no
												LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status > 0
												GROUP BY NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrGroupAll['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrGroupAll['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrGroupAll['CONTRACT_NO'] = $contract_no;
			$whocolluMember = $conoracle->prepare("SELECT
													MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME,
													LCC.REF_COLLNO AS MEMBER_NO			
												FROM
													LNCONTCOLL LCC LEFT JOIN MBMEMBMASTER MMB ON LCC.REF_COLLNO = MMB.MEMBER_NO
													LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
												WHERE
													LCC.LOANCOLLTYPE_CODE = '01'
													AND LCC.LOANCONTRACT_NO = :contract_no ");
			$whocolluMember->execute([':contract_no' => $contract_no]);
			while($rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC)){
				$arrMember = array();
				$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"]);
				$arrMember["AVATAR_PATH"] = $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"];
				$arrMember["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"];
				$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
				$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
				$arrGroupAllMember[] = $arrMember;
			}
			$arrGroupAll['MEMBER'] = $arrGroupAllMember;
			$arrayGroupLoan[] = $arrGroupAll;
			$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayGroupLoan = array();
			$getWhocollu = $conoracle->prepare("SELECT lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN lncontcoll lnc ON lnm.loancontract_no = lnc.loancontract_no
												LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
												and lnm.contract_status = '1'
                         						GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
				$arrGroupAll['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$arrGroupAll["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
				$whocolluMember = $conoracle->prepare("SELECT
														MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME,
														LCC.REF_COLLNO AS MEMBER_NO			
													FROM
														LNCONTCOLL LCC LEFT JOIN MBMEMBMASTER MMB ON LCC.REF_COLLNO = MMB.MEMBER_NO
														LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
													WHERE
														LCC.LOANCOLLTYPE_CODE = '01'
														AND LCC.LOANCONTRACT_NO = :contract_no ");
				$whocolluMember->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC)){
					$arrMember = array();
					$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"]);
					$arrMember["AVATAR_PATH"] = $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"];
					$arrMember["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"];
					$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
					$arrGroupAllMember[] = $arrMember;
				}
				$arrGroupAll['MEMBER'] = $arrGroupAllMember;
				$arrayGroupLoan[] = $arrGroupAll;
			}
			$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}
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