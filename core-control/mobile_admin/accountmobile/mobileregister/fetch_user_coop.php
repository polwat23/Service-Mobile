<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id' , 'ref_memno'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){
		$arrayAccount = array();
		$fetchAccount = $conmysql->prepare("SELECT member_no, ref_memno, password, acc_name, acc_surname, phone_number, email,  account_status, register_date ,position_desc
											FROM gcmemberaccount where account_status  <> '8'  AND ref_memno = :ref_memno");
		$fetchAccount->execute([':ref_memno' => $dataComing["ref_memno"]]);
		while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
			$arrUserAcount = array();
			$arrUserAcount["REF_MEMNO"] = $rowUser["ref_memno"];
			$arrUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrUserAcount["PASSWORD"] = $rowUser["password"];
			$arrUserAcount["ACC_NAME"] = $rowUser["acc_name"] ?? "";
			$arrUserAcount["ACC_SURNAME"] = $rowUser["acc_surname"] ?? "";
			$arrUserAcount["PHONE_NUMBER"] = $rowUser["phone_number"];
			$arrUserAcount["POSITION_DESC"] = $rowUser["position_desc"];
			$arrUserAcount["EMAIL"] = $rowUser["email"];
			$arrUserAcount["ACCOUNTSTATUS"] = $rowUser["account_status"];
			$arrUserAcount["REGISTERDATE"] = $lib->convertdate($rowUser["register_date"],'d m Y',true);
			$arrayAccount[] = $arrUserAcount;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayAccount;		
		$arrayResult["RESULT"] = TRUE;
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