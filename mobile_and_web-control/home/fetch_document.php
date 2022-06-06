<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CoopInfo')){
		$member_no = $payload["ref_memno"];
		$arrayGroupDoc = array();
		$arrayDocData = array();
		$fetchDoc = $conmysql->prepare("SELECT dm.member_no , dm.doc_address ,dc.docgrp_name,dm.doc_aliasname,dm.update_date ,dc.docgrp_no
										FROM doclistmaster dm LEFT JOIN docgroupcontrol dc ON dm.docgrp_no = dc.docgrp_no
										where dm.member_no= :member_no
										ORDER BY dm.update_date DESC");
		$fetchDoc->execute([':member_no' => $member_no]);
		while($rowDoc = $fetchDoc->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["MEMBER_NO"] = $rowDoc["member_no"];
			$arrayDoc["DOC_ADDRESS"] = $rowDoc["doc_address"];
			$arrayDoc["DOCGRP_NAME"] = $rowDoc["doc_aliasname"] ?? $rowDoc["docgrp_name"];
			$arrayDoc["DOCGRP_NO"] = $rowDoc["docgrp_no"];
			$arrayDoc["UPDATE_DATE"] = $lib->convertdate($rowDoc["update_date"],'D m Y',true);;
			$arrayGroupDoc[] = $arrayDoc;
		}
		
		$getDocType = $conmysql->prepare("SELECT docgrp_name, docgrp_no FROM docgroupcontrol where menu_component = 'Coopinfo' AND is_use ='1'");
		$getDocType->execute();
		while($rowDocType = $getDocType->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["DOCGRP_NAME"] = $rowDocType["docgrp_name"];
			$arrayDoc["DOCGRP_NO"] = $rowDocType["docgrp_no"];
			$arrayDocData[] = $arrayDoc;
		}
		
		$arrayResult['DOCUMENT'] = $arrayGroupDoc;
		$arrayResult['DOCUMENT_TYPE'] = $arrayDocData;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>