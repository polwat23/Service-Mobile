<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','qrcodegen_id','transfer_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','qrgeneratelist')){
		$arrayGrpAll = array();
		
		$fetchQrgenerateList = $conmysql->prepare("UPDATE gcqrcodegenmaster SET transfer_status = :transfer_status WHERE qrcodegen_id = :qrcodegen_id");
			
		if($fetchQrgenerateList->execute([
			':transfer_status' => $dataComing['transfer_status'],
			':qrcodegen_id' => $dataComing['qrcodegen_id']
		])){
			$arrayStruc = [
				':menu_name' => 'qrgeneratelist',
				':username' => $payload["username"],
				':use_list' => 'update status qrgeneratelist',
				':details' => "transfer_status ".$dataComing["transfer_status_old"]." => ".$dataComing["transfer_status"].", qrcodegen_id => ".$dataComing["qrcodegen_id"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขสถานะได้ กรุณาติดต่อผู้พัฒนา";
			echo json_encode($arrayResult);
			exit();
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