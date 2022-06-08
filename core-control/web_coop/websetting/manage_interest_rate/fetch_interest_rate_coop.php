<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$fetchInerestRate = $conmysql->prepare("SELECT
											interestrate_id,
											interest_name,
											active_date
										FROM
											webcoopinterestrate 
										WHERE
											is_use = '1'
										");
	$fetchInerestRate->execute([
		':is_use' =>  '1'
	]);

	while($rowInterestRate = $fetchInerestRate->fetch(PDO::FETCH_ASSOC)){
		$fetchInerestRateData = $conmysql->prepare("SELECT
														interestratelist_id,
														interestratelist_name,
														interest_rate,
														create_date,
														update_date
													FROM
														webcoopinterestratelist
													WHERE
														interestrate_id = :interestrate_id AND is_use = :is_use
												");
		$fetchInerestRateData->execute([
			':is_use' =>  '1',
			':interestrate_id' => $rowInterestRate["interestrate_id"]
		]);	
		$data = [];
		while($rowDataInterestRate = $fetchInerestRateData->fetch(PDO::FETCH_ASSOC)){
			$arrDataInterestRate = array();
			$arrDataInterestRate["SUB_ID"]=$rowDataInterestRate["interestratelist_id"];
			$arrDataInterestRate["SUB_NAME"]=$rowDataInterestRate["interestratelist_name"];
			$arrDataInterestRate["INTEREST_RATE"]=$rowDataInterestRate["interest_rate"];
			$arrDataInterestRate["CREATE_DATE"] = $lib->convertdate($rowDataInterestRate["create_date"],'d m Y',false); 
			$arrDataInterestRate["UPDATE_DATE"] = $lib->convertdate($rowDataInterestRate["update_date"],'d m Y',false); 
			$data[]=$arrDataInterestRate;
		}
		$arrInterestRate["INTERESTRATE_ID"] = $rowInterestRate["interestrate_id"];
		$arrInterestRate["INTERESTRATE_NAME"] = $rowInterestRate["interest_name"];
		$arrInterestRate["ACTIVE_DATE"] = $rowInterestRate["active_date"];
		$arrInterestRate["ACTIVE_DATE_FORMAT"] = $lib->convertdate($rowInterestRate["active_date"],'d m Y',false); 
		$arrInterestRate["DATA"] = $data;
		$arrayGroup[] = $arrInterestRate;
		
	}
	
	$arrayResult["INTERESTRATE_DATA"] = $arrayGroup;
	$arrayResult["INTERESTRATE_DATA_ORG"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>