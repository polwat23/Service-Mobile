<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_textmaptype','id_textincome'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
			$conmysql->beginTransaction();
			$updateMapping = $conmysql->prepare("UPDATE lbtextmaptype SET is_use = '0'
													WHERE id_textmaptype = :id_textmaptype");
													
			if($updateMapping->execute([
				':id_textmaptype' => $dataComing["id_textmaptype"]
			])){
				$updateText = $conmysql->prepare("UPDATE lbincoming SET is_use = '0'
													WHERE id_textincome = :id_textincome");
				if($updateText->execute([
					':id_textincome' => $dataComing["id_textincome"]
				])){
					$conmysql->commit();
					$arrayResult["RESULT"] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อความได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อความได้ กรุณาติดต่อผู้พัฒนา";
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
?>