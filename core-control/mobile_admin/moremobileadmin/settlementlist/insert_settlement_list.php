<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','month_period','settlement_list'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','settlementlist')){
		$conmysql->beginTransaction();
		$insertSettlement = $conmysql->prepare("UPDATE gcmembsettlement SET is_use = '0' WHERE month_period = :month_period and is_use = '1'");
		if($insertSettlement->execute([
			':month_period' => $dataComing["month_period"],
		])){
			foreach ($dataComing["settlement_list"] as $value) {
				$insertSettlement = $conmysql->prepare("INSERT INTO gcmembsettlement
				(emp_no, salary, settlement_amt, upload_username,
				update_username, month_period) 
				VALUES 
				(:emp_no, :salary, :settlement_amt, :upload_username,
				:update_username, :month_period)");
				if($insertSettlement->execute([
					':emp_no' => $value["EMP_NO"],
					':salary' => $value["SALARY"],
					':settlement_amt' => $value["SETTLEMENT_AMT"],
					':upload_username' => $payload["username"],
					':update_username' => $payload["username"],
					':month_period' => $dataComing["month_period"],
				])){
				
				}else{
					$conmysql->rollback();
					$arrayResult['ROW_DATA'] = $value;
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['RESPONSE'] = "ไม่สามารถอัปโหลดรายการหักได้ กรุณาติดต่อผู้พัฒนา";
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			$conmysql->rollback();
			$arrayResult['ROW_DATA'] = $value;
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถอัปโหลดรายการหักได้ กรุณาติดต่อผู้พัฒนา";
			echo json_encode($arrayResult);
			exit();
		}
		$conmysql->commit();
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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