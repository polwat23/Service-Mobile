<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','trantype'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptmixmin',$conoracle)){
		if(isset($dataComing["trantype"]) || $dataComing["trantype"] != ''){

			$fetchMaxID= $conoracle->prepare("SELECT MAX(ID) as ID_DEPTTYPE FROM GCCONSTANTLIMITTX");
			$fetchMaxID->execute();
			$rowMaxID = $fetchMaxID->fetch(PDO::FETCH_ASSOC);
	
			$insertConstants = $conoracle->prepare("INSERT INTO GCCONSTANTLIMITTX(ID,SIGN_FLAG ,MENU_COMPONENT) 
																	VALUES (:id_depttype,:flag ,:trantype)");
			if($insertConstants->execute([
				':id_depttype' => $rowMaxID["ID_DEPTTYPE"] + 1 ,
				':flag' => $dataComing["flag"],
				':trantype' => $dataComing["trantype"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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