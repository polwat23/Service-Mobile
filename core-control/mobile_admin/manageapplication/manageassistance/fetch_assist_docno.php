<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance',$conmssql)){
		$arrayWelfare = array();
		$fetchWelfare = $conmysql->prepare("SELECT assist_docno, assisttype_code, member_no, assist_name, assist_lastname, age, father_name, mother_name,
											academy_name, education_level, assist_amt, assist_year, req_date, contractdoc_url, req_status
											FROM assreqmasteronline WHERE req_status <> '1' ");
		$fetchWelfare->execute();
		while($dataWelfare = $fetchWelfare->fetch(PDO::FETCH_ASSOC)){
			$welfare = array();
			$getFullName  = $conmssql->prepare("SELECT MP.PRENAME_DESC , MB.MEMB_NAME ,MB.MEMB_SURNAME  , MP.PRENAME_DESC , MG.MEMBGROUP_DESC , mb.MEM_TEL
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
													LEFT JOIN MBUCFMEMBGROUP MG ON MB.MEMBGROUP_CODE = MG.MEMBGROUP_CODE
													WHERE MB.MEMBER_NO = :member_no ");
			$getFullName->execute([':member_no' => $dataWelfare["member_no"]]);
			$rowFullName = $getFullName->fetch(PDO::FETCH_ASSOC);
			$welfare["ASSIST_DOCNO"] = $dataWelfare["member_no"];
			$welfare["ASSISTTYPE_DESC"] = 'ทุนส่งเสริมการศึกษา';
			$welfare["MEMBER_NO"] = $dataWelfare["member_no"];
			$welfare["FULLNAME"] = $rowFullName["PRENAME_DESC"] . $rowFullName["MEMB_NAME"].' ' .$rowFullName["MEMB_SURNAME"];
			$welfare["ASSIST_NAME"] = $dataWelfare["assist_name"] .' '.$dataWelfare["assist_name"];
			$welfare["CONTRACTDOC_URL"] = $dataWelfare["contractdoc_url"];
			$welfare["REQ_DATE"] = $lib->convertdate($dataWelfare["req_date"],"D m Y");
			$arrayWelfare[] = $welfare;
		}
		$arrayResult['DOCNO_DATA'] = $arrayWelfare;
		$arrayResult['RESULT'] = TRUE;
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

