<?php
require_once('../autoload.php');

//$conwc = $con->connecttowcoracle();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayDataWcGrp = array();
		$getDataWc = $conoracle->prepare("select wcdeptmaster.coop_id,
                                 (case
                                    when wcdeptmaster.coop_id = '010000' then 'ครูไทย'
                                    when wcdeptmaster.coop_id = '080000' then 'สส.ชสอ'
                                    when wcdeptmaster.coop_id = '090000' then 'สฌอน.'
                                    when wcdeptmaster.coop_id = '041001' then 'สมาคม'
                                    when wcdeptmaster.coop_id = '041002' then 'ร่วมบุญ'
                                    when wcdeptmaster.coop_id = '041003' then 'สสอ.'
                                    when wcdeptmaster.coop_id = '020000' then 'สส.สท.'
                                end) as COOPBRANCH_DESC , 
                                membtype_desc as membtype_desc ,  
                                (case
                                    when wcdeptmaster.coop_id = '010000' then 576000
                                    when wcdeptmaster.coop_id = '080000' then 576000
					 	            when wcdeptmaster.coop_id = '090000' then 576000
                                    when wcdeptmaster.coop_id = '041001' then 160000
                                    when wcdeptmaster.coop_id = '041002' then 100000
                                    when wcdeptmaster.coop_id = '041003' then 150000
                                    when wcdeptmaster.coop_id = '020000' then 400000
                                end) as payment ,
                                wcdeptmaster.deptaccount_no as deptaccount_no  ,
                                mbucfprename.prename_desc || wcdeptmaster.deptaccount_name || '  ' ||  wcdeptmaster.deptaccount_sname as FULL_NAME,
                                wcdeptmaster.deptopen_date ,
							to_char(wcdeptmaster.deptopen_date,'dd/mm/')||to_char(EXTRACT(YEAR from wcdeptmaster.deptopen_date) +543) as T_date,
                                wcdeptmaster.prncbal,wcdeptmaster.deptclose_status as wfmember_status  
                                from wcdeptmaster , mbucfprename , mbmembmaster  , wcucfmembtype 
                                where mbmembmaster.prename_code = mbucfprename.prename_code
                                and mbmembmaster.member_no = wcdeptmaster.keep_deptno
							    and wcdeptmaster.wftype_code = wcucfmembtype.membtype_code and wcdeptmaster.coop_id = wcucfmembtype.coop_id
                                and deptclose_status = 0
                                and keep_deptno = :member_no
							and wcdeptmaster.coop_id <> '080000'
                                and wcdeptmaster.wftype_code in('01','05','21') 
							union 
							select  wcdeptmaster.coop_id,
                                (case
                                    when wcdeptmaster.coop_id = '010000' then 'ครูไทย'
                                    when wcdeptmaster.coop_id = '080000' then 'สส.ชสอ'
                                    when wcdeptmaster.coop_id = '090000' then 'สฌอน.'
                                    when wcdeptmaster.coop_id = '041001' then 'สมาคม'
                                    when wcdeptmaster.coop_id = '041002' then 'ร่วมบุญ'
                                    when wcdeptmaster.coop_id = '041003' then 'สสอ.'
                                    when wcdeptmaster.coop_id = '020000' then 'สส.สท.'
                                end) as COOPBRANCH_DESC , 
                                membtype_desc as membtype_desc ,  
                                (case
                                    when wcdeptmaster.coop_id = '010000' then 576000
                                    when wcdeptmaster.coop_id = '080000' then 576000
                                    when wcdeptmaster.coop_id = '090000' then 576000
                                    when wcdeptmaster.coop_id = '041001' then 160000
                                    when wcdeptmaster.coop_id = '041002' then 100000
                                    when wcdeptmaster.coop_id = '041003' then 150000
                                    when wcdeptmaster.coop_id = '020000' then 400000
                                end) as payment ,
                                wcdeptmaster.deptaccount_no as deptaccount_no  ,
                                mbucfprename.prename_desc || wcdeptmaster.deptaccount_name || '  ' ||  wcdeptmaster.deptaccount_sname as FULL_NAME,
                                wcdeptmaster.deptopen_date ,
						to_char(wcdeptmaster.deptopen_date,'dd/mm/')||to_char(EXTRACT(YEAR from wcdeptmaster.deptopen_date) +543) as T_date,
                                  wcdeptmaster.prncbal,wcdeptmaster.deptclose_status as wfmember_status  
                                from wcdeptmaster , mbucfprename , mbmembmaster  , wcucfmembtype 
                                where mbmembmaster.prename_code = mbucfprename.prename_code
                                and mbmembmaster.member_no = wcdeptmaster.keep_deptno
							    and wcdeptmaster.wftype_code = wcucfmembtype.membtype_code and wcdeptmaster.coop_id = wcucfmembtype.coop_id
                                and deptclose_status = 0
                                and keep_deptno = :member_no
							and wcdeptmaster.coop_id = '080000'
                                and wcdeptmaster.wftype_code in('01','02','04')");
		$getDataWc->execute([':member_no' => $member_no]);
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			if(isset($rowDataWc["DEPTACCOUNT_NO"]) && $rowDataWc["DEPTACCOUNT_NO"] != ""){
				$arrayDataWc = array();
				$arrayDataWc["DEPTACCOUNT_NO"] = $lib->formataccount($rowDataWc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrayDataWc["ACCOUNT_NAME"] = $rowDataWc["FULL_NAME"];
				$arrayDataWc["CREMATION_TYPE"] = $rowDataWc["COOPBRANCH_DESC"];
				//$arrayDataWc["CREMATION_CODE"] = $rowDataWc["WFTYPE_CODE"];
				$arrayDataWc["AMOUNT_WC"] = number_format($rowDataWc["PAYMENT"],2);
				/*$getPersonAccountWC = $conwc->prepare("SELECT WCCODEPOSIT.TRANSFEREE_NAME,WCCODEPOSIT.SEQ_NO
													FROM WCDEPTMASTER LEFT JOIN WCCODEPOSIT 
													ON WCDEPTMASTER.DEPTACCOUNT_NO= WCCODEPOSIT.DEPTACCOUNT_NO 
													WHERE WCDEPTMASTER.DEPTCLOSE_STATUS = 0 AND TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :account_no
													ORDER BY WCCODEPOSIT.SEQ_NO ASC");
				$getPersonAccountWC->execute([':account_no' => TRIM($rowDataWc["DEPTACCOUNT_NO"])]);
				while($rowPerson = $getPersonAccountWC->fetch(PDO::FETCH_ASSOC)){
					if(isset($rowPerson["TRANSFEREE_NAME"]) && $rowPerson["TRANSFEREE_NAME"] != ""){
						$arrPerson = array();
						$arrPerson["NAME"] = $rowPerson["TRANSFEREE_NAME"];
						$arrayDataWc["PERSON"][] = $arrPerson;
					}
				}*/
				$arrayDataWcGrp[] = $arrayDataWc;
			}
		}
		$arrayResult['CREMATION'] = $arrayDataWcGrp;
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