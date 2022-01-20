<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqdocumentformlist')){
		$arrGrp = array();
		
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$getAllReqDocno = $conmysql->prepare("SELECT df.reqdoc_no, df.reqdocformtype_id, df.document_url, df.req_status, df.remark, df.request_date, df.update_date, dt.documenttype_desc, df.member_no
													FROM gcreqdocformonline df
													LEFT JOIN gcreqdocformtype dt ON dt.reqdocformtype_id = df.reqdocformtype_id
													WHERE req_status = :req_status");
			$getAllReqDocno->execute([
				':req_status' => $dataComing["req_status"]
			]);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqdoc_no"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["REQDOCFORMTYPE_ID"] = $rowDocno["reqdocformtype_id"];
				$arrDocno["DOCUMENT_URL"] = $rowDocno["document_url"];
				$arrDocno["DOCUMENTTYPE_DESC"] = $rowDocno["documenttype_desc"];
				$arrDocno["REMARK"] = $rowDocno["remark"];
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				$arrDocno["UPDATE_DATE"] = $lib->convertdate($rowDocno["update_date"],'d m Y',true);
				$arrDocno["REQ_STATUS"] = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}
				$arrGrp[] = $arrDocno;
			}
		}else{
			$getAllReqDocno = $conmysql->prepare("SELECT df.reqdoc_no, df.reqdocformtype_id, df.document_url, df.req_status, df.remark, df.request_date, df.update_date, dt.documenttype_desc, df.member_no
													FROM gcreqdocformonline df
													LEFT JOIN gcreqdocformtype dt ON dt.reqdocformtype_id = df.reqdocformtype_id
													WHERE req_status = '8'");
			$getAllReqDocno->execute();
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqdoc_no"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["REQDOCFORMTYPE_ID"] = $rowDocno["reqdocformtype_id"];
				$arrDocno["DOCUMENT_URL"] = $rowDocno["document_url"];
				$arrDocno["DOCUMENTTYPE_DESC"] = $rowDocno["documenttype_desc"];
				$arrDocno["REMARK"] = $rowDocno["remark"];
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				$arrDocno["UPDATE_DATE"] = $lib->convertdate($rowDocno["update_date"],'d m Y',true);
				$arrDocno["REQ_STATUS"] = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}
				$arrGrp[] = $arrDocno;
			}
		}
		$arrayResult['REQ_LIST'] = $arrGrp;
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