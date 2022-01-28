<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','showinvoice')){
		$getBalanceLoan = $conoracle->prepare("SELECT DISTINCT LCNOTICEMTHRECV.NOTICE_DOCNO,   
												LCNOTICEMTHRECV.MEMBER_NO,   
												LCNOTICEMTHRECV.NOTICE_DATE,   
												LCNOTICEMTHRECV.NOTICEDUE_DATE,    
												MBUCFPRENAME.PRENAME_DESC,   
												MBMEMBMASTER.MEMB_NAME,   
												MBUCFPRENAME.SUFFNAME_DESC,
												LCNOTICEMTHRECV.IS_VIEW
												FROM LCNOTICEMTHRECV,       MBMEMBMASTER,   MBUCFPRENAME ,LCCONTMASTER  
												WHERE ( LCNOTICEMTHRECV.MEMBRANCH_ID = MBMEMBMASTER.BRANCH_ID )   
												AND ( LCNOTICEMTHRECV.MEMBER_NO = MBMEMBMASTER.MEMBER_NO )   
												AND ( MBMEMBMASTER.PRENAME_CODE = MBUCFPRENAME.PRENAME_CODE )    
												AND (LCCONTMASTER.CONTRACT_STATUS=1)  
												AND ( lcnoticemthrecv.notice_status = 8) 
												AND  (mbmembmaster.resign_status = 0)
												order by LCNOTICEMTHRECV.MEMBER_NO");
		$getBalanceLoan->execute();
		while($rowInvoiceLoan = $getBalanceLoan->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$arrBalDetail["NOTICE_DATE"] = $lib->convertdate($rowInvoiceLoan["NOTICE_DATE"],'d M Y');
			$arrBalDetail["NOTICE_DOCNO"] = $rowInvoiceLoan["NOTICE_DOCNO"];
			$arrBalDetail["MEMBER_NO"] = $rowInvoiceLoan["MEMBER_NO"];
			$arrBalDetail["IS_VIEW"] = $rowInvoiceLoan["IS_VIEW"];
			$arrBalDetail["FULLNAME"] = $rowInvoiceLoan["PRENAME_DESC"].$rowInvoiceLoan["MEMB_NAME"].' '.$rowInvoiceLoan["SUFFNAME_DESC"];
			$arrDetail[] = $arrBalDetail;
		}
		
		$arrayResult["DATA_INVOICE"] = $arrDetail;
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