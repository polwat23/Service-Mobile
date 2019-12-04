<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_ASSIST"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_ASSIST"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchAssType = $conoracle->prepare("select ast.ASSISTTYPE_DESC,ast.ASSISTTYPE_CODE,asm.ASSCONTRACT_NO from asscontmaster asm LEFT JOIN 
												assucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE WHERE member_no = :member_no and asscont_status = 1");
		$fetchAssType->execute([
			':member_no' => $member_no
		]);
		$arrGroupAss = array();
		while($rowAssType = $fetchAssType->fetch()){
			$arrAss = array();
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrGroupAss[] = $arrAss;
		}
		if(sizeof($arrGroupAss) > 0 || isset($new_token)){
			$arrayResult["ASSIST"] = $arrGroupAss;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>