<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$insert_interest_rate = $conmysql->prepare("INSERT INTO webcoopinterestrate(
							interest_name,
							active_date,
							update_by
						)
						VALUES(
							:interest_name,
							:active_date,
							:update_by
						)");
	if($insert_interest_rate->execute([
		':interest_name' =>  $dataComing["interest_name"],
		':active_date' =>  $dataComing["active_date"],
		':update_by' =>  $payload["username"],
	])){
		
		$fetchIdInterestRate = $conmysql->prepare("SELECT
														interestrate_id
													FROM
														webcoopinterestrate
													ORDER BY
														interestrate_id
													DESC
													LIMIT 1
													");
		$fetchIdInterestRate->execute();
		$id_interestrate = $fetchIdInterestRate->fetch(PDO::FETCH_ASSOC);	
		
		$insertData = [];
		foreach($dataComing["list"] as $data){
			$insertData[] = "('".$id_interestrate["interestrate_id"]."','".$data["INTEREST_NAME"]."','".$data["INTEREST_RATE"]."','".$payload["username"]."')";
		}
			
	
			
		$insertInterestRate = $conmysql->prepare("INSERT INTO webcoopinterestratelist(interestrate_id,interestratelist_name,interest_rate,update_by)
		VALUES".implode(',',$insertData));			
		if($insertInterestRate->execute()){
			
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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