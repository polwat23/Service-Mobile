<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_list'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$conoracle->beginTransaction();
		foreach($dataComing["menu_list"] as $menu_list){
			$updatemenu = $conoracle->prepare("UPDATE gcmenu SET menu_order = :menu_order
										 WHERE id_menu = :id_menu");
			if($updatemenu->execute([
				':menu_order' => $menu_list["ORDER"],
				':id_menu' => $menu_list["MENU_ID"]
			])){
				continue;
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถจัดเรียงเมนูได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}
		$conoracle->commit();
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