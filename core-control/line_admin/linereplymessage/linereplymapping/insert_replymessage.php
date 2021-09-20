<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','text_income'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
		$fetchMsg = $conmysql->prepare("SELECT id_textincome, text_income, is_use FROM lbincoming WHERE text_income = :text_income");
		$fetchMsg->execute([
			':text_income' => $dataComing["text_income"]
		]);
		$rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMsg["id_textincome"]) && $rowMsg["id_textincome"] != ""){
				$arrayResult['RESPONSE_ALERT'] = "มีข้อความนี้อยู่เเล้ว กรุณาแก้ไขข้อความและลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
		}else{
				$conmysql->beginTransaction();
				$insertMapping = $conmysql->prepare("INSERT INTO lbincoming(text_income) VALUES (:text_income)");
				if($insertMapping->execute([
					':text_income' => $dataComing["text_income"]
				])){
					$id_textincome = $conmysql->lastInsertId();
					$insertMapping = $conmysql->prepare("INSERT INTO lbtextmaptype(id_textincome, id_ref, type_message) VALUES (:id_textincome, :id_ref, :type_message)");
					if($insertMapping->execute([
						':id_textincome' => $id_textincome,
						':id_ref' => $dataComing["id_ref"],
						':type_message' => $dataComing["type_message"]
					])){
						$conmysql->commit();
						$arrayResult["RESULT"] = TRUE;
						require_once('../../../../include/exit_footer.php');
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
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
