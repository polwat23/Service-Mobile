<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequestTrack')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrpTrack = array();
		$getTypeMember = $conoracle->prepare("SELECT MEMBCAT_CODE FROM MBMEMBMASTER WHERE member_no = :member_no");
		$getTypeMember->execute([':member_no' => $member_no]);
		$rowTypeMember = $getTypeMember->fetch(PDO::FETCH_ASSOC);
		$getTrackReqStatus = $conoracle->prepare("SELECT AO.*,AT.ASSISTTYPE_DESC FROM ASSREQMASTERONLINE AO 
													LEFT JOIN ASSUCFASSISTTYPE AT ON AO.ASSISTTYPE_CODE = AT.ASSISTTYPE_CODE
													WHERE AO.member_no = :member_no");
		$getTrackReqStatus->execute([':member_no' => $member_no]);
		while($rowTrackStatus = $getTrackReqStatus->fetch(PDO::FETCH_ASSOC)){
			$arrTrackStatus = array();
			$arrTrackStatus["ASSIST_DOCNO"] = $rowTrackStatus["ASSIST_DOCNO"];
			$arrTrackStatus["WELFARE_DESC"] = $rowTrackStatus["ASSISTTYPE_DESC"];
			$arrTrackStatus["ASSIST_YEAR"] = $rowTrackStatus["ASSIST_YEAR"];
			$arrTrackStatus["REQ_STATUS"] = $rowTrackStatus["REQ_STATUS"];
			$arrTrackStatus["REQ_STATUS_DESC"] = $configError["REQ_WELFARE_STATUS"][0][$rowTrackStatus["REQ_STATUS"]][0][$lang_locale];
			$arrTrackStatus["ASSISTTYPE_CODE"] = $rowTrackStatus["ASSISTTYPE_CODE"];
			$getFormatForm = $conoracle->prepare("SELECT gw.label_text,gw.input_name,gw.input_format
												FROM gcformatreqwelfare gw LEFT JOIN gcconstantwelfare gf ON gw.id_const_welfare = gf.id_const_welfare
												WHERE gf.welfare_type_code = :assisttype_code and (gf.member_cate_code = :cate_code OR gf.member_cate_code = 'AL') 
												and gf.is_use = '1'");
			$getFormatForm->execute([
				':assisttype_code' => $rowTrackStatus["ASSISTTYPE_CODE"],
				':cate_code' => $rowTypeMember["MEMBCAT_CODE"]
			]);
			if($getFormatForm->rowCount() > 0){
				while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
					$arrayDetailAss = array();
					$arrayDetailAss["LABEL"] = $rowForm["LABEL_TEXT"];
					if(isset($rowForm["INPUT_FORMAT"])){
						$arrayDetailAss["VALUE"] = (json_decode($rowForm["INPUT_FORMAT"],true))[$rowTrackStatus[strtoupper($rowForm["INPUT_NAME"])]];
					}else{
						$arrayDetailAss["VALUE"] = $rowTrackStatus[strtoupper($rowForm["INPUT_NAME"])];
					}
					$arrTrackStatus["DETAIL_ASSIST"][] = $arrayDetailAss;
				}
			}
			$arrGrpTrack[] = $arrTrackStatus;
		}
		$arrayResult['TRACK_WELFARE'] = $arrGrpTrack;
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
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>