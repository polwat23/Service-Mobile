<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_WHOCOLLU"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_WHOCOLLU"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrayResult = array();
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$arrGroupAll = array();
			$arrGroupAllMember = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conoracle->prepare("SELECT NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN lncontcoll lnc ON lnm.loancontract_no = lnc.loancontract_no
												LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status = '1'
												GROUP BY NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch();
			$arrGroupAll['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrGroupAll['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrGroupAll['CONTRACT_NO'] = $dataComing["contract_no"];
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
			while($rowCollMember = $whocolluMember->fetch()){
				$arrMember = array();
				$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"]);
				$arrMember["AVATAR_PATH"] = $arrayAvarTar["AVATAR_PATH"];
				$arrMember["AVATAR_PATH_WEBP"] = $arrayAvarTar["AVATAR_PATH_WEBP"];
				$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
				$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
				$arrGroupAllMember[] = $arrMember;
			}
			$arrGroupAll['MEMBER'] = $arrGroupAllMember;
			$arrayGroupLoan[] = $arrGroupAll;
			if(sizeof($arrayGroupLoan) > 0 || isset($new_token)){
				$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				http_response_code(204);
				exit();
			}
		}else{
			$arrayGroupLoan = array();
			$getWhocollu = $conoracle->prepare("SELECT lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN lncontcoll lnc ON lnm.loancontract_no = lnc.loancontract_no
												LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
												and lnm.contract_status = '1'
                         						GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch()){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
				$arrGroupAll['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$arrGroupAll['CONTRACT_NO'] =  $lib->formatcontract($rowWhocollu["LOANCONTRACT_NO"],$func->getConstant('loan_format'));
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
				while($rowCollMember = $whocolluMember->fetch()){
					$arrMember = array();
					$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"]);
					$arrMember["AVATAR_PATH"] = $arrayAvarTar["AVATAR_PATH"];
					$arrMember["AVATAR_PATH_WEBP"] = $arrayAvarTar["AVATAR_PATH_WEBP"];
					$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
					$arrGroupAllMember[] = $arrMember;
				}
				$arrGroupAll['MEMBER'] = $arrGroupAllMember;
				$arrayGroupLoan[] = $arrGroupAll;
			}
			if(sizeof($arrayGroupLoan) > 0 || isset($new_token)){
				$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				http_response_code(204);
				exit();
			}
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>