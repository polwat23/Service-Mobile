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
				$member_no = $config["MEMBER_NO_DEV_UCOLLWHO"];
			}else if($payload["member_no"] == 'salemode'){
				$member_no = $config["MEMBER_NO_SALE_UCOLLWHO"];
			}else{
				$member_no = $payload["member_no"];
			}
			$arrayResult = array();
			$arrayGroupLoan = array();
			$getUcollwho = $conoracle->prepare("SELECT
												LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
												LNTYPE.loantype_desc as TYPE_DESC,
												PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
												LCM.MEMBER_NO AS MEMBER_NO,
												NVL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT
												FROM
												LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
												LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
												LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
												LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
												WHERE
												LCM.CONTRACT_STATUS = '1'
												AND LCC.LOANCOLLTYPE_CODE = '01'
												AND LCC.REF_COLLNO = :member_no");
			$getUcollwho->execute([':member_no' => $member_no]);
			while($rowUcollwho = $getUcollwho->fetch()){
				$arrayColl = array();
				$arrayColl["CONTRACT_NO"] = $lib->formatcontract($rowUcollwho["LOANCONTRACT_NO"],$func->getConstant('loan_format',$conmysql));
				$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
				$arrayColl["COLL_MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
				$arrayColl["PATH_AVATAR"] = $func->getPathpic($rowUcollwho["MEMBER_NO"],$conmysql);
				$arrayColl["APPROVE_AMT"] = number_format($rowUcollwho["LOANAPPROVE_AMT"],2);
				$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
				$arrayGroupLoan[] = $arrayColl;
			}
			$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
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