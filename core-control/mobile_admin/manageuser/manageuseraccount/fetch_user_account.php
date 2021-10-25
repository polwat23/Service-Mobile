<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayGroup = array();
		$fetchUserAccount = $conmssql->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status, user_type
												FROM gcmemberaccount WHERE user_type IN('0','1') 
												ORDER BY  member_no, register_date DESC");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$getName = $conmssqlcoop->prepare("SELECT Prefixname , Firstname , Lastname , email , telephone 
											FROM CoCooptation
											WHERE member_id = :member_no");
			$getName->execute([':member_no' => $rowUserlogin["member_no"]]);
			$rowname = $getName->fetch(PDO::FETCH_ASSOC);			
			
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["FULLNAME"]  = $rowname["Prefixname"].$rowname["Firstname"].' '.$rowname["Lastname"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowname["telephone"]);
			$arrGroupUserAcount["EMAIL"] = $rowname["email"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["register_date"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["register_channel"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["account_status"];
			$arrGroupUserAcount["ACCOUTTYPE"] = $rowUserlogin["user_type"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayGroup;
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