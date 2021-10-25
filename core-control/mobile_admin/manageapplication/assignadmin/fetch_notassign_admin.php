<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		
		$arrayNotAdmin = array();
		$fetchNotAdmin = $conmssql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '0'");
		$fetchNotAdmin->execute();
		while($rowAdmin = $fetchNotAdmin->fetch(PDO::FETCH_ASSOC)){
			$getName = $conmssqlcoop->prepare("SELECT Prefixname , Firstname , Lastname , email , telephone 
											FROM CoCooptation
											WHERE member_id = :member_no");
			$getName->execute([':member_no' => $rowAdmin["member_no"]]);
			$rowname = $getName->fetch(PDO::FETCH_ASSOC);	
			$arraymember = array();
			$arraymember["member_no"] = $rowAdmin["member_no"];
			$arraymember["fullname"] = $rowname["Prefixname"].$rowname["Firstname"].' '.$rowname["Lastname"];
			$arrayNotAdmin[] = $arraymember;
		}
		$arrayResult['NOT_ADMIN'] = $arrayNotAdmin;
		$arrayResult['RESULT'] = TRUE;
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
