<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_bindaccount','bindacc_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageaccbeenbind')){
			$unbindaccount = $conmysql->prepare("UPDATE gcbindaccount 
										  SET bindaccount_status = :bindacc_status
								          WHERE  id_bindaccount = :id_bindaccount;");
		if($unbindaccount->execute([
			':bindacc_status' => $dataComing["bindacc_status"],
			':id_bindaccount' => $dataComing["id_bindaccount"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกการผูกบัญชีได้ กรุณติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		echo json_encode($arrayResult);	
		}
		$arrayResult['BINACCOUNT_DATA'] = $arrayBindaccount;
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

