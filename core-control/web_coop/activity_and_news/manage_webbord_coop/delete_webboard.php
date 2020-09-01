<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managewebbord')){
			$del_file = null;
			$del_headfile = null;
			
			$deleteWebboard = $conmysql->prepare("DELETE FROM webcoopwebboard WHERE id_webboard = :id_webboard");
			if($deleteWebboard->execute([
				':id_webboard' =>  $dataComing["id_webboard"]
			])){
				
				$del_headfile="../../../../".$dataComing["img_head_path"];
				$del=unlink($del_headfile);
				
				$arrayResult['del'] = $del;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามาลบข้อมูลได้ กรุณาติดต่อผู้พัฒนา  ";
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