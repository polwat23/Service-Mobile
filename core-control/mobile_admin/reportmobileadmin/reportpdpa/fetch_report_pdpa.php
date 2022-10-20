<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','pdpareport')){
		$arrGrp = array();
		$arrayPDPDAGrp = array();
		$arrayExecute = array();
		$default_grp = "v3";
		if(isset($dataComing["policy_id"]) && $dataComing["policy_id"] != ''){
			if($dataComing["policy_id"] != 'all'){
				$arrayExecute[':policy_id'] = $dataComing["policy_id"];
			}
		}else{
			$arrayExecute[':policy_id'] = $default_grp;
		}
		$getPDPA = $conmysql->prepare("SELECT member_no, is_accept, accept_date, cancel_date, url_policy, policy_id FROM gcacceptpolicy WHERE 1=1".
					($dataComing["policy_id"] != 'all' ? " and policy_id = :policy_id" : "").
					" and member_no not in('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4')
					order by accept_date desc");
		$getPDPA->execute($arrayExecute);
		while($rowPDPA = $getPDPA->fetch(PDO::FETCH_ASSOC)){
			$arrayPDPA = array();
			$fetchMember = $conmssql->prepare("SELECT mp.PRENAME_SHORT,mb.MEMB_NAME,mb.MEMB_SURNAME
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
			$fetchMember->execute([
				':member_no' => $rowPDPA["member_no"]
			]);
			$rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC);
			$arrayPDPA["MEMBER_NO"] = $rowPDPA["member_no"];
			$arrayPDPA["MEMBER_FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrayPDPA["IS_ACCEPT"] = $rowPDPA["is_accept"];
			$arrayPDPA["ACCEPT_DATE"] = $lib->convertdate($rowPDPA["accept_date"],"D m Y", true);
			$arrayPDPA["ACCEPT_DATE_RAW"] = $rowPDPA["accept_date"];
			$arrayPDPA["CANCEL_DATE"] = $lib->convertdate($rowPDPA["cancel_date"],"D m Y", true);
			$arrayPDPA["URL_POLICY"] = $rowPDPA["url_policy"];
			$arrayPDPA["POLICY_ID"] = $rowPDPA["policy_id"];
			$arrayPDPDAGrp[] = $arrayPDPA;
		}
		$getGroup = $conmysql->prepare("select policy_id from gcacceptpolicy group by policy_id");
		$getGroup->execute();
		while($rowGroup = $getGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGrp[] = $rowGroup["policy_id"];
		}
		
		$arrayResult['PDPA_LIST'] = $arrayPDPDAGrp;
		$arrayResult['PDPA_GROUP'] = $arrGrp;
		$arrayResult['DEFAULT_GROUP'] = $default_grp;
		$arrayResult['RESULT'] = TRUE;
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