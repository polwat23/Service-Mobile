<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_LOAN"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_LOAN"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrLoanGrp = array();
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
			$getAccFav = $conmysql->prepare("SELECT gts.destination,gfl.name_fav
												FROM gcfavoritelist gfl LEFT JOIN gctransaction gts ON gfl.ref_no = gts.ref_no
												and gfl.member_no = gts.member_no
												WHERE gfl.member_no = :member_no and gfl.is_use = '1' and gts.destination_type = '3'");
			$getAccFav->execute([':member_no' => $payload["member_no"]]);
			while($rowAccFav = $getAccFav->fetch()){
				$arrAccFav = array();
				$arrAccFav["DESTINATION"] = $rowAccFav["destination"];
				$arrAccFav["NAME_FAV"] = $rowAccFav["name_fav"];
				$arrGroupAccFav[] = $arrAccFav;
			}
			$fetchLoanRepay = $conoracle->prepare("SELECT lnt.loantype_desc,lnm.loancontract_no,lnm.principal_balance,lnm.period_payamt,lnm.last_periodpay
													FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
													WHERE member_no = :member_no and contract_status = 1");
			$fetchLoanRepay->execute([':member_no' => $member_no]);
			while($rowLoan = $fetchLoanRepay->fetch()){
				$arrLoan = array();
				$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
				$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
				$arrLoan["BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
				$arrLoan["PERIOD_ALL"] = number_format($rowLoan["PERIOD_PAYAMT"],0);
				$arrLoan["PERIOD_BALANCE"] = number_format($rowLoan["LAST_PERIODPAY"],0);
				$arrLoanGrp[] = $arrLoan;
			}
			if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccFav) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult['LOAN'] = $arrLoanGrp;
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