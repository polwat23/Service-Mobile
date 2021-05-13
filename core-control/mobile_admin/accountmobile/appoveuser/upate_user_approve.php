<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','data'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','approveusermobile')){
		$conmysql->beginTransaction();
		$template = $func->getTemplateSystem('TempPassword');
		$arrayDataTemplate = array();
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
		
		foreach($dataComing["data"] as $data_value){
			$password = $lib->randomText('all',6);
			$arrayDataTemplate["TEMP_PASSWORD"] = $password;
			$arrayDataTemplate["FULL_NAME"] = (isset($data_value["NAME"]) ? $data_value["NAME"] : $data_value["MEMBER_NO"]);
			$arrayDataTemplate["USERNAME"] = $data_value["MEMBER_NO"];
			
			$update = $conmysql->prepare("UPDATE gcmemberaccount SET ACCOUNT_STATUS = :ACCOUNT_STATUS ,TEMPPASS = :PASSWORD  WHERE MEMBER_NO = :MEMBER_NO");
			if($update->execute([
						':ACCOUNT_STATUS' => $data_value["ACCOUNT_STATUS"],
						':PASSWORD' => password_hash($password,PASSWORD_DEFAULT),
						':MEMBER_NO' => $data_value["MEMBER_NO"]
					])){
				$arrayResult['RESULT'] = TRUE;
				$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
				$arrMailStatus = $lib->sendMail($data_value["EMAIL"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถอนุมัติได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}		
		$conmysql->commit();		
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] =  json_encode($dataComing);
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>

