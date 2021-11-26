<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','req_status','reqdoc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqdocumentformlist')){
		if($dataComing["req_status"] == '1'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqdocformonline SET req_status = '1',remark = :remark,username = :username WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => 'reqdocumentformlist',
					':username' => $payload["username"],
					':use_list' => 'change status reqdocformonline',
					':details' => $dataComing["reqdoc_no"]." => status : 1 , remark = ".($dataComing["remark"] ?? "")
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				require_once('../../../../include/exit_footer.php');
				
			}
		}else if($dataComing["req_status"] == '7'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqdocformonline SET req_status = '7',remark = :remark,username = :username WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => 'reqdocumentformlist',
					':username' => $payload["username"],
					':use_list' => 'change status reqdocformonline',
					':details' => $dataComing["reqdoc_no"]." => status : 7 , remark = ".($dataComing["remark"] ?? "")
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
				require_once('../../../../include/exit_footer.php');
				
			}
		}else if($dataComing["req_status"] == '-9'){
			$approveReqLoan = $conmysql->prepare("UPDATE gcreqdocformonline SET req_status = '-9',remark = :remark,username = :username WHERE reqdoc_no = :reqdoc_no");
			if($approveReqLoan->execute([
				':remark' => $dataComing["remark"] ?? null,
				':username' => $payload["username"],
				':reqdoc_no' => $dataComing["reqdoc_no"]
			])){
				$arrayStruc = [
					':menu_name' => 'reqdocumentformlist',
					':username' => $payload["username"],
					':use_list' => 'change status reqdocformonline',
					':details' => $dataComing["reqdoc_no"]." => status : -9 , remark = ".($dataComing["remark"] ?? "")
				];
				
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกใบคำขอนี้ได้ กรุณาติดต่อผู้พัฒนา";
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