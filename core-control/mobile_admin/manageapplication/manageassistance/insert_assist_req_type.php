<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','assist_code','membtype_code','assist_desc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		
		$updaeusername = $conmysql->prepare("INSERT INTO gcconstantwelfare(
								welfare_type_desc,
								welfare_type_code, 
								member_cate_code) 
								VALUES (:welfare_type_desc ,:welfare_type_code, :member_cate_code)");
		if($updaeusername->execute([
			':welfare_type_desc' => $dataComing["assist_desc"],
			':welfare_type_code' => $dataComing["assist_code"],
			':member_cate_code' => $dataComing["membtype_code"]
		])){
			
			$arrayStruc = [
				':menu_name' => "manageassistance",
				':username' => $payload["username"],
				':use_list' => "insert assistreq type",
				':details' => "add welfare code ".$dataComing["assist_code"]
			];
			
			$log->writeLog('manageapplication',$arrayStruc);
			
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่ประเภทสวัสดิการนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
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