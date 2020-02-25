<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept
													WHERE allow_transaction = '1' and is_use = '1'");
		$fetchAccAllowTrans->execute();
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayDept[] = "'".$rowAccAllow["dept_type_code"]."'";
			}
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.prncbal
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													and dpm.membcat_code = dpt.membcat_code
													WHERE dpt.depttype_code IN(".implode(',',$arrayDept).") and dpm.member_no = :member_no");
			$getDataBalAcc->execute([':member_no' => $member_no]);
			while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
				$arrAccAllow = array();
				$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
				$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
				$arrAccAllow["BALANCE"] = $rowDataAccAllow["PRNCBAL"];
				$arrAccAllow["BALANCE_FORMAT"] = number_format($rowDataAccAllow["PRNCBAL"],2);
				$arrGroupAccAllow[] = $arrAccAllow;
			}
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$getAccFav = $conmysql->prepare("SELECT fav_refno,name_fav,destination FROM gcfavoritelist WHERE member_no = :member_no and flag_trans = 'TRN'");
				$getAccFav->execute([':member_no' => $payload["member_no"]]);
				while($rowAccFav = $getAccFav->fetch(PDO::FETCH_ASSOC)){
					$arrFavMenu = array();
					$arrFavMenu["NAME_FAV"] = $rowAccFav["name_fav"];
					$arrFavMenu["FAV_REFNO"] = $rowAccFav["fav_refno"];
					$arrFavMenu["DESTINATION"] = $lib->formataccount($rowAccFav["destination"],$func->getConstant('dep_format'));
					$arrFavMenu["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowAccFav["destination"],$func->getConstant('hidden_dep'));
					$arrGroupAccFav[] = $arrFavMenu;
				}
			}
			if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccFav) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult["FORMAT_DEPT"] = $func->getConstant('dep_format');
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>