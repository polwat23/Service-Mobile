<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin',$conoracle)){
		$arrayAdmin = array();
		$fetchAdmin = $conoracle->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '1'");
		$fetchAdmin->execute();
		while($rowAdmin = $fetchAdmin->fetch(PDO::FETCH_ASSOC)){
			$arrayAdmin[] = $rowAdmin["MEMBER_NO"];
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
