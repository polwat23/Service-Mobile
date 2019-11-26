<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'GuaranteeInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_WHOCOLLU"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_WHOCOLLU"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrayResult = array();
		if(isset($dataComing["contract_no"])){
			$arrGroupAll = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conoracle->prepare("SELECT NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN lncontcoll lnc ON lnm.loancontract_no = lnc.loancontract_no
												LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status = '1'");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch();
			$arrayResult['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayResult['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$whocolluMember = $conoracle->prepare("SELECT
													MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME,
													LCC.REF_COLLNO AS MEMBER_NO			
												FROM
													LNCONTCOLL LCC LEFT JOIN MBMEMBMASTER MMB ON LCC.REF_COLLNO = MMB.MEMBER_NO
													LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
												WHERE
													LCC.LOANCOLLTYPE_CODE = '01' 
													AND LCC.COLL_STATUS = '1' 
													AND LCC.LOANCONTRACT_NO = :contract_no ");
			$whocolluMember->execute([':contract_no' => $contract_no]);
			while($rowCollMember = $whocolluMember->fetch()){
				$arrMember = array();
				$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"],$conmysql);
				$arrMember["AVATAR_PATH"] = $arrayAvarTar["AVATAR_PATH"];
				$arrMember["AVATAR_PATH_WEBP"] = $arrayAvarTar["AVATAR_PATH_WEBP"];
				$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
				$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
				$arrGroupAll[] = $arrMember;
			}
			if(sizeof($arrGroupAll) > 0 || isset($new_token)){
				$arrayResult['MEMBER_NO'] = $arrGroupAll;
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
				$arrGroupAll['CONTRACT_NO'] =  $lib->formatcontract($rowWhocollu["LOANCONTRACT_NO"],$func->getConstant('loan_format',$conmysql));
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
					$arrayAvarTar = $func->getPathpic($rowCollMember["MEMBER_NO"],$conmysql);
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
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>