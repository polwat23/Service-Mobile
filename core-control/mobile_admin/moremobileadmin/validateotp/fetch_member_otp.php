<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','validateotp')){
		$member_no = $lib->mb_str_pad($dataComing["member_no"]);
		$getDataMember = $conmssql->prepare("SELECT MEMB_NAME,MEMB_SURNAME,MEM_TELMOBILE FROM mbmembmaster WHERE member_no = :member_no");
		$getDataMember->execute([':member_no' => $member_no]);
		$dataMember = array();
		while($rowDataMember = $getDataMember->fetch(PDO::FETCH_ASSOC)){
			$member = array();
			$member["FULL_NAME"] = $rowDataMember["MEMB_NAME"].' '.$rowDataMember["MEMB_SURNAME"];
			$member["TEL"] = $rowDataMember["MEM_TELMOBILE"];
			$member["MEMBER_NO"] = $member_no;
			$dataMember[] = $member;
		}
		$arrayResult['RESULT'] = TRUE;
		$arrayResult['DATAMEMBER'] = $dataMember;
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