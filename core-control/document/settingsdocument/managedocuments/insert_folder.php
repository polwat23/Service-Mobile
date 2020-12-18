<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		if(isset($dataComing["ref_doc_system"]) && $dataComing["ref_doc_system"] !== ""){
			$insertDocumentSystems = $conmysql->prepare("INSERT INTO docgroupcontrol(docgrp_no, doc_system, docgrp_name, 
												docsub_prefix, docgrp_ref, create_by, doc_no_prefix) 
												VALUES (:docgrp_no, :doc_system, :docgrp_name,
												:docsub_prefix, :docgrp_ref, :create_by, :doc_no_prefix)");
			if($insertDocumentSystems->execute([
				':docgrp_no' =>  $dataComing["folder_key"],
				':doc_system' =>  $dataComing["ref_doc_system"],
				':docgrp_name' =>  $dataComing["folder_name"],
				':docsub_prefix' =>  $dataComing["docgrp_no_prefix"],
				':docgrp_ref' =>  $dataComing["docgrp_ref"],
				':create_by' =>  $payload["username"],
				':doc_no_prefix' =>  $dataComing["doc_no_prefix"],
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มแฟ้มเอกสารได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มแฟ้มเอกสารได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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

