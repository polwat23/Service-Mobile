<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_extra_credit','extra_credit_name'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extracreditproject')){
	
		if(isset($dataComing["news_html_root_"]) && $dataComing["news_html_root_"] != null){
			$detail_html = '<!DOCTYPE HTML>
									<html>
									<head>
										<style>
										img {
											max-width: 100%;
										}
										</style>
								  <meta charset="UTF-8">
								  <meta name="viewport" content="width=device-width, initial-scale=1.0">
								  '.$dataComing["news_html_root_"].'
								  </body>
									</html>';
		}
		$updateConstants = $conmysql->prepare("UPDATE gcconstantextracreditproject SET extra_credit_name = :extra_credit_name,extra_credit_desc = :extra_credit_desc,extra_credit_html = :extra_credit_html,loantype_code = :loantype_code, update_by = :update_by WHERE id_extra_credit = :id_extra_credit");
		if($updateConstants->execute([
			':extra_credit_name' => $dataComing["extra_credit_name"],
			':extra_credit_desc' => $dataComing["extra_credit_desc"],
			':extra_credit_html' => $detail_html ?? null,
			':loantype_code' => $dataComing["loantype_code"],
			':update_by' => $payload["username"],
			':id_extra_credit' => $dataComing["id_extra_credit"],
		])){
			$arrayStruc = [
				':menu_name' => "extracreditproject",
				':username' => $payload["username"],
				':use_list' =>"update gcconstantextracreditproject",
				':details' => "extra_credit_name = ".$dataComing["extra_credit_name"]."; extra_credit_desc = ".$dataComing["extra_credit_desc"]."; loantype_code = ".$dataComing["loantype_code"]."; id_extra_credit = ".$dataComing["id_extra_credit"]."; extra_credit_html = ".$detail_html ?? ''
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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