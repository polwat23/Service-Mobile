<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember')){
		$arrayGroup = array();
		$arrayGroupSec = array();
		$fetchSector = $conoracle->prepare(" SELECT SECTOR_ID,SECTOR_DESC FROM  MBUCFMEMBSECTOR WHERE SECTOR_ID <> 'ฮฮ'  ORDER BY SECTOR_ID ");
		$fetchSector->execute();
		while($rowSector = $fetchSector->fetch(PDO::FETCH_ASSOC)){
			$arraySector = array();
			$arraySector["SECTOR_ID"] = $rowSector["SECTOR_ID"];
			$arraySector["SECTOR_DESC"] = $rowSector["SECTOR_DESC"];
			$arrayGroupSec[] = $arraySector;
		}
		
		
		$fetchProvince = $conoracle->prepare("SELECT province_code,province_desc FROM mbucfprovince");
		$fetchProvince->execute();
		while($rowProvince = $fetchProvince->fetch(PDO::FETCH_ASSOC)){
			$arrayProvince = array();
			$arrayProvince["PROVINCE_CODE"] = $rowProvince["PROVINCE_CODE"];
			$arrayProvince["PROVINCE_DESC"] = $rowProvince["PROVINCE_DESC"];
			$arrayGroup[] = $arrayProvince;
		}
		$arrayResult["SECTOR"] = $arrayGroupSec;
		$arrayResult["PROVINCE"] = $arrayGroup;
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