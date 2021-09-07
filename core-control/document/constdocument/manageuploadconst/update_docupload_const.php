<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','manageuploadconst')){
			$insertDocumentSystems = $conmssql->prepare("UPDATE docuploadconstant SET upload_system = :upload_system, 
													upload_system_desc = :upload_system_desc, menu_component = :menu_component 
													WHERE id_upload = :id_upload");
			if($insertDocumentSystems->execute([
				':upload_system' =>  $dataComing["upload_system"],
				':upload_system_desc' =>  $dataComing["upload_system_desc"],
				':menu_component' =>  $dataComing["menu_component"],
				':id_upload' =>  $dataComing["id_upload"]
			])){				
				$arrayStruc = [
					':menu_name' => "manageuploadconst",
					':username' => $payload["username"],
					':use_list' =>"edit upload const",
					':details' => "id_upload = ".$dataComing["id_upload"]." upload_system = ".$dataComing["upload_system"]." upload_system_desc = ".$dataComing["upload_system_desc"]." menu_component = ".$dataComing["menu_component"]
				];
				$log->writeLog('editdocument',$arrayStruc);	

				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา ";
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

