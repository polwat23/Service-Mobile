<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember')){
		$arrayGroupAll = array();
		$arrayExecute = array();
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = $dataComing["member_no"];
		}
		if(isset($dataComing["member_name"]) && $dataComing["member_name"] != ''){
			$arrName = explode(' ',$dataComing["member_name"]);
			if(isset($arrName[1])){
				$arrayExecute[':member_name'] = "'%".TRIM($arrName[0])."%'";
				$arrayExecute[':member_surname'] = '%'.TRIM($arrName[1]).'%';
			}else{
				$arrayExecute[':member_name'] = "'%".TRIM($arrName[0])."%'";
			}
		}
	
		if(empty($dataComing["member_no"]) && empty($dataComing["member_name"]) && empty($dataComing["province"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		$fetchMember = $conmssqlcoop->prepare("SELECT prefixname as PRENAME_SHORT,firstname as MEMB_NAME,lastname as MEMB_SURNAME,
											birthdate as BIRTH_DATE,telephone as MEM_TELMOBILE,member_in as MEMBER_DATE,member_id as MEMBER_NO,
											ADDRESS1 , ADDRESS2
											FROM cocooptation
											WHERE 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and member_id = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (LTRIM(RTRIM(firstname)) LIKE ".$arrayExecute[':member_name'] : null).
											(isset($arrayExecute[':member_surname']) ? " and LTRIM(RTRIM(lastname)) LIKE ".$arrayExecute[':member_surname'].")" : 
											(isset($arrayExecute[':member_name']) ? " OR LTRIM(RTRIM(lastname)) LIKE ".$arrayExecute[':member_name'].")" : null)));
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["ADDRESS"] = $rowMember["ADDRESS1"]." ".$rowMember["ADDRESS2"];
			$arrayGroup["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayGroup["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayGroup["NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrayGroup["TEL"] = $rowMember["MEM_TELMOBILE"];
			$arrayGroup["EMAIL"] = "-";
			$arrayGroup["MEMBER_NO"] = $rowMember["MEMBER_NO"];
			$arrayGroup["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],'D m Y');
			$arrayGroupAll[] = $arrayGroup;
		}
		$arrayResult["MEMBER_DATA"] = $arrayGroupAll;
		$arrayResult["RESULTDD"] = $arrayExecute;
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