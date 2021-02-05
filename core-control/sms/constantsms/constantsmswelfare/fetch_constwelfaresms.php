<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmswelfare')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_smsconstantwelfare,
																		welfare_itemtype_code,
																		allow_smsconstantwelfare,
																		allow_notify
																	FROM
																		  smsconstantwelfare
																	ORDER BY welfare_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTWELFARE"] = $rowMenuMobile["id_smsconstantwelfare"];
			$arrConstans["ITEM_CODE"] = $rowMenuMobile["welfare_itemtype_code"];
			$arrConstans["ALLOW_SMSCONSTANTWELFARE"] = $rowMenuMobile["allow_smsconstantwelfare"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["allow_notify"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT ITEM_CODE,ITEM_DESC FROM ASSUCFASSITEMCODE ORDER BY ITEM_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_SMSCONSTANTWELFARE"] = 0;
						//$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTWELFARE"] = $arrayChkG[array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE'))]["ALLOW_SMSCONSTANTWELFARE"];
					//$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["ITEM_CODE"],array_column($arrayChkG,'ITEM_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["ITEM_CODE"] = $rowDepttype["ITEM_CODE"];
			$arrayDepttype["ITEM_DESC"] = $rowDepttype["ITEM_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["WELFARE_DATA"] = $arrayGroup;
		
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