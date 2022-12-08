<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDataWC = array();
		
		$getCremation1 = $conmssql->prepare("SELECT DISTINCT   TA.WFMEMBER_NO,TA.WFTYPE_DESC,TA.MEMBER_NO,TA.TIMES,CONCAT(TA.PRENAME_DESC,TA.MEMB_NAME,'  ',TA.MEMB_SURNAME) AS MEMB_NAME,TA.CARD_PERSON 
FROM TEMPSSTTA TA
WHERE TA.COOP_ID='059001' AND TA.WC_ID='03' AND TA.WFTYPE_CODE ='06 'AND TA.MEMBER_NO= :member_no");
		$getCremation1->execute([':member_no' => $member_no]);
		$no = 1;
		while($rowCremation = $getCremation1->fetch(PDO::FETCH_ASSOC)){
		
			$getDepostit = $conmssql->prepare("SELECT TA.WFMEMBER_NO,
												DEPOSITA.WFMEMBER_NO,DEPOSITA.SEQ_NO,DEPOSITA.DEPOSIT_NAME,DEPOSITA.RELATIONSHIP
											FROM TEMPSSTTA TA
											LEFT JOIN (SELECT * FROM TEMPDEPOSITA SITA WHERE  SITA.WC_ID='03' AND SITA.WFTYPE_CODE ='07 ') DEPOSITA ON TA.WFMEMBER_NO = DEPOSITA.WFMEMBER_NO
											WHERE TA.COOP_ID='059001' AND TA.WC_ID='03' AND TA.WFTYPE_CODE ='06 'AND TA.MEMBER_NO= :member_no AND DEPOSITA.wfmember_no = :wfmember_no");
			$getDepostit->execute([
				':member_no' => $member_no,
				':wfmember_no' => $rowCremation["WFMEMBER_NO"]
			]);
			$arrCremation = array();
			while($rowDeposit = $getDepostit->fetch(PDO::FETCH_ASSOC)){
				$arrPerson["NAME"] = $rowDeposit["DEPOSIT_NAME"];
				$arrCremation["PERSON"][] = $arrPerson;
			}
			$arrayOther[0]["LABEL"] = 'ลำดับ';
			$arrayOther[0]["VALUE"] = $no;
			$arrayOther[1]["LABEL"] = "เลขฌาปนกิจ";
			$arrayOther[1]["VALUE"] = $rowCremation["WFMEMBER_NO"];
			$arrayOther[2]["LABEL"] = "ประเภทสมัคร";
			$arrayOther[2]["VALUE"] = $rowCremation["WFTYPE_DESC"];
			$arrayOther[3]["LABEL"] = "รอบ";
			$arrayOther[3]["VALUE"] = $rowCremation["TIMES"];
			$arrayOther[4]["LABEL"] = "ชื่อ-สกุล";
			$arrayOther[4]["VALUE"] = $rowCremation["MEMB_NAME"];
			$arrayOther[5]["LABEL"] = "เลขบัตร";
			$arrayOther[5]["VALUE"] = $rowCremation["CARD_PERSON"];
			$arrCremation["OTHER_INFO"] = $arrayOther;

			

			$arrCremation["CREMATION_TYPE"] = "สสธท.ล้านที่ 1 (ข้อมูลทายาท)";
			$arrCremation["CARD_COLOR"] = "#FF9966";
			$arrDataWC[] = $arrCremation;
			$no++;
 		}
		
		//select สสธท.ล้านที่ 2
		$getCremation_2 = $conmssql->prepare("SELECT TB.DEPTACCOUNT_NO,TB.WFTYPE_DESC,TB.MEMBER_NO,TIMES,TB.MEMB_NAME,TB.CARD_PERSON 
											  FROM TEMPSSTTB TB 
		                                       WHERE  TB.WC_ID='03' 
												AND TB.WFTYPE_CODE ='08 ' 
												AND TB.MEMBER_NO = :member_no");
		
		$getCremation_2->execute([':member_no' => $member_no]);
		$no = 1;
		while($rowCremation2 = $getCremation_2->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrayOther[0]["LABEL"] = 'ลำดับ';
			$arrayOther[0]["VALUE"] = $no;
			$arrayOther[1]["LABEL"] = "เลขกองทุน";
			$arrayOther[1]["VALUE"] = $rowCremation2["DEPTACCOUNT_NO"];
			$arrayOther[2]["LABEL"] = "ประเภทสมัคร";
			$arrayOther[2]["VALUE"] = $rowCremation2["WFTYPE_DESC"];
			$arrayOther[3]["LABEL"] = "รอบ";
			$arrayOther[3]["VALUE"] = $rowCremation2["TIMES"];
			$arrayOther[4]["LABEL"] = "ชื่อ-สกุล";
			$arrayOther[4]["VALUE"] = $rowCremation2["MEMB_NAME"];
			$arrayOther[5]["LABEL"] = "เลขบัตร";
			$arrayOther[5]["VALUE"] = $rowCremation2["CARD_PERSON"];
			

			$arrCremation["OTHER_INFO"] = $arrayOther;
			$arrCremation["CREMATION_TYPE"] = "กสธท.ล้านที่ 2";
			$arrCremation["CARD_COLOR"] = "#FF9966";
			$arrDataWC[] = $arrCremation;
			$no++;
 		}
		
		//select สสธท.ล้านที่ 3
		$getCremation_3 = $conmssql->prepare("SELECT  TC.DEPTACCOUNT_NO,TC.WFTYPE_DESC,TC.MEMBER_NO,TIMES,TC.MEMB_NAME,TC.CARD_PERSON  FROM TEMPSSTTC TC WHERE TC.WC_ID='03' AND TC.WFTYPE_CODE ='09 '  AND TC.MEMBER_NO= :member_no;");
		$getCremation_3->execute([':member_no' => $member_no]);
		$no = 1;
		while($rowCremation3 = $getCremation_3->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrayOther[0]["LABEL"] = 'ลำดับ';
			$arrayOther[0]["VALUE"] = $no;
			$arrayOther[1]["LABEL"] = "เลขกองทุน";
			$arrayOther[1]["VALUE"] = $rowCremation3["DEPTACCOUNT_NO"];
			$arrayOther[2]["LABEL"] = "ประเภทสมัคร";
			$arrayOther[2]["VALUE"] = $rowCremation3["WFTYPE_DESC"];
			$arrayOther[3]["LABEL"] = "รอบ";
			$arrayOther[3]["VALUE"] = $rowCremation3["TIMES"];
			$arrayOther[4]["LABEL"] = "ชื่อ-สกุล";
			$arrayOther[4]["VALUE"] = $rowCremation3["MEMB_NAME"];
			$arrayOther[5]["LABEL"] = "เลขบัตร";
			$arrayOther[5]["VALUE"] = $rowCremation3["CARD_PERSON"];
			

			$arrCremation["OTHER_INFO"] = $arrayOther;
			$arrCremation["CREMATION_TYPE"] = "กสธท.ล้านที่ 3";
			$arrCremation["CARD_COLOR"] = "#FF9966";
			$arrDataWC[] = $arrCremation;
			$no++;
 		}
		
		
		//select สส.ชสอ.
		$getCremations = $conmssql->prepare("SELECT TD.WFMEMBER_NO,TD.WFTYPE_DESC,TD.MEMBER_NO,TD.TIMES,CONCAT(TD.PRENAME_DESC,TD.MEMB_NAME,'  ',TD.MEMB_SURNAME) AS MEMB_NAME,TD.CARD_PERSON 
			FROM TEMPSSTTD TD
			WHERE TD.COOP_ID='059001' AND TD.WC_ID='03' AND TD.WFTYPE_CODE ='10 'AND TD.MEMBER_NO= :member_no");
		$getCremations->execute([':member_no' => $member_no]);
		$no = 1;
		while($rowCremations = $getCremations->fetch(PDO::FETCH_ASSOC)){
		
		$getDepostit = $conmssql->prepare("SELECT TD.WFMEMBER_NO,TD.WFTYPE_DESC
,DEPOSITB.WFMEMBER_NO,DEPOSITB.SEQ_NO,DEPOSITB.DEPOSIT_NAME,DEPOSITB.RELATIONSHIP
FROM TEMPSSTTD TD
LEFT JOIN (SELECT * FROM TEMPDEPOSITB SITB WHERE  SITB.WC_ID='03' AND SITB.WFTYPE_CODE ='11 ') DEPOSITB ON TD.WFMEMBER_NO = DEPOSITB.WFMEMBER_NO
WHERE TD.COOP_ID='059001' AND TD.WC_ID='03' AND TD.WFTYPE_CODE ='10 'AND TD.MEMBER_NO= :member_no AND TD.WFMEMBER_NO  =  :wfmember_no");
		$arrCremation = array();
		$getDepostit->execute([
			':member_no' => $member_no,
			':wfmember_no' => $rowCremations["WFMEMBER_NO"]
		]);
		$arrCremation = array();
		while($rowDeposit = $getDepostit->fetch(PDO::FETCH_ASSOC)){
			$arrPerson["NAME"] = $rowDeposit["DEPOSIT_NAME"];
			$arrCremation["PERSON"][] = $arrPerson;
		}
		
		
		$arrayOther[0]["LABEL"] = 'ลำดับ';
		$arrayOther[0]["VALUE"] = $no;
		$arrayOther[1]["LABEL"] = "เลขฌาปนกิจ";
		$arrayOther[1]["VALUE"] = $rowCremations["WFMEMBER_NO"];
		$arrayOther[2]["LABEL"] = "ประเภทสมัคร";
		$arrayOther[2]["VALUE"] = $rowCremations["WFTYPE_DESC"];
		$arrayOther[3]["LABEL"] = "รอบ";
		$arrayOther[3]["VALUE"] = $rowCremations["TIMES"];
		$arrayOther[4]["LABEL"] = "ชื่อ-สกุล";
		$arrayOther[4]["VALUE"] = $rowCremations["MEMB_NAME"];
		$arrayOther[5]["LABEL"] = "เลขบัตร";
		$arrayOther[5]["VALUE"] = $rowCremations["CARD_PERSON"];
	
		

		$arrCremation["OTHER_INFO"] = $arrayOther;
		$arrCremation["CREMATION_TYPE"] = "สส.ชสอ. (ข้อมูลทายาท)";
		$arrCremation["CARD_COLOR"] = "#FF9966";
		$arrDataWC[] = $arrCremation;
		$no++;
	}

		
		$arrayResult['CREMATION'] = $arrDataWC;
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