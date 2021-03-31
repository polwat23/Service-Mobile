<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','manageuploadconst')){
			$insertDocumentSystems = $conmysql->prepare("INSERT INTO docuploadconstant( upload_system, 
													upload_system_desc, menu_component) 
													VALUES (:upload_system,:upload_system_desc,:menu_component)");
			if($insertDocumentSystems->execute([
				':upload_system' =>  $dataComing["upload_system"],
				':upload_system_desc' =>  $dataComing["upload_system_desc"],
				':menu_component' =>  $dataComing["menu_component"]
			])){				
				$arrayStruc = [
					':menu_name' => "manageuploadconst",
					':username' => $payload["username"],
					':use_list' =>"add upload const",
					':details' => "upload_system = ".$dataComing["upload_system"]." upload_system_desc = ".$dataComing["upload_system_desc"]." menu_component = ".$dataComing["menu_component"]
				];
				$log->writeLog('editdocument',$arrayStruc);	

				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา ";
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

