<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','data'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
	
		$fetchMsg = $conmysql->prepare("SELECT id_textincome, text_income, is_use FROM lbincoming WHERE text_income = :text_income");
		$fetchMsg->execute([
			':text_income' => $dataComing["text_income"]
		]);
		$rowMsg = $fetchMsg->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMsg["id_textincome"]) && $rowMsg["id_textincome"] != ""){
				$arrayResult['RESPONSE_ALERT'] = "มีข้อความนี้อยู่เเล้ว กรุณาแก้ไขข้อความหรือเปลี่ยนข้อความ และลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
		}else{
			$conmysql->beginTransaction();
			$insertMapping = $conmysql->prepare("INSERT INTO lbincoming(text_income) VALUES (:text_income)");
			if($insertMapping->execute([
				':text_income' => $dataComing["text_income"]
			])){
				$id_textincome = $conmysql->lastInsertId();
				foreach($dataComing["data"] as $valueInsert){
					$insertBulkReply[] = "('".$id_textincome."','".$valueInsert["id_ref"]."','".$valueInsert["type_message"]."')";
				}
				$insertMapping = $conmysql->prepare("INSERT INTO lbtextmaptype(id_textincome, id_ref, type_message) VALUES".implode(',',$insertBulkReply));
				
				if($insertMapping->execute()){
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
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อความได้ กรุณาติดต่อผู้พัฒนา1";
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