<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','moratoriumreq')){
		$cancel_refno = date("Ymd").($lib->randomText('all',4));
		$approveReqLoan = $conmysql->prepare("UPDATE gcmoratorium SET is_moratorium = '0',cancel_refno = :cancel_refno WHERE is_moratorium = '1'");
		if($approveReqLoan->execute([
			':cancel_refno' => $cancel_refno
		])){
			$arrayStruc = [
				':menu_name' => 'moratoriumreq',
				':username' => $payload["username"],
				':use_list' => 'change status moratorium',
				':details' => "cancel approve all => ref_no = ".$cancel_refno
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกคำขอได้ กรุณาติดต่อผู้พัฒนา";
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