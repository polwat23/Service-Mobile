<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsshare',$conoracle)){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
																		id_smsconstantshare,
																		share_itemtype_code,
																		allow_smsconstantshare,
																		allow_notify
																	FROM
																		smsconstantshare
																	ORDER BY share_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTSHARE"] = $rowMenuMobile["ID_SMSCONSTANTSHARE"];
			$arrConstans["SHRITEMTYPE_CODE"] = $rowMenuMobile["SHARE_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTSHARE"] = $rowMenuMobile["ALLOW_SMSCONSTANTSHARE"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT SHRITEMTYPE_CODE,SHRITEMTYPE_DESC FROM SHUCFSHRITEMTYPE ORDER BY SHRITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTSHARE"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
				}
				
			$arrayDepttype["SHRITEMTYPE_CODE"] = $rowDepttype["SHRITEMTYPE_CODE"];
			$arrayDepttype["SHRITEMTYPE_DESC"] = $rowDepttype["SHRITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["SHARE_DATA"] = $arrayGroup;
		
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