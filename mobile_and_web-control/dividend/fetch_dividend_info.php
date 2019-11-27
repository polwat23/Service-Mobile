<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'DividendInfo')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_DIVIDEND"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_DIVIDEND"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend',$conmysql);
		$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yr.DIV_YEAR AS DIV_YEAR FROM YRDIVMASTER yrm LEFT JOIN yrcfrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no and yr.LOCKPROC_FLAG = '1' 
												GROUP BY yr.DIV_YEAR ORDER BY yr.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch()){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT div_amt,avg_amt FROM yrdivmaster WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch();
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$getMethpay = $conoracle->prepare("SELECT
													CUCF.MONEYTYPE_DESC AS TYPE_DESC,
													CM.BANK_DESC AS BANK,
													YM.EXPENSE_AMT AS RECEIVE_AMT ,						
													YM.EXPENSE_ACCID AS BANK_ACCOUNT,
													NVL(CM.ACCOUNT_FORMAT,'xxx-xx-xxxxx') as ACCOUNT_FORMAT
												FROM 
													YRDIVMETHPAY YM LEFT JOIN CMUCFMONEYTYPE CUCF ON
													YM.MONEYTYPE_CODE = CUCF.MONEYTYPE_CODE
													LEFT JOIN CMUCFBANK CM ON YM.EXPENSE_BANK = CM.BANK_CODE
												WHERE YM.paytype_code = 'ALL' AND 
													YM.MEMBER_NO = :member_no
													AND YM.DIV_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowMethpay = $getMethpay->fetch();
			$arrDividend["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],$rowMethpay["ACCOUNT_FORMAT"]);
			$arrDividend["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
			$arrDividend["BANK"] = $rowMethpay["BANK"];
			$arrDividend["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
			$getPaydiv = $conoracle->prepare("SELECT yucf.methpaytype_desc AS TYPE_DESC,ymp.expense_amt as pay_amt
											FROM yrdivmethpay ymp LEFT JOIN yrucfmethpay yucf ON ymp.methpaytype_code = yucf.methpaytype_code
											WHERE ymp.MEMBER_NO = :member_no and ymp.div_year = :div_year and ymp.paytype_code <> 'ALL' ");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			while($rowPay = $getPaydiv->fetch()){
				$arrPay = array();
				$arrPay["TYPE_DESC"] = $rowPay["TYPE_DESC"];
				$arrPay["PAY_AMT"] = number_format($rowPay["PAY_AMT"],2);
				$sumPay += $rowPay["PAY_AMT"];
				$arrayPayGroup[] = $arrPay;
			}
			$arrDividend["PAY"] = $arrayPayGroup;
			$arrDividend["SUMPAY"] = number_format($sumPay,2);
			$arrDivmaster[] = $arrDividend;
		}
		if(sizeof($arrDivmaster) > 0 || isset($new_token)){
			$arrayResult["DIVIDEND"] = $arrDivmaster;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>