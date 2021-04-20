<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedoccontrolid')){
			$insertDocumentSystems = $conmysql->prepare("UPDATE doccontrolid SET is_use = '0'
													WHERE short_prefix = :short_prefix");
			if($insertDocumentSystems->execute([
				':short_prefix' =>  $dataComing["short_prefix"]
			])){				
				$arrayStruc = [
					':menu_name' => "managedoccontrolid",
					':username' => $payload["username"],
					':use_list' =>"Delete controlid",
					':details' => "Delete on ".$dataComing["short_prefix"]
				];
				$log->writeLog('editdocument',$arrayStruc);	

				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
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

