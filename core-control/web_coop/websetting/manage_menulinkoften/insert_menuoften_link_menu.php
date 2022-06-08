<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_menu'],$dataComing)){
	
	$inserGroupoftenlink = $conmysql->prepare("INSERT INTO webcoopmenuoftenlink(                      
                                        id_menu,
										menu_order,
										create_by,
										update_by)
									VALUES(
                                        :id_menu,
										:menu_order,                                      
										:create_by,
										:update_by
									)");
	if($inserGroupoftenlink->execute([
		':id_menu' =>  $dataComing["id_menu"],
		':menu_order' =>  $dataComing["menu_order"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		$arrayResult['inserGroupoftenlink'] = [
		':id_menu' =>  $dataComing["id_menu"],
		':menu_order' =>  $dataComing["menu_order"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	];
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

