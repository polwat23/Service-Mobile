<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_depttran'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror')){
		$updateStatusADJ = $conmysql->prepare("UPDATE logdepttransbankerror SET is_adj = '1' WHERE id_deptransbankerr = :id_depttran");
		if($updateStatusADJ->execute([':id_depttran' => $dataComing["id_depttran"]])){
			$arrayStruc = [
				':menu_name' => "logdepositerror",
				':username' => $payload["username"],
				':use_list' => "เปลี่ยนสถานะรายการ adj รายการเงินฝากเรียบร้อยรายการที่ ".$dataComing["id_depttran"],
				':details' => "เปลี่ยนสถานะรายการ adj รายการเงินฝากเรียบร้อยรายการที่ ".$dataComing["id_depttran"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			$arrayStruc = [
				':menu_name' => "logdepositerror",
				':username' => $payload["username"],
				':use_list' => "เปลี่ยนสถานะรายการ adj รายการเงินฝากไม่สำเร็จรายการที่ ".$dataComing["id_depttran"],
				':details' => "เปลี่ยนสถานะรายการ adj รายการเงินฝากไม่สำเร็จรายการที่ ".$dataComing["id_depttran"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult['RESPONSE'] = "เปลี่ยนสถานะรายการ adj รายการเงินฝากไม่สำเร็จ";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>