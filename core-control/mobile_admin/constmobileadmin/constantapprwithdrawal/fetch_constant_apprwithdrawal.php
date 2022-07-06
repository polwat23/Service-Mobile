<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT cawd.id_apprwd_constant, cawd.minimum_value, cawd.maximum_value, cawd.member_no,css.id_section_system,css.system_assign 
															FROM gcconstantapprwithdrawal cawd 
															LEFT JOIN coresectionsystem css ON css.id_section_system = cawd.id_section_system 
															WHERE cawd.is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_APPRWD_CONSTANT"] = $rowMenuMobile["id_apprwd_constant"];
			$arrConstans["MINIMUM_VALUE"] = $rowMenuMobile["minimum_value"];
			$arrConstans["MAXIMUM_VALUE"] = $rowMenuMobile["maximum_value"];
			$arrConstans["MEMBER_NO"] = $rowMenuMobile["member_no"];
			$arrConstans["ID_SECTION_SYSTEM"] = $rowMenuMobile["id_section_system"];
			$arrConstans["SYSTEM_ASSIGN"] = $rowMenuMobile["system_assign"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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