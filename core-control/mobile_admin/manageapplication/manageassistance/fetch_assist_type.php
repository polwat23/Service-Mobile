<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$arrayWelfare = array();
		$fetchWelfare = $conmysql->prepare("SELECT 
												gwf.id_const_welfare,
												gwf.welfare_type_code,
												gwf.welfare_type_desc,
												gwf.member_cate_code,
												gwf.is_use,
												gwf.create_date,
												gwf.update_date
											FROM gcconstantwelfare gwf
											WHERE gwf.is_use = '1'");
		$fetchWelfare->execute();
		while($dataWelfare = $fetchWelfare->fetch(PDO::FETCH_ASSOC)){
			$welfare = array();
			$welfare["ID_CONST_WELFARE"] = $dataWelfare["id_const_welfare"];
			$welfare["WELFARE_TYPE_CODE"] = $dataWelfare["welfare_type_code"];
			$welfare["WELFARE_TYPR_DESC"] = $dataWelfare["welfare_type_desc"];
			$welfare["MEMBER_CATE_CODE"] = $dataWelfare["member_cate_code"];
			$welfare["IS_USE"] = $dataWelfare["is_use"];
			$welfare["CREATE_DATE"] = $lib->convertdate($dataWelfare["create_date"],'d m Y H-i-s',true); 
			$welfare["UPDATE_DATE"] = $lib->convertdate($dataWelfare["update_date"],'d m Y H-i-s',true);
			$arrayWelfare[] = $welfare;
		}
		$arrayResult['WELFARE_DATA'] = $arrayWelfare;
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

