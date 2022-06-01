<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = array();
		$arrayType = array();
		$arrayExecute = array();
		
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute[':start_date'] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute[':end_date'] = $dataComing["end_date"];
		}
		
		$getAllReqDocno =  $conmysql->prepare("SELECT id_mora, member_no, loancontract_no, loangroup_code, request_date, cancel_date, is_moratorium 
		FROM gcmoratorium 
		WHERE ".
		(isset($dataComing["req_status"]) && $dataComing["req_status"] == "1" ? " is_moratorium = '1'" : "is_moratorium <> '8'").
		(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
		(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null).
		" ORDER BY request_date desc");
		$getAllReqDocno->execute($arrayExecute);
		while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
			$arrDocno = array();
			$arrDocno["ID_MORA"] = $rowDocno["id_mora"];
			$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
			
			$fetchMember = $conmssql->prepare("SELECT MP.PRENAME_SHORT,MB.MEMB_NAME,MB.MEMB_SURNAME,
								MB.MEMBER_NO,MB.MEMBGROUP_CODE 
								FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
								WHERE MB.MEMBER_NO = :member_no");
			$fetchMember->execute([
				':member_no' => $rowDocno["member_no"]
			]);
			
			$arrDocno["FULLNAME"] = null;
			$arrDocno["MEMBGROUP_CODE"] = null;
			while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
				$arrDocno["FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
				$arrDocno["MEMBGROUP_CODE"] = $rowMember["MEMBGROUP_CODE"];
			}
			
			$arrDocno["LOANGROUP_CODE"] = $rowDocno["loangroup_code"];
			$arrDocno["LOANGROUP_DESC"] = $rowDocno["loangroup_code"] == '01' ? "เงินกู้ฉุกเฉิน" : "เงินกู้สามัญ";
			$arrDocno["REQUEST_DATE"] = $rowDocno["request_date"];
			$arrDocno["CANCEL_DATE"] = $rowDocno["cancel_date"];
			$arrDocno["REQ_STATUS"] = $rowDocno["is_moratorium"];
			
			if($rowDocno["is_moratorium"] == '8'){
				$arrDocno["REQ_STATUS_DESC"] = "รอยืนยัน";
			}else if($rowDocno["is_moratorium"] == '1'){
				$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
			}else{
				$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
			}
			$arrGrp[] = $arrDocno;
		}
		$arrayResult['REQ_LIST'] = $arrGrp;
		//$arrayResult['REQ_MESSAGE'] = $dataComing["is_filtered"] ? null : "รายการใบคำขอกู้ 3 เดือนล่าสุด";
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