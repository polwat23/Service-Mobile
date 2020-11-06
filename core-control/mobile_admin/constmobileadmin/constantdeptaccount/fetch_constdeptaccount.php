<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_accountconstant,
																		dept_type_code,
																		member_cate_code,
																		allow_transaction,
																		allow_showdetail
																	FROM
																		gcconstantaccountdept
																	ORDER BY dept_type_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_ACCCONSTANT"] = $rowMenuMobile["id_accountconstant"];
			$arrConstans["DEPTTYPE_CODE"] = $rowMenuMobile["dept_type_code"];
			$arrConstans["MEMBER_TYPE_CODE"] = $rowMenuMobile["member_cate_code"];
			$arrConstans["ALLOW_TRANSACTION"] = $rowMenuMobile["allow_transaction"];
			$arrConstans["ALLOW_SHOWDETAIL"] = $rowMenuMobile["allow_showdetail"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ORDER BY DEPTTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_TRANSACTION"] = 0;
						$arrayDepttype["MEMBER_TYPE_CODE"] = 'AL';
						$arrayDepttype["ALLOW_SHOWDETAIL"] = 0;
				}else{
					$arrayDepttype["ALLOW_TRANSACTION"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_TRANSACTION"];
					$arrayDepttype["MEMBER_TYPE_CODE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["MEMBER_TYPE_CODE"];
					$arrayDepttype["ALLOW_SHOWDETAIL"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_SHOWDETAIL"];
				}
				
			$arrayDepttype["DEPTTYPE_CODE"] = $rowDepttype["DEPTTYPE_CODE"];
			$arrayDepttype["DEPTTYPE_DESC"] = $rowDepttype["DEPTTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
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
