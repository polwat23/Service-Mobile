<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_SHARE"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_SHARE"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupAccAllow = array();
		$arrayAcc = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_transaction = '1' and gad.is_use = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch()){
				$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
			}
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.prncbal
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													and dpm.membcat_code = dpt.membcat_code
													WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).")");
			$getDataBalAcc->execute();
			while($rowDataAccAllow = $getDataBalAcc->fetch()){
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
			$fetchShare = $conoracle->prepare("SELECT sharestk_amt FROM shsharemaster WHERE member_no = :member_no");
			$fetchShare->execute([':member_no' => $member_no]);
			$rowShare = $fetchShare->fetch();
			$arrayShare = array();
			$arrayShare["SHARE_AMT"] = number_format($rowShare["SHARESTK_AMT"]*10,2);
			$arrayShare["MEMBER_NO"] = $payload["member_no"];
			if(sizeof($arrGroupAccAllow) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['SHARE'] = $arrayShare;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
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