<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CoopInfo')){
		$member_no = $payload["ref_memno"];
		$arrayGroupDoc = array();
		$fetchDoc = $conmysql->prepare("SELECT dm.member_no , dm.doc_address ,dc.docgrp_name,dm.update_date
										FROM doclistmaster dm LEFT JOIN docgroupcontrol dc ON dm.docgrp_no = dc.docgrp_no
										where dm.member_no= :member_no
										ORDER BY dm.update_date DESC");
		$fetchDoc->execute([':member_no' => $member_no]);
		while($rowDoc = $fetchDoc->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["MEMBER_NO"] = $rowDoc["member_no"];
			$arrayDoc["DOC_ADDRESS"] = $rowDoc["doc_address"];
			$arrayDoc["DOCGRP_NAME"] = $rowDoc["docgrp_name"];
			$arrayDoc["UPDATE_DATE"] = $rowDoc["update_date"];
			$arrayGroupDoc[] = $arrayDoc;
		}
		$arrayResult['DOCUMENT'] = $arrayGroupDoc;
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