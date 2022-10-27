<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_depttype'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptmixmin',$conoracle)){
		if(isset($dataComing["id_depttype"]) || $dataComing["id_depttype"] != ''){

			$insertDepositMinMax = $conoracle->prepare("UPDATE GCCONSTANTLIMITTX SET amount = :amount,
														count = :count,
														times = :times_code
														WHERE id = :id_depttype");
			if($insertDepositMinMax->execute([
				':id_depttype' => $dataComing["id_depttype"],
				':amount' => $dataComing["amount"],
				':count' => $dataComing["count"],
				':times_code' => $dataComing["times_code"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถ Update ได้กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่เป็นค่าว่างได้";
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