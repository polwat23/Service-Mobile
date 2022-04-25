<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','interestrate_id','interestratelist_name','interest_rate'],$dataComing)){

	$insert_list_interest_rate = $conmysql->prepare("INSERT INTO webcoopinterestratelist(
									interestrate_id,
									interestratelist_name,
									interest_rate,
									update_by)
								VALUES(
									:interestrate_id,
									:interestratelist_name,
									:interest_rate,
									:update_by
								)");
	if($insert_list_interest_rate->execute([
		':interestrate_id' =>  $dataComing["interestrate_id"],
		':interestratelist_name' => $dataComing["interestratelist_name"],
		':interest_rate' => $dataComing["interest_rate"],
		':update_by' =>  $payload["username"]
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่ขม้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>