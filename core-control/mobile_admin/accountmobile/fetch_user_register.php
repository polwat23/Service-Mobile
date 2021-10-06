<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'accountmobile','createuserpassword')){
		$arrayGroup = array();	
		$arrayMembName =  array();	
		
		$fetchUser= $conmssql->prepare("select member_no from gcmemberaccount");
		$fetchUser->execute();
		while($rowUser = $fetchUser->fetch(PDO::FETCH_ASSOC)){
		
			$arrayMembName[$rowUser["member_no"]] = $rowUser["member_no"];
		}
		
		$fetchUserAccount = $conmssqlcoop->prepare("SELECT co.member_id as member_no  ,co.prefixname as prename_desc, co.firstname as memb_name ,co.lastname as memb_surname ,
													co.email , co.telephone as phone_number , co.member_in as register_date
													FROM cocooptation co WHERE status ='AC' ");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["MEMBER_COUNT"] = $arrayMembName[$rowUserlogin["member_no"]] ;
			$arrGroupUserAcount["FULL_NAME"] = $rowUserlogin["prename_desc"].$rowUserlogin["memb_name"].' '.$rowUserlogin["memb_surname"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["TEL"] = $rowUserlogin["phone_number"];
			$arrGroupUserAcount["EMAIL"] = $rowUserlogin["email"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["register_date"],'d m Y',true);
			$arrayGroup[] = $arrGroupUserAcount;
		}
		
		$arrayResult["USER_ACCOUNT"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>