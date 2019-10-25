<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token === 'expired'){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "SQL409";
				$arrayResult['RESPONSE'] = "Invalid RefreshToken is not correct or RefreshToken was expired";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}else{
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}
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
				if($rowWhocollu){
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
						$arrMember["PATH_AVATAR"] = $func->getPathpic($rowCollMember["MEMBER_NO"],$conmysql);
						$arrMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrMember["MEMBER_NO"] = $rowCollMember["MEMBER_NO"];
						$arrGroupAll[] = $arrMember;
					}
					$arrayResult['MEMBER_NO'] = $arrGroupAll;
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE_CODE'] = "SQL400";
					$arrayResult['RESPONSE'] = "Empty data";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
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
					$whocolluMember->execute([':contract_no' => $arrGroupAll['CONTRACT_NO']]);
					while($rowCollMember = $whocolluMember->fetch()){
						$arrMember = array();
						$arrMember["PATH_AVATAR"] = $func->getPathpic($rowCollMember["MEMBER_NO"],$conmysql);
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
			$arrayResult['RESPONSE_CODE'] = "PARAM500";
			$arrayResult['RESPONSE'] = "Not permission this menu";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>