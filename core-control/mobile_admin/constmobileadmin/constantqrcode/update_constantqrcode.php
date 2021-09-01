<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','trans_code_qr','trans_desc_qr','id_conttranqr'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantqrcode')){
		$fetchCheckConst = $conmysql->prepare("select id_conttranqr from gcconttypetransqrcode 
											where is_use = '1' and trans_code_qr = :trans_code_qr AND id_conttranqr != :id_conttranqr");
		$fetchCheckConst->execute([
			':trans_code_qr' => $dataComing["trans_code_qr"],
			':id_conttranqr' => $dataComing["id_conttranqr"],
		]);
		$rowCheckConst = $fetchCheckConst->fetch(PDO::FETCH_ASSOC);
		if(isset($rowCheckConst["id_conttranqr"])){
			$arrayResult['RESPONSE'] = "มีรหัสรายการนี้เเล้ว กรุณาตรวจสอบและลองใหม่อีกครั้ง";
			$arrayResult['INPUT_ERROR'] = 'trans_code_qr';
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			$updateConstants = $conmysql->prepare("UPDATE gcconttypetransqrcode SET
											trans_code_qr = :trans_code_qr, trans_desc_qr = :trans_desc_qr, operation_desc_th = :operation_desc_th, operation_desc_en =:operation_desc_en
											WHERE id_conttranqr = :id_conttranqr");
			if($updateConstants->execute([
				':trans_code_qr' => $dataComing["trans_code_qr"],
				':trans_desc_qr' => $dataComing["trans_desc_qr"],
				':operation_desc_th' => $dataComing["operation_desc_th"],
				':operation_desc_en' => $dataComing["operation_desc_en"],
				':id_conttranqr' => $dataComing["id_conttranqr"]
			])){
				$arrayStruc = [
						':menu_name' => "constantqrcode",
						':username' => $payload["username"],
						':use_list' =>"insert GCCONTTYPETRANSQRCODE",
						':details' => "trans_code_qr => ".$dataComing["trans_code_qr"].
									" trans_desc_qr => ".$dataComing["trans_desc_qr"].
									" operation_desc_th => ".$dataComing["operation_desc_th"].
									" operation_desc_en => ".$dataComing["operation_desc_en"].
									" id_conttranqr => ".$dataComing["id_conttranqr"]
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>