<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ShareInfo')){
		$member_no = $payload["ref_memno"];
	
		$getSharemasterinfo = $conoracle->prepare("SELECT MB.MEMBER_NO,   
												MP.PRENAME_DESC||MB.MEMB_NAME ||' '|| MP.SUFFNAME_DESC AS COOP_NAME,   
												(SHR.SHARESTK_AMT  * SHY.UNITSHARE_VALUE) AS  SHARESTK_AMT, 
												SHR.SHRPAR_STKCHKVALUE,
												SHR.SHRPAR_STKBIZVALUE,
												SHR .SHRPAR_VALUE,
												(CASE WHEN SHR.SHRPAR_STATUS = 0 THEN 'พอดีเกณฑ์'
													WHEN SHR.SHRPAR_STATUS = -1 THEN 'ต่ำกว่าเกณฑ์' 
														WHEN SHR.SHRPAR_STATUS = 1 THEN 'เกินกว่าเกณฑ์' ELSE '' END) AS SHRPAR_STATUS,
												(CASE WHEN (SHR.SHRPAR_VALUE - SHR.SHRPAR_STKCHKVALUE) < 0 THEN  (SHR.SHRPAR_VALUE - SHR.SHRPAR_STKCHKVALUE) * -1
												ELSE (SHR.SHRPAR_VALUE - SHR.SHRPAR_STKCHKVALUE) END ) AS  SHRPAR_AMT
												FROM MBMEMBMASTER MB,   SHSHAREMASTER SHR,   SHSHARETYPE SHY,   MBUCFPRENAME MP  
												WHERE  MP.PRENAME_CODE = MB.PRENAME_CODE   
												AND MB.MEMBER_NO = SHR.MEMBER_NO   
												AND SHR.SHARETYPE_CODE = SHY.SHARETYPE_CODE    
												AND MB.MEMBER_NO = :member_no");
		$getSharemasterinfo->execute([':member_no' => $member_no]);
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		if($rowMastershare){
			$arrGroupStm = array();
			$arrayResult['MEMBER_NO'] = $rowMastershare["MEMBER_NO"];
			$arrayResult['COOP_NAME'] = $rowMastershare["COOP_NAME"];
			$arrayResult['SHARESTK_AMT'] = number_format($rowMastershare["SHARESTK_AMT"],2);
			$arrayResult['SHRPAR_STKCHKVALUE'] = number_format($rowMastershare["SHRPAR_STKCHKVALUE"]/500);
			$arrayResult['SHRPAR_VALUE'] = number_format($rowMastershare["SHRPAR_VALUE"],2);
			$arrayResult['SHRPAR_AMT'] = number_format($rowMastershare["SHRPAR_AMT"],2);
			$arrayResult['SHRPAR_STKBIZVALUE'] = number_format($rowMastershare["SHRPAR_STKBIZVALUE"],2);
			$arrayResult['SHRPAR_STATUS'] = $rowMastershare["SHRPAR_STATUS"];
			$arrayResult['SHRPAR_YEAR'] = date('Y')+543;
			
			
			$limit = $func->getConstant('limit_stmshare');
			$arrayResult['LIMIT_DURATION'] = $limit;
			/*if($lib->checkCompleteArgument(["date_start"],$dataComing)){
				$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
			}else{
				$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
			}*/
			$date_before = date('1968-08-01');
			if($lib->checkCompleteArgument(["date_end"],$dataComing)){
				$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
			}else{
				$date_now = date('Y-m-d');
			}
			$getShareStatement = $conoracle->prepare("SELECT SHSHARECERTIFICATE.SHARECERT_TYPE,   
										SHSHARECERTIFICATE.CERTSERIAL_NO,   
										TRIM(SHSHARECERTIFICATE.SHARECERT_NO) as SHARECERT_NO,   
										SHSHARECERTIFICATE.SHARE_AMT,   
										SHSHARECERTIFICATE.SHARECERT_DATE,   
										SHSHARECERTIFICATE.SHAREBUY_DATE, 
										SHSHARETYPE.UNITSHARE_VALUE,   
										TRIM(SHSHARECERTIFICATE.SHARENO_STARTPREFIX)||'-'||SHSHARECERTIFICATE.SHARENO_START as SHARENO_START,
										TRIM(SHSHARECERTIFICATE.SHARENO_ENDPREFIX)||'-'||SHSHARECERTIFICATE.SHARENO_END   as SHARENO_ENDPREFIX,
										SHSHARECERTIFICATE.SHARE_AMT * SHSHARETYPE.UNITSHARE_VALUE as SHARESTK_AMT
										FROM SHSHARECERTIFICATE, SHSHAREMASTER, SHSHARETYPE  
										WHERE ( SHSHARECERTIFICATE.MEMBRANCH_ID = SHSHAREMASTER.BRANCH_ID )   
										AND ( SHSHARECERTIFICATE.MEMBER_NO = SHSHAREMASTER.MEMBER_NO )   
										AND ( SHSHAREMASTER.BRANCH_ID = SHSHARETYPE.BRANCH_ID )   
										AND ( SHSHAREMASTER.SHARETYPE_CODE = SHSHARETYPE.SHARETYPE_CODE )   
										AND (  shsharemaster.member_no = :member_no)   
										AND ( shsharecertificate.sharecert_status = 1 )  
										AND (shsharecertificate.SHAREBUY_DATE BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD'))
										ORDER BY SHSHARECERTIFICATE.SHAREBUY_DATE");
			$getShareStatement->execute([
				':member_no' => $member_no ,
				':datebefore' => $date_before,
				':datenow' => $date_now
			]);
			while($rowStm = $getShareStatement->fetch(PDO::FETCH_ASSOC)){
				$arrayStm = array();
				$arrayStm["SHARECERT_NO"] = $rowStm["SHARECERT_NO"];
				$arrayStm["CERTSERIAL_NO"] = $rowStm["CERTSERIAL_NO"];
				$arrayStm["SHARECERT_DATE"] = $lib->convertdate($rowStm["SHARECERT_DATE"],'D m Y');
				$arrayStm["SHAREBUY_DATE"] = $lib->convertdate($rowStm["SHAREBUY_DATE"],'D m Y');
				$arrayStm["SHARENO_START"] = $rowStm["SHARENO_START"];
				$arrayStm["SHARENO_ENDPREFIX"] = $rowStm["SHARENO_ENDPREFIX"];
				$arrayStm["SHARE_AMT"] = $rowStm["SHARE_AMT"];
				$arrayStm["SUM_SHARE_AMT"] = number_format($rowStm["SHARESTK_AMT"],2);
				$arrGroupStm[] = $arrayStm;
			}
			$arrayResult['STATEMENT'] = $arrGroupStm;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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