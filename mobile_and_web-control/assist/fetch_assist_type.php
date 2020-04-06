<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpYear = array();
		$yearAss = 0;
		$fetchAssGrpYear = $conoracle->prepare("SELECT capital_year as ASSIST_YEAR,sum(SUMPAY) as ASS_RECEIVED FROM asnreqmaster 
												WHERE member_no = :member_no GROUP BY capital_year ORDER BY capital_year DESC");
		$fetchAssGrpYear->execute([':member_no' => $member_no]);
		while($rowAssYear = $fetchAssGrpYear->fetch(PDO::FETCH_ASSOC)){
			$arrayYear = array();
			$arrayYear["ASSIST_YEAR"] = $rowAssYear["ASSIST_YEAR"];
			$arrayYear["ASS_RECEIVED"] = number_format($rowAssYear["ASS_RECEIVED"],2);
			if($yearAss < $rowAssYear["ASSIST_YEAR"]){
				$yearAss = $rowAssYear["ASSIST_YEAR"];
			}
			$arrayGrpYear[] = $arrayYear;
		}
		if(isset($dataComing["ass_year"]) && $dataComing["ass_year"] != ""){
			$yearAss = $dataComing["ass_year"];
		}
		$fetchAssType = $conoracle->prepare("SELECT ast.ASSISTTYPE_DESC,ast.ASSISTTYPE_CODE,asm.ASSIST_DOCNO as ASSCONTRACT_NO,asm.SUMPAY,asm.PAY_DATE
												FROM asnreqmaster asm LEFT JOIN 
												asnucfassisttype ast ON asm.ASSISTTYPE_CODE = ast.ASSISTTYPE_CODE and asm.coop_id = ast.coop_id WHERE asm.member_no = :member_no 
												and asm.pay_status = 1 and asm.capital_year = :year");
		$fetchAssType->execute([
			':member_no' => $member_no,
			':year' => $yearAss
		]);
		$arrGroupAss = array();
		while($rowAssType = $fetchAssType->fetch(PDO::FETCH_ASSOC)){
			$arrAss = array();
			$arrAss["ASSIST_RECVAMT"] = number_format($rowAssType["SUMPAY"],2);
			$arrAss["PAY_DATE"] = $lib->convertdate($rowAssType["PAY_DATE"],'d m Y');
			$arrAss["ASSISTTYPE_CODE"] = $rowAssType["ASSISTTYPE_CODE"];
			$arrAss["ASSISTTYPE_DESC"] = $rowAssType["ASSISTTYPE_DESC"];
			$arrAss["ASSCONTRACT_NO"] = $rowAssType["ASSCONTRACT_NO"];
			$arrGroupAss[] = $arrAss;
		}
		$arrayResult["IS_STM"] = FALSE;
		$arrayResult["YEAR"] = $arrayGrpYear;
		$arrayResult["ASSIST"] = $arrGroupAss;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>