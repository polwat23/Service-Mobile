<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && 
$lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'sms',$conmysql)){
		$fetchTopic = $conmysql->prepare("SELECT stp.smstemplate_name,sm.sms_menu_name,csy.system_assign FROM smstopicmatchtemplate stmt 
											INNER JOIN smstemplate stp ON  stmt.id_smstemplate = stp.id_smstemplate
											INNER JOIN smsmenu sm ON stmt.id_smsmenu = sm.id_smsmenu INNER JOIN corepermissionmenu cpm ON sm.id_coremenu = cpm.id_coremenu and cpm.is_use = '1'
											INNER JOIN coresectionsystem csy ON cpm.id_section_system = csy.id_section_system and csy.is_use = '1'
											WHERE stmt.is_use = '1' GROUP BY smstemplate_name,sms_menu_name,system_assign");
		$fetchTopic->execute();
		$arrAllTopic = array();
		while($rowTopic = $fetchTopic->fetch()){
			$arrayTopic = array();
			$arrayGroupTopic = array();
			$arrayTopic["TEMPLATE_NAME"] = $rowTopic["smstemplate_name"];
			$arrayTopic["TOPIC_NAME"] = $rowTopic["sms_menu_name"];
			$arrayTopic["SYSTEM_CONTROL"] = $rowTopic["system_assign"];
			$arrayGroupTopic["TOPIC_NAME"] = $rowTopic["sms_menu_name"];
			$arrayGroupTopic["TEMPLATE_NAME"] = $rowTopic["smstemplate_name"];
			if(array_search($rowTopic["sms_menu_name"],array_column($arrAllTopic,'TOPIC_NAME')) === False && 
			array_search($rowTopic["smstemplate_name"],array_column($arrAllTopic,'TEMPLATE_NAME')) === False){
				($arrayGroupTopic['SYSTEM_CONTROL'])[] = $arrayTopic["SYSTEM_CONTROL"];
				$arrAllTopic[] = $arrayGroupTopic;
			}else{
				($arrAllTopic[array_search($rowTopic["sms_menu_name"],array_column($arrAllTopic,'TOPIC_NAME'))]["SYSTEM_CONTROL"])[] = $arrayTopic["SYSTEM_CONTROL"];
				($arrAllTopic[array_search($rowTopic["smstemplate_name"],array_column($arrAllTopic,'TEMPLATE_NAME'))]["SYSTEM_CONTROL"])[] = $arrayTopic["SYSTEM_CONTROL"];
			}
		}
		$arrayResult['TOPIC'] = $arrAllTopic;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>