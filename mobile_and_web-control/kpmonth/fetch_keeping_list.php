<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_KEEPINGMONTH"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_KEEPINGMONTH"];
		}else{
			$member_no = $payload["member_no"];
		}
		$limit_period = $func->getConstant('limit_kpmonth');
		$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth');
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
		if(sizeof($arrayGroupPeriod) > 0 || isset($new_token)){
			$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
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