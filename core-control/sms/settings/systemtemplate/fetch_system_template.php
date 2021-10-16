<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managesystemtemplate',$conoracle)){
		$arrGroupSysTemplate = array();
		$fetchSysTemplate = $conoracle->prepare("SELECT component_system,subject,body,id_systemplate,is_use FROM smssystemtemplate WHERE is_use <> '-9'");
		$fetchSysTemplate->execute();
		
		while($rowSysTemplate = $fetchSysTemplate->fetch(PDO::FETCH_ASSOC)){
			$arraySysTem = array();
			$arraySysTem["COMPONENT"] = $rowSysTemplate["COMPONENT_SYSTEM"];
			$arraySysTem["SUBJECT"] = $rowSysTemplate["SUBJECT"];
			
			if($rowSysTemplate["ID_SYSTEMPLATE"] == '8'|| $rowSysTemplate["ID_SYSTEMPLATE"] == '11' || $rowSysTemplate["ID_SYSTEMPLATE"] =='28'){
				$arraySysTem["BODY"] = file_get_contents(__DIR__.'/../../../..'.$rowSysTemplate["BODY"]);
			}else{
				$arraySysTem["BODY"] = $rowSysTemplate["BODY"];
			}
			
			$arraySysTem["IS_USE"] = $rowSysTemplate["IS_USE"];
			$arraySysTem["ID_SYSTEMPLATE"] = $rowSysTemplate["ID_SYSTEMPLATE"];
			$arrGroupSysTemplate[] = $arraySysTem;
		}
		$arrayResult['SYSTEM_TEMPLATE'] = $arrGroupSysTemplate;
		
		if(sizeof($arrGroupSysTemplate) > 0){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			http_response_code(204);
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