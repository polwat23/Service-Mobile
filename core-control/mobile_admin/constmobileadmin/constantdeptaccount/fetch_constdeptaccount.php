<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT cad.id_accountconstant,cad.dept_type_code,cad.member_cate_code,cad.dept_type_desc,cad.id_palette,cad.allow_transaction,
										pc.type_palette, pc.color_main,pc.color_secon,pc.color_deg,pc.color_text,cad.is_use as cad_is_use
										FROM gcconstantaccountdept cad 
										JOIN gcpalettecolor pc ON pc.id_palette = cad.id_palette
										WHERE cad.is_use = '1' OR cad.is_use = '0' AND pc.is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch()){
			$arrConstans = array();
			$arrConstans["ID_ACCCONSTANT"] = $rowMenuMobile["id_accountconstant"];
			$arrConstans["DEPT_TYPE_CODE"] = $rowMenuMobile["dept_type_code"];
			$arrConstans["MEMBER_CATE_CODE"] = $rowMenuMobile["member_cate_code"];
			$arrConstans["DEPT_TYPE_CODE"] = $rowMenuMobile["dept_type_desc"];
			$arrConstans["ID_PALETTE"] = $rowMenuMobile["id_palette"];
			$arrConstans["ALLOW_TRANSACTION"] = $rowMenuMobile["allow_transaction"];
			$arrConstans["TYPE_PALETTE"] = $rowMenuMobile["type_palette"];
			$arrConstans["COLOR_MAIN"] = $rowMenuMobile["color_main"];
			$arrConstans["COLOR_SECON"] = $rowMenuMobile["color_secon"];
			$arrConstans["COLOR_DEG"] = $rowMenuMobile["color_deg"];
			$arrConstans["COLOR_TEXT"] = $rowMenuMobile["color_text"];
			$arrConstans["DEPT_IS_USE"] = $rowMenuMobile["cad_is_use"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["ACCOUNT_DATA"] = $arrayGroup;
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