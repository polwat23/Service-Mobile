<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','apv_docno','user_score','apv_score','user_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ApproveWithdrawal')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conoracle->beginTransaction();
		$use_user_score = 0;
		$getCoopId = $conoracle->prepare("SELECT coop_id FROM dpdeptapprove WHERE apv_docno = :apv_docno");
		$getCoopId->execute([':apv_docno' => $dataComing["apv_docno"]]);
		$rowCoopId = $getCoopId->fetch(PDO::FETCH_ASSOC);
		$getScoreRemain = $conoracle->prepare("SELECT SUM(user_score) as score_remain,NVL(MAX(seq_no),0) as seq_no FROM dpdeptapprovedet WHERE apv_docno = :apv_docno");
		$getScoreRemain->execute([':apv_docno' => $dataComing["apv_docno"]]);
		$rowScoreRemain = $getScoreRemain->fetch(PDO::FETCH_ASSOC);
		$ApvSeqDet = $conoracle->prepare("INSERT INTO dpdeptapprovedet(coop_id,apv_docno,seq_no,apv_id,user_score) 
														VALUES(:coop_id,:apv_docno,:seq_no,:username,:user_score)");
		if($ApvSeqDet->execute([
			':coop_id' => $rowCoopId["COOP_ID"],
			':apv_docno' => $dataComing["apv_docno"],
			':seq_no' => $rowScoreRemain["SEQ_NO"] + 1,
			':username' => $dataComing["user_id"],
			':user_score' => $dataComing["user_score"]
		])){
			if($dataComing["apv_score"] <= $rowScoreRemain["SCORE_REMAIN"] || $dataComing["user_score"] > $dataComing["apv_score"]){
				$updateCompleteApv = $conoracle->prepare("UPDATE dpdeptapprove SET apv_status = 1,approve_date = sysdate WHERE apv_docno = :apv_docno");
				if($updateCompleteApv->execute([
					':apv_docno' => $dataComing["apv_docno"]
				])){
					$conoracle->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conoracle->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1034",
						":error_desc" => "อนุมัติรายการเงินฝากไม่ได้เพราะ Update ลงตาราง dpdeptapprove ไม่ได้"."\n"."Query => ".$updateCompleteApv->queryString."\n"."Param => ". json_encode([
							':apv_docno' => $dataComing["apv_docno"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "อนุมัติรายการเงินฝากไม่ได้เพราะ Update ลง dpdeptapprove ไม่ได้"."\n"."Query => ".$updateCompleteApv->queryString."\n"."Param => ". json_encode([
						':apv_docno' => $dataComing["apv_docno"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1034";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conoracle->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conoracle->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1034",
				":error_desc" => "อนุมัติรายการเงินฝากไม่ได้เพราะ Insert ลงตาราง dpdeptapprovedet ไม่ได้"."\n"."Query => ".$ApvSeqDet->queryString."\n"."Param => ". json_encode([
					':coop_id' => $rowCoopId["COOP_ID"],
					':apv_docno' => $dataComing["apv_docno"],
					':seq_no' => $rowScoreRemain["SEQ_NO"] + 1,
					':username' => $dataComing["user_id"],
					':user_score' => $dataComing["user_score"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "อนุมัติรายการเงินฝากไม่ได้เพราะ Insert ลง dpdeptapprovedet ไม่ได้"."\n"."Query => ".$ApvSeqDet->queryString."\n"."Param => ". json_encode([
				':coop_id' => $rowCoopId["COOP_ID"],
				':apv_docno' => $dataComing["apv_docno"],
				':seq_no' => $rowScoreRemain["SEQ_NO"] + 1,
				':username' => $dataComing["user_id"],
				':user_score' => $dataComing["user_score"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1034";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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
