<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocumentsfile')){
		$arrDetail = array();
		$fetchDocument = $conmysql->prepare("select dm.doc_no,dm.docgrp_no,dm.create_date,dm.doc_address,dm.open_status , dm.member_no,
											gb.prename_desc, gb.memb_name, gb.memb_ename , gb.suffname_desc 
										  FROM doclistmaster dm LEFT JOIN gcmembonlineregis gb ON dm.member_no = gb.member_no ");
		$fetchDocument->execute();
		while($rowDocument= $fetchDocument->fetch(PDO::FETCH_ASSOC)){
			$arrGroupDocument["MEMBER_NO"] = $rowDocument["member_no"];
			$arrGroupDocument["COOP_NAME"] = $rowDocument["prename_desc"].$rowDocument["memb_name"].' '.$rowDocument["suffname_desc"];
			$arrGroupDocument["DOC_NO"] = $rowDocument["doc_no"];
			$arrGroupDocument["DOCGRP_NO"] = $rowDocument["docgrp_no"];
			$arrGroupDocument["CREATE_DATE"] = $lib->convertdate($rowDocument["create_date"],'d M Y',true);
			$arrGroupDocument["DOC_ADDRESS"] = $rowDocument["doc_address"]; 
			$arrGroupDocument["OPEN_STATUS"] = $rowDocument["open_status"];
			$arrDetail[] = $arrGroupDocument;
		}
		$arrayResult["DATA_DOCUMENT"] = $arrDetail;
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