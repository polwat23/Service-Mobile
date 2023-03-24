<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','usernotregistered')){
		$arrayUserRegister = array();
		$fetchUserAccount = $conmysql->prepare("SELECT member_no FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayUserRegister[] = $rowUserRegis["member_no"];
		}
		$arrayGroup = array();
		$fetchUserNotRegis = $conmssql->prepare("SELECT mb.MEMBER_NO,mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,mb.MEMBER_DATE
												      ,mb.addr_mobilephone as MEM_TELMOBILE,mg.MEMBGROUP_DESC
												 FROM mbmembmaster mb 
												LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												WHERE mb.resign_status = '0'");
		$fetchUserNotRegis->execute();
		while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
			if(!in_array($rowUserNotRegis["MEMBER_NO"],$arrayUserRegister)){
				$arrayUserNotRegister = array();
				$arrayUserNotRegister["MEMBER_NO"] = $rowUserNotRegis["MEMBER_NO"];
				$arrayUserNotRegister["NAME"] = $rowUserNotRegis["PRENAME_DESC"].$rowUserNotRegis["MEMB_NAME"]." ".$rowUserNotRegis["MEMB_SURNAME"];
				$arrayUserNotRegister["MEMBER_DATE"] = $lib->convertdate($rowUserNotRegis["MEMBER_DATE"],'D m Y');
				$arrayUserNotRegister["TEL"] = isset($rowUserNotRegis["MEM_TELMOBILE"]) ? $lib->formatphone(preg_replace('/[^0-9]/', '', $rowUserNotRegis["MEM_TELMOBILE"]),'-') : "-";
				$arrayUserNotRegister["EMAIL"] = "-";
				$arrayUserNotRegister["MEMBGROUP_DESC"] = $rowUserNotRegis["MEMBGROUP_DESC"]??"-";
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