<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcbankconstant
		SET transaction_cycle= :transaction_cycle,
		max_numof_deposit= :max_numof_deposit,
		max_numof_withdraw= :max_numof_withdraw,
		min_deposit= :min_deposit,
		max_deposit= :max_deposit,
		min_withdraw= :min_withdraw,
		max_withdraw= :max_withdraw
		WHERE id_bankconstant = :id_bankconstant");
		if($updateConstants->execute([
			':transaction_cycle' => $dataComing["transaction_cycle"],
			':max_numof_deposit' => $dataComing["max_numof_deposit"],
			':max_numof_withdraw' => $dataComing["max_numof_withdraw"],
			':min_deposit' => $dataComing["min_deposit"],
			':max_deposit' => $dataComing["max_deposit"],
			':min_withdraw' => $dataComing["min_withdraw"],
			':max_withdraw' => $dataComing["max_withdraw"],
			':id_bankconstant' => $dataComing["id_bankconstant"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"update gcbankconstant",
					':details' => "transaction_cycle => ".$dataComing["transaction_cycle"].
								" max_numof_deposit => ".$dataComing["max_numof_deposit"].
								" max_numof_withdraw => ".$dataComing["max_numof_withdraw"].
								" min_deposit => ".$dataComing["min_deposit"].
								" max_deposit => ".$dataComing["max_deposit"].
								" min_withdraw => ".$dataComing["min_withdraw"].
								" max_withdraw => ".$dataComing["max_withdraw"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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