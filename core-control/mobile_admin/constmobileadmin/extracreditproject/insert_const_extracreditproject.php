<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','extra_credit_name'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extracreditproject')){
		if(isset($dataComing["extra_credit_name"]) || $dataComing["extra_credit_name"] != ''){
		
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
			$updateConstants = $conmysql->prepare("insert into gcconstantextracreditproject(extra_credit_name, extra_credit_desc, extra_credit_html, loantype_code, create_by, update_by) 
			VALUES(:extra_credit_name, :extra_credit_desc, :extra_credit_html, :loantype_code, :create_by,:update_by)");
			if($updateConstants->execute([
				':extra_credit_name' => $dataComing["extra_credit_name"],
				':extra_credit_desc' => $dataComing["extra_credit_desc"],
				':extra_credit_html' => $detail_html ?? null,
				':loantype_code' => $dataComing["loantype_code"],
				':create_by' => $payload["username"],
				':update_by' => $payload["username"],
			])){
				$arrayStruc = [
					':menu_name' => "extracreditproject",
					':username' => $payload["username"],
					':use_list' =>"insert gcconstantextracreditproject",
					':details' => "extra_credit_name = ".$dataComing["extra_credit_name"]."; extra_credit_desc = ".$dataComing["extra_credit_desc"]."; loantype_code = ".$dataComing["loantype_code"]."; extra_credit_html = ".$detail_html ?? ''
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเป็นค่าว่างได้";
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