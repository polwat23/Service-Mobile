<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $configAS["MEMBER_NO_DEV_KEEPINGMONTH"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $configAS["MEMBER_NO_SALE_KEEPINGMONTH"];
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
													(select kpr.RECEIPT_NO,NVL(sum_item.ITEM_PAYMENT,kpr.RECEIVE_AMT) as RECEIVE_AMT from kpmastreceive kpr,(SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT FROM kpmastreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period) sum_item
													where kpr.member_no = :member_no and kpr.recv_period = :recv_period )
												UNION
													(select kpr.RECEIPT_NO,NVL(sum_item.ITEM_PAYMENT,kpr.RECEIVE_AMT) as RECEIVE_AMT from kptempreceive kpr,(SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT FROM kptempreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period) sum_item
													where kpr.member_no = :member_no and kpr.recv_period = :recv_period )
												)");
			$getKPDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $rowPeriod["RECV_PERIOD"]
			]);
			$rowKPDetali = $getKPDetail->fetch();
			$arrKpmonth["SLIP_NO"] = $rowKPDetali["RECEIPT_NO"];
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["RECEIVE_AMT"],2);
			$arrayGroupPeriod[] = $arrKpmonth;
		}
		$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>