<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$arrayWelfare = array();
		
		$getNameWelfare = $conoracle->prepare("SELECT DISTINCT aud.ASSISTTYPE_CODE,aut.ASSISTTYPE_DESC,aud.MEMBTYPE_CODE from assucfassisttype aut 
												LEFT JOIN assucfassisttypedet aud ON aut.ASSISTTYPE_CODE = aud.ASSISTTYPE_CODE
												");
		$getNameWelfare->execute();
		while($rowNameWelfare = $getNameWelfare->fetch(PDO::FETCH_ASSOC)){
			$arrayWef[$rowNameWelfare["ASSISTTYPE_CODE"]]["ASSISTTYPE_DESC"] = $rowNameWelfare["ASSISTTYPE_DESC"];
			$arrayWef[$rowNameWelfare["ASSISTTYPE_CODE"]]["MEMBTYPE_CODE"] = $rowNameWelfare["MEMBTYPE_CODE"];
		}
		$fetchWelfare = $conmysql->prepare("SELECT 
												gwf.id_const_welfare,
												gwf.welfare_type_code,
												gwf.member_cate_code
											FROM gcconstantwelfare gwf
											WHERE gwf.is_use = '1'");
		$fetchWelfare->execute();
		while($dataWelfare = $fetchWelfare->fetch(PDO::FETCH_ASSOC)){
			$welfare = array();
			$welfare["ID_CONST_WELFARE"] = $dataWelfare["id_const_welfare"];
			$welfare["WELFARE_TYPE_CODE"] = $dataWelfare["welfare_type_code"];
			$welfare["WELFARE_TYPE_DESC"] = $arrayWef[$dataWelfare["welfare_type_code"]]["ASSISTTYPE_DESC"];
			$welfare["MEMBER_CATE_CODE"] = $dataWelfare["member_cate_code"];
			$arrayWelfare[] = $welfare;
		}
		$arrayResult['WELFARE_DATA'] = $arrayWelfare;
		$arrayResult['WELFARE_LIST'] = $arrayWef;
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

