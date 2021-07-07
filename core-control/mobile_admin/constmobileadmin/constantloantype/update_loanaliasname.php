<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','loan_typecode'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttypeloan')){
		$fetchLoanTypeCheck = $conoracle->prepare("SELECT LOANTYPE_CODE,IS_CREDITLOAN,IS_LOANREQUEST,IS_ESTIMATE_CREDITLOAN,LOANTYPE_ALIAS_NAME 
												FROM gcconstanttypeloan
												WHERE LOANTYPE_CODE = :loan_typecode");
		$fetchLoanTypeCheck->execute([
			':loan_typecode' => $dataComing["loan_typecode"] 
		]);
		$rowLoantype = $fetchLoanTypeCheck->fetch(PDO::FETCH_ASSOC);
		if(isset($rowLoantype["LOANTYPE_CODE"])){
			$update_email = $conoracle->prepare("UPDATE gcconstanttypeloan 
																	SET LOANTYPE_ALIAS_NAME = :alias_name
																	WHERE  LOANTYPE_CODE = :loan_typecode;");
			if($update_email->execute([
				':alias_name' => $dataComing["alias_name"],
				':loan_typecode' => $dataComing["loan_typecode"] 
			])){
				$arrayStruc = [
					':menu_name' => "constanttypeloan",
					':username' => $payload["username"],
					':use_list' => "change loan alias_name",
					':details' => ($dataComing["old_alias_name"] ?? ' ').' => '.$dataComing["alias_name"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$insertConstants = $conoracle->prepare("INSERT gcconstanttypeloan(LOANTYPE_ALIAS_NAME,LOANTYPE_CODE,IS_CREDITLOAN,IS_LOANREQUEST,IS_ESTIMATE_CREDITLOAN)
															VALUES (:alias_name,:loan_typecode,'0','0','0')");
			if($insertConstants->execute([
				':alias_name' => $dataComing["alias_name"],
				':loan_typecode' => $dataComing["loan_typecode"] 
			])){
				$arrayStruc = [
					':menu_name' => "constanttypeloan",
					':username' => $payload["username"],
					':use_list' => "change loan alias_name",
					':details' => ($dataComing["old_alias_name"] ?? ' ').' => '.$dataComing["alias_name"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}
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















