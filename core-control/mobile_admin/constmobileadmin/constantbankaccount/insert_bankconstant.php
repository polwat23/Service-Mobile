<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount',$conoracle)){
		$id_bankconstant = $func->getMaxTable('id_bankconstant' , 'gcbankconstant',$conoracle);
		$updateConstants = $conoracle->prepare("INSERT INTO gcbankconstant
		(id_bankconstant,
		transaction_cycle,
		transaction_name,
		max_numof_deposit,
		max_numof_withdraw,
		min_deposit,
		max_deposit,
		min_withdraw,
		max_withdraw,
		each_bank)
		VALUES (:id_bankconstant,
		:transaction_cycle,
		:transaction_name,
		:max_numof_deposit,
		:max_numof_withdraw,
		:min_deposit,
		:max_deposit,
		:min_withdraw,
		:max_withdraw,
		:each_bank)");
		if($updateConstants->execute([
			':id_bankconstant' => $id_bankconstant,
			':transaction_cycle' => $dataComing["transaction_cycle"],
			':transaction_name' => $dataComing["transaction_name"],
			':max_numof_deposit' => $dataComing["max_numof_deposit"],
			':max_numof_withdraw' => $dataComing["max_numof_withdraw"],
			':min_deposit' => $dataComing["min_deposit"],
			':max_deposit' => $dataComing["max_deposit"],
			':min_withdraw' => $dataComing["min_withdraw"],
			':max_withdraw' => $dataComing["max_withdraw"],
			':each_bank' => $dataComing["each_bank"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"insert gcbankconstant",
					':details' => "transaction_cycle => ".$dataComing["transaction_cycle"].
								" transaction_name => ".$dataComing["transaction_name"].
								" max_numof_deposit => ".$dataComing["max_numof_deposit"].
								" max_numof_withdraw => ".$dataComing["max_numof_withdraw"].
								" min_deposit => ".$dataComing["min_deposit"].
								" max_deposit => ".$dataComing["max_deposit"].
								" min_withdraw => ".$dataComing["min_withdraw"].
								" max_withdraw => ".$dataComing["max_withdraw"]
			];
			$log->writeLog('manageuser',$arrayStruc, false, $conoracle);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESs'] = [
						':id_bankconstant' => $id_bankconstant,
						':transaction_cycle' => $dataComing["transaction_cycle"],
						':transaction_name' => $dataComing["transaction_name"],
						':max_numof_deposit' => $dataComing["max_numof_deposit"],
						':max_numof_withdraw' => $dataComing["max_numof_withdraw"],
						':min_deposit' => $dataComing["min_deposit"],
						':max_deposit' => $dataComing["max_deposit"],
						':min_withdraw' => $dataComing["min_withdraw"],
						':max_withdraw' => $dataComing["max_withdraw"],
						':each_bank' => $dataComing["each_bank"]
					];
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่างคงที่ยอดทำรายการได้";
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