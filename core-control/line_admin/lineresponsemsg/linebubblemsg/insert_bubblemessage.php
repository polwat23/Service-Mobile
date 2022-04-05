<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','title','data_root_'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
			$updateText = $conmysql->prepare("INSERT INTO lbbublemessage(title, data,creat_by) 
															VALUES (:title, :data, :creat_by)");
															
			if($updateText->execute([
				':title' => $dataComing["title"],
				':data' => $dataComing["data_root_"],
				':creat_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				$arrayResult["data"] = $dataComing;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = " ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['data'] = [
					':title' => $dataComing["title"],
					':data' => $dataComing["data_root_"],
					':creat_by' => $payload["username"]
				];
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