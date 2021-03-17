<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		
		$insertMemberCoop = $conmysql->prepare("INSERT INTO `gcmembonlineregis`(`member_no`, `prename_desc`, `memb_name`, `prename_edesc`, `memb_ename`, `approve_id`, `remark`) 
							VALUES (:member_no ,:prename_desc ,:memb_name ,:prename_edesc , :memb_ename, :approve_id, :remark)");
		if($insertMemberCoop->execute([
			':member_no' => $dataComing["member_no"],
			':prename_desc' => $dataComing["prename_desc"],
			':memb_name' => $dataComing["memb_name"],
			':prename_edesc'=> $dataComing["prename_edesc"],
			':memb_ename'=> $dataComing["memb_ename"],
			':approve_id'=> $payload["username"],
			':remark'=> $dataComing["remark"],
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถสร้างบัญชีได้";
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