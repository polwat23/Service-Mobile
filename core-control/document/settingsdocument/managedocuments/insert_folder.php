<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		$checkFolder = $conmysql->prepare("SELECT docgrp_no,is_use
												FROM docgroupcontrol
												WHERE docgrp_no = :docgrp_no");
		$checkFolder->execute([
			':docgrp_no' =>  $dataComing["folder_key"]
		]);
		
		if($checkFolder->rowCount() > 0){
			$rowCheckFolder = $checkFolder->fetch(PDO::FETCH_ASSOC);
			if($rowCheckFolder["is_use"] == '0'){
				$deleteDocuments = $conmysql->prepare("UPDATE docgroupcontrol SET is_use = '1',create_date = NOW(),is_lock = '0',
													docgrp_name = :docgrp_name,docgrp_ref = :docgrp_ref,create_by = :create_by,
													menu_component = :menu_component
													WHERE is_use = '0' AND docgrp_no = :docgrp_no");
				if($deleteDocuments->execute([
					':docgrp_no' =>  $dataComing["folder_key"],
					':docgrp_name' =>  $dataComing["folder_name"],
					':docgrp_ref' =>  (isset($dataComing["docgrp_ref"]) && $dataComing["docgrp_ref"] != "") ? $dataComing["docgrp_ref"] : NULL,
					':create_by' =>  $payload["username"],
					':menu_component' => (isset($dataComing["menu_component"]) && $dataComing["menu_component"] != "") ? $dataComing["menu_component"] : NULL
				])){
					$arrayStruc = [
						':menu_name' => "managedocuments",
						':username' => $payload["username"],
						':use_list' =>"add folder",
						':details' => "docgrp_no => ".$dataComing["folder_key"]." docgrp_name => ".$dataComing["folder_name"]." docgrp_ref => ".$dataComing["docgrp_ref"] ?? ""." create_by => ".$payload["username"]." menu_component=> ".$dataComing["menu_component"] ?? "",
					];
					$log->writeLog('editdocument',$arrayStruc);	

					$arrayResult["RESULT"] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มแฟ้มเอกสารได้ กรุณาติดต่อผู้พัฒนา ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['FORM_ERROR'] = "folder_key";
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มแฟ้มเอกสารได้เนื่องจากมี Key แฟ้มเอกสารนี้อยู่แล้ว";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$insertDocumentSystems = $conmysql->prepare("INSERT INTO docgroupcontrol(docgrp_no, docgrp_name, 
												docgrp_ref, create_by, menu_component) 
												VALUES (:docgrp_no, :docgrp_name,
												:docgrp_ref, :create_by, :menu_component)");
			if($insertDocumentSystems->execute([
				':docgrp_no' =>  $dataComing["folder_key"],
				':docgrp_name' =>  $dataComing["folder_name"],
				':docgrp_ref' =>  (isset($dataComing["docgrp_ref"]) && $dataComing["docgrp_ref"] != "") ? $dataComing["docgrp_ref"] : NULL,
				':create_by' =>  $payload["username"],
				':menu_component' => (isset($dataComing["menu_component"]) && $dataComing["menu_component"] != "") ? $dataComing["menu_component"] : NULL
			])){				
				$arrayStruc = [
					':menu_name' => "managedocuments",
					':username' => $payload["username"],
					':use_list' =>"add folder",
					':details' => "docgrp_no => ".$dataComing["folder_key"]." docgrp_name => ".$dataComing["folder_name"]." docgrp_ref => ".$dataComing["docgrp_ref"] ?? ""." create_by => ".$payload["username"]." menu_component=> ".$dataComing["menu_component"] ?? "",
				];
				$log->writeLog('editdocument',$arrayStruc);	

				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มแฟ้มเอกสารได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['checkFolder'] = $checkFolder->rowCount() > 0;
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
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

