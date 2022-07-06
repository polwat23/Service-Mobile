<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsinsure',$conoracle)){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
																		id_smsconstantinsure,
																		insure_itemtype_code,
																		allow_smsconstantinsure,
																		allow_notify
																	FROM
																		 smsconstantinsure
																	ORDER BY insure_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTINSURE"] = $rowMenuMobile["ID_SMSCONSTANTINSURE"];
			$arrConstans["INSITEMTYPE_CODE"] = $rowMenuMobile["INSURE_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTINSURE"] = $rowMenuMobile["ALLOW_SMSCONSTANTINSURE"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT INSITEMTYPE_CODE,INSITEMTYPE_DESC FROM INSUCFINSITEMTYPE ORDER BY INSITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_SMSCONSTANTINSURE"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTINSURE"] = $arrayChkG[array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTINSURE"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["INSITEMTYPE_CODE"],array_column($arrayChkG,'INSITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["INSITEMTYPE_CODE"] = $rowDepttype["INSITEMTYPE_CODE"];
			$arrayDepttype["INSITEMTYPE_DESC"] = $rowDepttype["INSITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["INSURE_DATA"] = $arrayGroup;
		
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