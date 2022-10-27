<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_depttype'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptmixmin',$conoracle)){
		if(isset($dataComing["id_depttype"]) || $dataComing["id_depttype"] != ''){

			$insertConstants = $conoracle->prepare("UPDATE GCCONSTANTDEPTMAXMIN SET IS_USE = :is_use
													WHERE id_depttype = :id_depttype");
			if($insertConstants->execute([
				':id_depttype' => $dataComing["id_depttype"],
				':is_use' => $dataComing["is_use"]
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
			$arrayResult['RESPONSE'] = "ไม่สามารถ Update ได้กรุณาติดต่อผู้พัฒนา";
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