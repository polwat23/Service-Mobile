<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reqdocformtype_id','active_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqdocumentformlist')){
		
		$updateConst = $conmysql->prepare("UPDATE gcreqdocformtype SET is_use = :status WHERE reqdocformtype_id = :reqdocformtype_id");
		if($updateConst->execute([
			':reqdocformtype_id' => $dataComing["reqdocformtype_id"],
			':status' => $dataComing["active_status"]
		])){
			$arrayStruc = [
				':menu_name' => 'constantreqdocumentformtype',
				':username' => $payload["username"],
				':use_list' => 'delete gcreqdocformtype',
				':details' => $payload["username"]." => reqdocformtype_id : ".($dataComing["reqdocformtype_id"] ?? "")
			];
			$log->writeLog('manageuser',$arrayStruc);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการใบคำขอได้ กรุณาติดต่อผู้พัฒนา";
			require_once('../../../../include/exit_footer.php');
			
		}
			
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
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