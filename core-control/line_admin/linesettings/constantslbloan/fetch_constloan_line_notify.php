<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'line','constantslinenotifyloan')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
												id_lbconstantloan,
												loan_itemtype_code,
												allow_lbconstantloan
											FROM
												lbconstantloan
											ORDER BY loan_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_LBCONSTANTLOAN"] = $rowMenuMobile["id_lbconstantloan"];
			$arrConstans["LOANITEMTYPE_CODE"] = $rowMenuMobile["loan_itemtype_code"];
			$arrConstans["ALLOW_LBCONSTANTLOAN"] = $rowMenuMobile["allow_lbconstantloan"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT LOANITEMTYPE_CODE,LOANITEMTYPE_DESC FROM LNUCFLOANITEMTYPE ORDER BY LOANITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_LBCONSTANTLOAN"] = 0;
				}else{
					$arrayDepttype["ALLOW_LBCONSTANTLOAN"] = $arrayChkG[array_search($rowDepttype["LOANITEMTYPE_CODE"],array_column($arrayChkG,'LOANITEMTYPE_CODE'))]["ALLOW_LBCONSTANTLOAN"];
				}
				
			$arrayDepttype["LOANITEMTYPE_CODE"] = $rowDepttype["LOANITEMTYPE_CODE"];
			$arrayDepttype["LOANITEMTYPE_DESC"] = $rowDepttype["LOANITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["LINE_NOTIFY_LOAN_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>