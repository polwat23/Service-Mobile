<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','usernotregistered')){
		$arrayUserRegister = array();
		$fetchUserAccount = $conmssql->prepare("SELECT member_no FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayUserRegister[] = $rowUserRegis["member_no"];
		}
		$arrayGroup = array();
		$fetchUserNotRegis = $conmssqlcoop->prepare("SELECT co.member_id as MEMBER_NO  ,co.prefixname as PRENAME_DESC, co.firstname as MEMB_NAME ,co.lastname as MEMB_SURNAME ,
													 co.EMAIL , co.telephone as PHONE_NUMBER , co.member_in as MEMBER_DATE
													 FROM cocooptation co WHERE status ='AC'");
		$fetchUserNotRegis->execute();
		while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
			if(!in_array($rowUserNotRegis["MEMBER_NO"],$arrayUserRegister)){
				$arrayUserNotRegister = array();
				$arrayUserNotRegister["MEMBER_NO"] = $rowUserNotRegis["MEMBER_NO"];
				$arrayUserNotRegister["NAME"] = $rowUserNotRegis["PRENAME_DESC"].$rowUserNotRegis["MEMB_NAME"]." ".$rowUserNotRegis["MEMB_SURNAME"];
				$arrayUserNotRegister["MEMBER_DATE"] = $lib->convertdate($rowUserNotRegis["MEMBER_DATE"],'D m Y');
				$arrayUserNotRegister["TEL"] = $rowUserNotRegis["PHONE_NUMBER"];
				$arrayUserNotRegister["EMAIL"] =  $rowUserNotRegis["EMAIL"];
				$arrayGroup[] = $arrayUserNotRegister;
			}
		}
		$arrayResult["USER_NOT_REGISTER"] = $arrayGroup;
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