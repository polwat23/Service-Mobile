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
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'PaymentMonthlyInfo')){
			if($payload["member_no"] == 'dev@mode'){
				$member_no = $config["MEMBER_NO_DEV_KEEPINGMONTH"];
			}else if($payload["member_no"] == 'salemode'){
				$member_no = $config["MEMBER_NO_SALE_KEEPINGMONTH"];
			}else{
				$member_no = $payload["member_no"];
			}
			$limit_period = $func->getConstant('limit_kpmonth',$conmysql);
			$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth',$conmysql);
			$dateNow = date('d');
			$arrayGroupPeriod = array();
			if($dateNow >= $dateshow_kpmonth){
				$getPeriodKP = $conoracle->prepare("SELECT * from ((
														select recv_period from kpmastreceive where member_no = :member_no
													UNION  
														select recv_period  from kptempreceive where member_no = :member_no
													) ORDER BY recv_period DESC) where rownum <= :limit_period");
			}else{
				$getPeriodKP = $conoracle->prepare("SELECT * from ((
														select recv_period from kpmastreceive where member_no = :member_no and 
														recv_period <> ( select MAX(recv_period) from kpmastreceive where member_no = :member_no)
													UNION 
														select recv_period  from kptempreceive where member_no = :member_no and 
														recv_period <> ( select MAX(recv_period) from kptempreceive where member_no = :member_no)
													) ORDER BY recv_period DESC) where rownum <= :limit_period");
			}
			$getPeriodKP->execute([
					':member_no' => $member_no,
					':limit_period' => $limit_period
			]);
			while($rowPeriod = $getPeriodKP->fetch()){
				$arrKpmonth = array();
				$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
				$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp($rowPeriod["RECV_PERIOD"]);
				$getKPDetail = $conoracle->prepare("select * from (
														(select RECEIPT_NO,RECEIVE_AMT from kpmastreceive 
														where member_no = :member_no and recv_period = :period)
													UNION
														(select RECEIPT_NO,RECEIVE_AMT from kptempreceive 
														where member_no = :member_no and recv_period = :period)
													)");
				$getKPDetail->execute([
					':member_no' => $member_no,
					':period' => $rowPeriod["RECV_PERIOD"]
				]);
				$rowKPDetali = $getKPDetail->fetch();
				$arrKpmonth["SLIP_NO"] = $rowKPDetali["RECEIPT_NO"];
				$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["RECEIVE_AMT"],2);
				$arrayGroupPeriod[] = $arrKpmonth;
			}
			$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
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