<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_imagecarousel'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresimagemsg')){
			$updateCarousel = $conmysql->prepare("UPDATE lbimagecarouseltemplate SET is_use = '0', update_by = :update_by
													WHERE id_imagecarousel = :id_imagecarousel");
			if($updateCarousel->execute([
				':update_by' => $payload["username"],
				':id_imagecarousel' => $dataComing["id_imagecarousel"]
			])){
				$updateCarouselMap = $conmysql->prepare("UPDATE lbimagecarouselmap SET is_use = '0', update_by = :update_by
													WHERE imagecarousel_id = :id_imagecarousel");
				if($updateCarouselMap->execute([
					':update_by' => $payload["username"],
					':id_imagecarousel' => $dataComing["id_imagecarousel"]
				])){
					foreach($dataComing["column"] AS $arrComlumn){
						$updateCarouselColumn = $conmysql->prepare("UPDATE lbimagecarouselcolumns SET is_use = '0', update_by = :update_by
															WHERE id_columns = :id_columns");
						if($updateCarouselColumn->execute([
							':update_by' => $payload["username"],
							':id_columns' => $arrComlumn["ID_COLUMNS"]
						])){
								$arrayResult['MESSAGE'] = "ทำรายการสำเร็จ";
						}else{
							$arrayResult['RESPONSE'] = "ไม่สามารถลบรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}
					$arrayResult["RESULT"] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถลบรูปภาพได้ กรุณาติดต่อผู้พัฒนา2";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบรูปภาพได้ กรุณาติดต่อผู้พัฒนา";
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