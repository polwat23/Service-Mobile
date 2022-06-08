<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
			
	$deleteGallery = $conmysql->prepare("UPDATE webcoopinterestrate SET 
												interest_name = :interest_name,
												active_date = :active_date,
												update_by = :update_by
											WHERE interestrate_id = :interestrate_id");
	if($deleteGallery->execute([
		':interest_name' =>  $dataComing["interest_name"],
		':active_date' =>  $dataComing["active_date"],
		':interestrate_id' =>  $dataComing["interestrate_id"],
		':update_by' =>  $payload["username"]
	])){
		
		$interestratelist = $conmysql->prepare("DELETE FROM webcoopinterestratelist WHERE interestrate_id = :interestrate_id");						
		if($interestratelist->execute([
				':interestrate_id' =>  $dataComing["interestrate_id"]
			])){
				
				$insertData = [];
				foreach($dataComing["list"] as $data){
							$insertData[] = "('".$dataComing["interestrate_id"]."','".$data["SUB_NAME"]."','".$data["INTEREST_RATE"]."','".$payload["username"]."')";
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
			$arrayResult['RESPONSE'] = "เกิดข้อผิดพลาด กรุณาติดผู้ผัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}  
		
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามาแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา  ";
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