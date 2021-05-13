<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','mobileregister')){

		$fetchAccount = $conmysql->prepare("SELECT member_no,acc_name,acc_surname,email FROM gcmemberaccount  where member_no = :member_no");
		$fetchAccount->execute([':member_no' => $dataComing["member_no"]]);
		while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
			$arrUserAcount = array();
			$arrUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrUserAcount["EMAIL"] = $rowUser["email"];
			$arrUserAcount["NAME"] = $rowUser["acc_name"].' '.$rowUser["acc_surname"];
		}
		
		$file_name = $lib->randomText('all',6);
		$template = $func->getTemplateSystem('ForgetPassword');
		$arrayDataTemplate = array();
		$arrayDataTemplate["FULL_NAME"] = (isset($arrUserAcount["NAME"]) ? $arrUserAcount["NAME"] : $arrUserAcount["MEMBER_NO"]);
		$arrayDataTemplate["TEMP_PASSWORD"] = $file_name;
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
	
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status,temppass = :newpassword,account_status = '-9',counter_wrongpass = 0 
																 WHERE member_no = :member_no");
		if($repassword->execute([
				':newpassword' => password_hash($file_name,PASSWORD_DEFAULT),
				':member_no' => $dataComing["member_no"]
		])){
			$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
			$arrMailStatus = $lib->sendMail($arrUserAcount["EMAIL"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction);
			$arrayResult['arrMailStatus'] = $arrMailStatus;
			$arrayResult['arrResponse'] = $arrResponse;
			$arrayResult['EMAIL'] = $arrUserAcount["EMAIL"];
			/*$arrayStruc = [
				':menu_name' => $menuName,
				':username' => $payload["username"],
				':use_list' => $list_name,
				':details' => $dataComing["member_no"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);*/
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถรีเซ็ตรหัสผ่านได้ กรุณาติดต่อผู้พัฒนา";
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