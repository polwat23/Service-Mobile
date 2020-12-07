<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','body_root_','subject'],$dataComing)){
	if($func->check_permission_core($payload,'sms','loanbillingemail')){
		$destination = array();
		$arrGroupAllSuccess = array();
		$arrGroupAllFailed = array();
		if(isset($dataComing["destination"]) && sizeof($dataComing["destination"]) > 0){
			foreach($dataComing["destination"] as $dest){
				$destination[] = strtolower($lib->mb_str_pad($dest));
			}
			if(sizeof($destination) > 0){
				$arrDataPre = array();
				$getDataForPreview = $conoracle->prepare("SELECT MEMBER_NO
														FROM kpkepnotenoughmoneytosms
														WHERE member_no IN('".implode("','",$destination)."')
														GROUP BY member_no");
				$getDataForPreview->execute();
				while($rowDataPre = $getDataForPreview->fetch(PDO::FETCH_ASSOC)){
					$mailAsset = $func->getMailAddress($rowDataPre["MEMBER_NO"]);
					if(isset($mailAsset[0]["EMAIL"]) && $mailAsset[0]["EMAIL"] != ""){
						$arrTarget = array();
						$arrTarget["FULL_NAME"] = $arrDataPre[$mailAsset[0]["MEMBER_NO"]]["FULL_NAME"];
						$arrMessage = $lib->mergeTemplate($dataComing["subject"],$dataComing["body_root_"],$arrTarget);
						$arrGroupSuccess["DESTINATION"] = $mailAsset[0]["MEMBER_NO"];
						$arrGroupSuccess["REF"] = $mailAsset[0]["MEMBER_NO"];
						$arrGroupSuccess["MAIL"] = $mailAsset[0]["EMAIL"];
						$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
						$arrGroupAllSuccess[] = $arrGroupSuccess;
					}else{
						$arrGroupCheckSend["DESTINATION"] = $mailAsset[0]["MEMBER_NO"];
						$arrGroupCheckSend["REF"] = $mailAsset[0]["MEMBER_NO"];
						$arrGroupCheckSend["MESSAGE"] = 'ไม่มีข้อมูลอีเมลผู้รับอยู่ในระบบ';
						$arrGroupAllFailed[] = $arrGroupCheckSend;
					}
				}
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			$getDataForPreview = $conoracle->prepare("SELECT MEMBER_NO
													FROM kpkepnotenoughmoneytosms
													WHERE mailpost_status = '0' 
													GROUP BY member_no");
			$getDataForPreview->execute();
			while($rowDataPre = $getDataForPreview->fetch(PDO::FETCH_ASSOC)){
				$mailAsset = $func->getMailAddress($rowDataPre["MEMBER_NO"]);
				if(isset($mailAsset[0]["EMAIL"]) && $mailAsset[0]["EMAIL"] != ""){
					$arrTarget = array();
					$arrTarget["FULL_NAME"] = $rowDataPre["FULL_NAME"];
					$arrMessage = $lib->mergeTemplate($dataComing["subject"],$dataComing["body_root_"],$arrTarget);
					$arrGroupSuccess["DESTINATION"] = $mailAsset[0]["MEMBER_NO"];
					$arrGroupSuccess["REF"] = $mailAsset[0]["MEMBER_NO"];
					$arrGroupSuccess["MAIL"] = $mailAsset[0]["EMAIL"];
					$arrGroupSuccess["MESSAGE"] = $arrMessage["BODY"].'^'.$arrMessage["SUBJECT"];
					$arrGroupAllSuccess[] = $arrGroupSuccess;
				}else{
					$arrGroupCheckSend["DESTINATION"] = $mailAsset[0]["MEMBER_NO"];
					$arrGroupCheckSend["REF"] = $mailAsset[0]["MEMBER_NO"];
					$arrGroupCheckSend["MESSAGE"] = 'ไม่มีข้อมูลอีเมลผู้รับอยู่ในระบบ';
					$arrGroupAllFailed[] = $arrGroupCheckSend;
				}
			}
			$arrayResult['SUCCESS'] = $arrGroupAllSuccess;
			$arrayResult['FAILED'] = $arrGroupAllFailed;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>