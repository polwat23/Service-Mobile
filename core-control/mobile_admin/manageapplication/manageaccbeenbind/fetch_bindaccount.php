<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$bindaccount = array();
		$fetchAdmin = $conmysql->prepare("SELECT id_bindaccount,member_no,deptaccount_no_coop,deptaccount_no_bank,mobile_no,bank_account_name,bank_account_name_en,bind_date,unbind_date,bindaccount_status
										FROM gcbindaccount");
		$fetchAdmin->execute();
		while($rowAdmin = $fetchAdmin->fetch(PDO::FETCH_ASSOC)){
			$bindaccount["IB_BINDACCOUNT"] = $rowAdmin["id_bindaccount"];
			$bindaccount["MEMBER_NO"] = $rowAdmin["member_no"];
			$bindaccount["DEPTACCOUNT_NO_COOP"] = $rowAdmin["deptaccount_no_coop"];
			$bindaccount["DEPTACCOUNT_NO_BANK"] = $rowAdmin["deptaccount_no_bank"];
			$bindaccount["BANK_ACCOUNT_NAME"] = $rowAdmin["bank_account_name"];
			$bindaccount["BANK_ACCOUNT_NAME_EN"] = $rowAdmin["bank_account_name_en"];
			$bindaccount["BIND_DATE"] = $rowAdmin["bind_date"];
			$bindaccount["UNBIN_DATE"] = $rowAdmin["unbind_date"];
			$bindaccount["BINDACCOUNT_STATUS"] = $rowAdmin["bindaccount_status"];
			$arrayBindaccount[] = $bindaccount;
		}
		$arrayResult['BINACCOUNT_DATA'] = $arrayBindaccount;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
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
