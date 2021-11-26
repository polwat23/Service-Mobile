<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','documenttype_desc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqdocumentformtype')){
		
		// start เพิ่มไฟล์เเนบ
		if(isset($dataComing["documentform_upload"]) && $dataComing["documentform_upload"] != null && $dataComing["isupload"]){
			$destination = __DIR__.'/../../../../resource/documentonlinetype';
			$random_text = $lib->randomText('all',6);
			$file_name = $random_text."_".date("Ymd");
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_pdf($dataComing["documentform_upload"],$file_name,$destination,null);
			if($createImage){
				$pathFile = $config["URL_SERVICE"]."resource/documentonlinetype/".$createImage["normal_path"];
				
				if(isset($pathFile) && $pathFile != null){
					$pathFile = $pathFile."?".$random_text;
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอัพโหลดไฟล์แนบได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		//end เพิ่มไฟล์เเนบ
		
		if($dataComing["isupload"]){
			$doc_url = $pathFile;
		}else{
			$doc_url = $dataComing["documentform_url"];
		}
		$updateConst = $conmysql->prepare("INSERT INTO gcreqdocformtype (documenttype_desc, documentform_url) VALUES (:documenttype_desc, :documentform_url)");
		if($updateConst->execute([
			':documenttype_desc' => $dataComing["documenttype_desc"],
			':documentform_url' => $doc_url
		])){
			$arrayStruc = [
				':menu_name' => 'constantreqdocumentformtype',
				':username' => $payload["username"],
				':use_list' => 'add gcreqdocformtype',
				':details' => $payload["username"]." => add documenttype_desc : ".($dataComing["documentform_url"] ?? "").", documentform_url = ".($dataComing["documentform_url"] ?? "")
			];
			$log->writeLog('manageuser',$arrayStruc);
		}else{
			$arrayResult['updateConst'] = $updateConst;
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการใบคำขอ กรุณาติดต่อผู้พัฒนา";
			require_once('../../../../include/exit_footer.php');
			
		}
			
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