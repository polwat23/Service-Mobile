<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','bank_code','bank_name','bank_short_name','bank_format_account','bank_format_account_hide','id_palette'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount')){
		$updateConstants = $conmysql->prepare("UPDATE csbankdisplay 
											   SET bank_name = :bank_name,
												   bank_short_name = :bank_short_name,
												   bank_format_account = :bank_format_account,
												   bank_format_account_hide = :bank_format_account_hide,
												   id_palette = :id_palette
											   WHERE bank_code = :bank_code");
		if($updateConstants->execute([
			':bank_code' => $dataComing["bank_code"],
			':bank_name' => $dataComing["bank_name"],
			':bank_short_name' => $dataComing["bank_short_name"],
			':bank_format_account' => $dataComing["bank_format_account"],
			':bank_format_account_hide' => $dataComing["bank_format_account_hide"],
			':id_palette' => $dataComing["id_palette"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
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