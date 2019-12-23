<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop')){
		$arrarDataAcc = array();
		$getDataAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
												and dpm.membcat_code = dpt.membcat_code
												WHERE dpm.deptaccount_no = :deptaccount_no");
		$getDataAcc->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
		$rowDataAcc = $getDataAcc->fetch();
		if(isset($rowDataAcc["DEPTTYPE_DESC"])){
			$checkAllowToTransaction = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_transaction = '1' and gad.is_use = '1'");
			$checkAllowToTransaction->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
			if($checkAllowToTransaction->rowCount() > 0){
				$arrarDataAcc["DEPTACCOUNT_NO"] = $dataComing["deptaccount_no"];
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($dataComing["deptaccount_no"],$func->getConstant('dep_format'));
				$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($dataComing["deptaccount_no"],$func->getConstant('hidden_dep'));
				$arrarDataAcc["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
				$arrarDataAcc["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
				$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0026";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "บัญชีปลายทางยังไม่ได้อนุญาตเพื่อทำธุรกรรม";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Destination deposit account does not allow for transaction";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0025";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบบัญชีปลายทาง กรุณาตรวจสอบเลขบัญชีปลายทางอีกครั้ง";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Not found destination deposit account please please recheck";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>