<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managebanner')){
	  
		$del_img = $conmysql->prepare("DELETE FROM webcoopbanner WHERE banner_id = :banner_id");						
		if($del_img->execute([
				':banner_id' =>  $dataComing["banner_id"]
			])){
				
			$del_file="../../../../".$dataComing["imgPath"];
			$delIMG=unlink($del_file);
			
			$arrayResult["RESULT"] = TRUE;
			$arrayResult["DEL IMG"] = $delIMG;
			$arrayResult["DEL path"] = "../../../../".$dataComing["imgPath"];
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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