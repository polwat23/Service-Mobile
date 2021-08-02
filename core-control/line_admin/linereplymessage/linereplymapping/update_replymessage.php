<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_ref','type_message','id_textmaptype'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
			$updateMapping = $conmysql->prepare("UPDATE lbtextmaptype SET id_ref = :id_ref, type_message = :type_message WHERE id_textmaptype = :id_textmaptype");
			if($updateMapping->execute([
				':id_ref' => $dataComing["id_ref"],
				':type_message' => $dataComing["type_message"],
				':id_textmaptype' => $dataComing["id_textmaptype"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>