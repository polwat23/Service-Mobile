<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$arrayAdmin = array();
		$fetchAdmin = $conmssql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '1'");
		$fetchAdmin->execute();
		while($rowAdmin = $fetchAdmin->fetch(PDO::FETCH_ASSOC)){
			$getName = $conmssqlcoop->prepare("SELECT Prefixname , Firstname , Lastname , email , telephone 
											FROM CoCooptation
											WHERE member_id = :member_no");
			$getName->execute([':member_no' => $rowAdmin["member_no"]]);
			$rowname = $getName->fetch(PDO::FETCH_ASSOC);	
			$arraymember = array();
			$arraymember["member_no"] = $rowAdmin["member_no"];
			$arraymember["fullname"] = $rowname["Prefixname"].$rowname["Firstname"].' '.$rowname["Lastname"];
			$arrayAdmin[] = $arraymember;
		}
		$arrayResult['ADMIN'] = $arrayAdmin;
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
