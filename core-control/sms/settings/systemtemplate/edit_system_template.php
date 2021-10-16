<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','subject','detail_root_','id_systemplate','is_use'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managesystemtemplate',$conoracle)){
		if($dataComing["id_systemplate"] == '8' || $dataComing["id_systemplate"] == '11' || $dataComing["id_systemplate"] == '28'){
			
			$file_pointer = __DIR__.'/../../../../resource/html/'.'sms'.$dataComing["id_systemplate"].'.html'; 
  
			if (!unlink($file_pointer)) { 
				$arrayResult['RESPOsssNSE'] = $file_pointer;
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตระบบได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
			file_put_contents(__DIR__.'/../../../../resource/html/'.'sms'.$dataComing["id_systemplate"].'.html', $dataComing["detail_root_"] . PHP_EOL, FILE_APPEND);
			
			$updateSysTemplate = $conoracle->prepare("UPDATE smssystemtemplate SET subject = :subject,body = :body,is_use = :is_use WHERE id_systemplate = :id_systemplate");
			if($updateSysTemplate->execute([
				':subject' => $dataComing["subject"],
				':body' => '/resource/html/'.'sms'.$dataComing["id_systemplate"].'.html' ?? null,
				':is_use' => $dataComing["is_use"],
				':id_systemplate' => $dataComing["id_systemplate"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตระบบได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');	
			}
		}else{
			$updateSysTemplate = $conoracle->prepare("UPDATE smssystemtemplate SET subject = :subject,body = :body,is_use = :is_use WHERE id_systemplate = :id_systemplate");
			if($updateSysTemplate->execute([
				':subject' => $dataComing["subject"],
				':body' => $dataComing["detail_root_"],
				':is_use' => $dataComing["is_use"],
				':id_systemplate' => $dataComing["id_systemplate"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตระบบได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');	
			}
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