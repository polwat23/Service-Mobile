<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','showinvoice')){
		
		$fetchEndDate = $conmysql->prepare("SELECT end_date FROM gcendclosedate");
		$fetchEndDate->execute();
		$rowEndDate = $fetchEndDate->fetch(PDO::FETCH_ASSOC);
		
		
		$getBalanceLoan = $conoracle->prepare("SELECT  CM.MEMBER_NO , MP.PRENAME_DESC,   MB.MEMB_NAME,   MP.SUFFNAME_DESC,CM.DOC_DATE ,
											CM.FILE_NAME ,CM.SERVER_PATH , CM.DOC_ID , CM.LONCONNO as NOTICE_DOCNO, CM.IS_VIEW 
											FROM CMNOTATIONREGISFILE CM,MBMEMBMASTER MB,   MBUCFPRENAME MP
											WHERE CM.MEMBER_NO = MB.MEMBER_NO
											AND MB.PRENAME_CODE = MP.PRENAME_CODE  ");
		$getBalanceLoan->execute();
		while($rowInvoiceLoan = $getBalanceLoan->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$arrBalDetail["NOTICE_DATE"] = $lib->convertdate($rowInvoiceLoan["DOC_DATE"],'d M Y');
			$arrBalDetail["NOTICE_DOCNO"] = $rowInvoiceLoan["NOTICE_DOCNO"];
			$arrBalDetail["MEMBER_NO"] = $rowInvoiceLoan["MEMBER_NO"];
			$arrBalDetail["IS_VIEW"] = $rowInvoiceLoan["IS_VIEW"];
			$arrBalDetail["FULLNAME"] = $rowInvoiceLoan["PRENAME_DESC"].$rowInvoiceLoan["MEMB_NAME"].' '.$rowInvoiceLoan["SUFFNAME_DESC"];
			$arrDetail[] = $arrBalDetail;
		}
		
		$arrayResult["DATA_INVOICE"] = $arrDetail;
		$arrayResult["END_DATE"] = $rowEndDate["end_date"];
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