<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reqdocformtype_id','documenttype_desc','documentform_url'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqdocumentformlist')){
		
		$updateConst = $conmysql->prepare("UPDATE gcreqdocformtype SET documenttype_desc = :documenttype_desc, documentform_url = :documentform_url WHERE reqdocformtype_id = :reqdocformtype_id");
		if($updateConst->execute([
			':documenttype_desc' => $dataComing["documenttype_desc"],
			':documentform_url' => $dataComing["documentform_url"],
			':reqdocformtype_id' => $dataComing["reqdocformtype_id"]
		])){
			$arrayStruc = [
				':menu_name' => 'constantreqdocumentformtype',
				':username' => $payload["username"],
				':use_list' => 'update gcreqdocformtype',
				':details' => $payload["username"]." => documenttype_desc : ".($dataComing["documenttype_desc"] ?? "").", reqdocformtype_id : ".($dataComing["reqdocformtype_id"] ?? "").", documentform_url = ".($dataComing["documentform_url"] ?? "")
			];
			$log->writeLog('manageuser',$arrayStruc);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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