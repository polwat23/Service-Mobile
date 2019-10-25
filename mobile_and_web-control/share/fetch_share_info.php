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
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ShareInfo')){
			if($payload["member_no"] == 'dev@mode'){
				$member_no = $config["MEMBER_NO_DEV_SHARE"];
			}else if($payload["member_no"] == 'salemode'){
				$member_no = $config["MEMBER_NO_SALE_SHARE"];
			}else{
				$member_no = $payload["member_no"];
			}
			$getSharemasterinfo = $conoracle->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT,sharebegin_amt
														FROM shsharemaster WHERE member_no = :member_no");
			$getSharemasterinfo->execute([':member_no' => $member_no]);
			$rowMastershare = $getSharemasterinfo->fetch();
			if($rowMastershare){
				$arrGroupStm = array();
				$arrayResult['BRING_FORWARD'] = number_format($rowMastershare["SHAREBEGIN_AMT"],2);
				$arrayResult['SHARE_AMT'] = number_format($rowMastershare["SHARE_AMT"],2);
				$arrayResult['PERIOD_SHARE_AMT'] = number_format($rowMastershare["PERIOD_SHARE_AMT"],2);
				$limit = $func->getConstant('limit_stmshare',$conmysql);
				$arrayResult['LIMIT_DURATION'] = $limit;
				if(isset($dataComing["date_start"])){
					$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
				}else{
					$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
				}
				$date_now = date('Y-m-d');
				$getShareStatement = $conoracle->prepare("SELECT stm.operate_date,(stm.share_amount * 10) as PERIOD_SHARE_AMOUNT,
															stm.sharestk_amt as SUM_SHARE_AMT,sht.shritemtype_desc,stm.period
															FROM shsharestatement stm LEFT JOIN shucfshritemtype sht ON stm.shritemtype_code = sht.shritemtype_code
															WHERE stm.member_no = :member_no and stm.ENTRY_DATE
															BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD')");
				$getShareStatement->execute([
					':member_no' => $member_no,
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowStm = $getShareStatement->fetch()){
					$arrayStm = array();
					$arrayStm["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
					$arrayStm["PERIOD_SHARE_AMOUNT"] = number_format($rowStm["PERIOD_SHARE_AMOUNT"],2);
					$arrayStm["SUM_SHARE_AMT"] = number_format($rowStm["SUM_SHARE_AMT"],2);
					$arrayStm["SHARETYPE_DESC"] = $rowStm["SHRITEMTYPE_DESC"];
					$arrayStm["PERIOD"] = $rowStm["PERIOD"];
					$arrGroupStm[] = $arrayStm;
				}
				$arrayResult['STATEMENT'] = $arrGroupStm;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL400";
				$arrayResult['RESPONSE'] = "Not found your share";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "PARAM500";
			$arrayResult['RESPONSE'] =  "Not permission this menu";
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