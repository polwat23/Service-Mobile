<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','data'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereplymapping')){
		
			$updateTextIncomming = $conmysql->prepare("UPDATE lbincoming SET  text_income = :text_income WHERE id_textincome = :id_textincome");
			if($updateTextIncomming->execute([
				':id_textincome' => $dataComing["id_textincome"],
				':text_income' => $dataComing["text_income"]
			])){
			
				$insertMapping = $conmysql->prepare("DELETE FROM lbtextmaptype WHERE id_textincome = :id_textincome");
				if($insertMapping->execute([
					':id_textincome' => $dataComing["id_textincome"]
				])){
					$arrayResult['RESPONSE'] = 'ทำรายการสำเร็จ';

				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
				foreach($dataComing["data"] AS $dataupdate){
				
					$insertMapping = $conmysql->prepare("INSERT INTO lbtextmaptype (id_textincome, id_ref,type_message) VALUES(:id_textincome,:id_ref,:type_message)");
					if($insertMapping->execute([
						':id_ref' => $dataupdate["id_ref"],
						':id_textincome' => $dataComing["id_textincome"],
						':type_message' => $dataupdate["type_message"]
						
					])){
						$arrayResult['RESPONSE'] = 'ทำรายการสำเร็จ';
						 
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา2";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}	
				}
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา";
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