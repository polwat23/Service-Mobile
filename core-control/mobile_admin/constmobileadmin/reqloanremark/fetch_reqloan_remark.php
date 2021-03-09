<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqloanremark')){
		$arrGrp = array();
		$getRemark = $conmysql->prepare("SELECT id_reqremark, remark_title, remark_detail FROM gcreqloanremark WHERE is_use = '1'");
		$getRemark->execute();
		while($rowRemark = $getRemark->fetch(PDO::FETCH_ASSOC)){
			$arrRemark = array();
			$arrRemark["ID_REQREMARK"] = $rowRemark["id_reqremark"];
			$arrRemark["REMARK_TITLE"] = $rowRemark["remark_title"];
			$arrRemark["REMARK_DETAIL"] = $rowRemark["remark_detail"];
			$arrGrp[] = $arrRemark;
		}
		
		$arrayResult['REMARK'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
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