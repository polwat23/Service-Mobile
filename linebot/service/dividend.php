<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrDivmaster = array();
	$limit_year = $func->getConstant('limit_dividend');
	$getYeardividend = $conoracle->prepare("SELECT * FROM (SELECT yr.DIV_YEAR AS DIV_YEAR,yr.DIVPERCENT_RATE,yr.AVGPERCENT_RATE FROM YRDIVMASTER yrm LEFT JOIN yrcfrate yr 
												ON yrm.DIV_YEAR = yr.DIV_YEAR WHERE yrm.MEMBER_NO = :member_no 
												GROUP BY yr.DIV_YEAR,yr.DIVPERCENT_RATE,yr.AVGPERCENT_RATE ORDER BY yr.DIV_YEAR DESC) where rownum <= :limit_year");
		$getYeardividend->execute([
			':member_no' => $member_no,
			':limit_year' => $limit_year
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conoracle->prepare("SELECT div_amt,avg_amt FROM yrdivmaster WHERE member_no = :member_no and div_year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_RATE"] = ($rowYear["DIVPERCENT_RATE"] * 100).'%';
			$arrDividend["AVG_RATE"] = ($rowYear["AVGPERCENT_RATE"] * 100).'%';
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$getMethpay = $conoracle->prepare("SELECT
													CUCF.MONEYTYPE_DESC AS TYPE_DESC,
													CM.BANK_DESC AS BANK,
													YM.EXPENSE_AMT AS RECEIVE_AMT ,						
													YM.EXPENSE_ACCID AS BANK_ACCOUNT,
													YM.METHPAYTYPE_CODE
												FROM 
													YRDIVMETHPAY YM LEFT JOIN CMUCFMONEYTYPE CUCF ON
													YM.MONEYTYPE_CODE = CUCF.MONEYTYPE_CODE
													LEFT JOIN CMUCFBANK CM ON YM.EXPENSE_BANK = CM.BANK_CODE
												WHERE
													YM.MEMBER_NO = :member_no
													AND YM.METHPAYTYPE_CODE IN ('CBT','CHS','DEP') 
													AND YM.DIV_YEAR = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				if($rowMethpay["METHPAYTYPE_CODE"] == "CBT" || $rowMethpay["METHPAYTYPE_CODE"] == "DEP"){
					if(isset($rowMethpay["BANK"])){
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],'xxx-xxxxxx-x');
					}else{
						$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["BANK_ACCOUNT"],$func->getConstant('dep_format'));
					}
				}
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["TYPE_DESC"];
				$arrayRecv["BANK"] = $rowMethpay["BANK"];
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["RECEIVE_AMT"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}

			$getPaydiv = $conoracle->prepare("SELECT UCF.METHPAYTYPE_DESC AS TYPE_DESC, DIVD.MONEYTYPE_CODE AS MONEYTYPE_CODE , 
										DIVD.EXPENSE_AMT AS PAY_AMT 
										FROM YRDIVMETHPAY DIVD 
										LEFT JOIN YRUCFMETHPAY UCF ON DIVD.METHPAYTYPE_CODE = UCF.METHPAYTYPE_CODE 
										WHERE ( DIVD.MEMBER_NO = :member_no) AND UCF.METHPAYTYPE_CODE NOT IN ('CBT','CHS','DEP') 
										AND ( DIVD.DIV_YEAR = :div_year ) ORDER BY DIVD.SEQ_NO");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			
			while($rowPay = $getPaydiv->fetch(PDO::FETCH_ASSOC)){
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
	$groupDividend = $arrDivmaster;
	$datas = [];
	$datas["type"] = "flex";
	$datas["altText"] = "ปันผล";
	$datas["contents"]["type"] = "carousel";
	$index = 0;
	if(sizeof($groupDividend)>0){
		foreach($groupDividend as $dividendData){
			$datas["contents"]["contents"][$index]["type"] = "bubble";
			$datas["contents"]["contents"][$index]["direction"] = "ltr";
			$datas["contents"]["contents"][$index]["body"]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["layout"] = "vertical";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["layout"] = "vertical";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["text"] = "ประจำปี ".($dividendData["YEAR"]??'-');
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["size"] = "lg";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][0]["align"] = "center";
			if(isset($dividendData["DIV_RATE"])){
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["layout"] = "baseline";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "อัตราเงินปันผล ";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][0]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = ($dividendData["DIV_RATE"]??'-');
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][1]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][1]["color"] = "#000000";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["contents"][1]["align"] = "end";
			}else{
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][1]["type"] = "filler";
			}
			if(isset($dividendData["AVG_RATE"])){
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["layout"] = "baseline";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][0]["text"] = "อัตราเงินปันผลเฉลี่ยคืน ";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][0]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][1]["text"] = ($dividendData["AVG_RATE"]??'-');
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][1]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][1]["color"] = "#000000";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["contents"][1]["align"] = "end";
			}else{
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][2]["type"] = "filler";
			}
			
			
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["layout"] = "baseline";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][0]["text"] = "ปันผล ";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][1]["text"] = ($dividendData["DIV_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][1]["color"] = "#000000";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][3]["contents"][1]["align"] = "end";
			
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["layout"] = "baseline";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][0]["text"] = "เฉลี่ยคืน ";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][1]["text"] = ($dividendData["AVG_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][1]["color"] = "#000000";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][4]["contents"][1]["align"] = "end";
			
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["layout"] = "baseline";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][0]["text"] = "รวม ";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["text"] = ($dividendData["SUM_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["color"] = "#35B84B";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][5]["contents"][1]["align"] = "end";
			//รายการหัก
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["type"] = "box";
			$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["layout"] = "vertical";
			if(sizeof($dividendData["PAY"])>0){
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][0]["type"] = "separator";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][0]["margin"] = "md";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["layout"] = "baseline";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["margin"] = "sm";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][0]["text"] = "รายการหัก";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][0]["weight"] = "bold";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][0]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][0]["color"] = "#D32F2F";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][1]["text"] = sizeof($dividendData["PAY"]).' รายการ';
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][1]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][1]["color"] = "#AAAAAA";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][1]["contents"][1]["align"] = "end";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["layout"] = "vertical";
				$indexPay = 0;
				foreach($dividendData["PAY"] as $payData){
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["type"] = "box";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["layout"] = "baseline";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["paddingStart"] = "10px";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][0]["text"] = ($payData["TYPE_DESC"]??'-');
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][0]["color"] = "#AAAAAA";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][1]["text"] =  ($payData["PAY_AMT"]??'-'); 
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][2]["contents"][$indexPay]["contents"][1]["align"] = "end";
					$indexPay++;
				}
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["layout"] = "baseline";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["margin"] = "sm";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][0]["text"] = "รวมหัก";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][0]["weight"] = "bold";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][0]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][0]["color"] = "#000000";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["text"] = ($dividendData["SUMPAY"]??'-')." บาท";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["weight"] = "bold";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["color"] = "#000000";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][3]["contents"][1]["align"] = "end";
			}else{
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][6]["contents"][0]["type"] = "filler";
			}	
			//วิธีการรับเงิน
			if(sizeof($dividendData["RECEIVE_ACCOUNT"])>0){
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["layout"] = "vertical";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][0]["type"] = "separator";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][0]["margin"] = "md";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["text"] = "วิธีรับเงิน";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["weight"] = "bold";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["size"] = "xs";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["color"] = "#000000";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][1]["margin"] = "md";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["type"] = "box";
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["layout"] = "vertical";
				$indexReceiveAccount = 0;
				foreach($dividendData["RECEIVE_ACCOUNT"] as $receiveAccount){
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["type"] = "box";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["layout"] = "vertical";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][0]["text"] = ($receiveAccount["RECEIVE_DESC"]??'-');
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][0]["color"] = "#000000";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][0]["offsetStart"] = "10px";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][1]["text"] = ($receiveAccount["BANK"]??'-');
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][1]["color"] = "#1885C3";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][1]["offsetStart"] = "10px";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["type"] = "box";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["layout"] = "horizontal";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][0]["text"] = "เลขบัญชี";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][0]["offsetStart"] = "10px";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][1]["text"] = ($receiveAccount["ACCOUNT_RECEIVE"]??'-');
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][1]["color"] = "#000000";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][2]["contents"][1]["align"] = "end";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["type"] = "box";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["layout"] = "horizontal";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][0]["text"] = "จำนวน";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][0]["offsetStart"] = "10px";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["text"] = ($receiveAccount["RECEIVE_AMT"]??'-').' บาท';
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["color"] = "#000000";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["align"] = "end";
					$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["contents"][2]["contents"][$indexReceiveAccount]["contents"][3]["contents"][1]["weight"] = "bold";
					$indexReceiveAccount++;			
				}
				
			}else{
				$datas["contents"]["contents"][$index]["body"]["contents"][0]["contents"][7]["type"] = "filler";
			}
			$arrPostData["messages"][0] = $datas;
			$arrPostData["replyToken"] = $reply_token; 
			$index++;
		}
	}else{
		$messageResponse = "ไม่พบข้อมูลปันผล";
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}	
	
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>