<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','branch_id'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopbranch
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											branch_id  = :branch_id");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':branch_id' => $dataComing["branch_id"],
				':update_by' => $payload["username"]
			])){
				
				$updatStatusWebcoopboardofdirectors = $conmysql->prepare("
										UPDATE
											webcoopboardofdirectors
										SET
											is_use = '-9'
											
										WHERE
											branch_id  = :branch_id");						
				if($updatStatusWebcoopboardofdirectors->execute([
						':branch_id' => $dataComing["branch_id"]
					])){
						$arrayResult["RESULT"] = TRUE;
						echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
					$arrayResult['dataComing'] = [
						':branch_name' =>  $dataComing["branch_name"],
						':branch_id' => $dataComing["branch_id"],
						':update_by' => $payload["username"]
					];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				} 
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else if($dataComing["type"] == 'rename'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopbranch
										SET
											branch_name = :branch_name,
											update_by = :update_by
										WHERE
											branch_id  = :branch_id");						
		if($updatStatus->execute([
				':branch_name' =>  $dataComing["branch_name"],
				':branch_id' => $dataComing["branch_id"],
				':update_by' => $payload["username"]
			])){
				$arrayResult['RESULT'] = TRUE;
				
				echo json_encode($arrayResult);
				
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['dataComing'] = [
				':branch_name' =>  $dataComing["branch_name"],
				':branch_id' => $dataComing["branch_id"],
				':update_by' => $payload["username"]
			];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>