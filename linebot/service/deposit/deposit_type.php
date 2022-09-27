<?php
if($lineLib->checkBindAccount($user_id)){

	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrayResult = array();
	$arrdepositData = array();
	$getSumdepositData = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1");
	$getSumdepositData->execute([':member_no' => $member_no]);
	$rowSumbalance = $getSumdepositData->fetch(PDO::FETCH_ASSOC);
	$getTotalAccount = $conoracle->prepare("SELECT COUNT(deptaccount_no) as TOTALACCOUNT FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1;");
	$getTotalAccount->execute([':member_no' => $member_no]);
	$rowTotalAccount = $getTotalAccount->fetch(PDO::FETCH_ASSOC);
	$getAccount = $conoracle->prepare("SELECT dp.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,dp.DEPTACCOUNT_NO,dp.DEPTACCOUNT_NAME,dp.prncbal as BALANCE,
										(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE DEPTACCOUNT_NO = dp.DEPTACCOUNT_NO) as LAST_OPERATE_DATE
										FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.DEPTTYPE_CODE = dt.DEPTTYPE_CODE
										WHERE dp.member_no = :member_no and (dp.DEPTTYPE_CODE = :depttype_code or dt.DEPTTYPE_DESC = :depttype_desc) and dp.deptclose_status <> 1 ORDER BY dp.DEPTACCOUNT_NO ASC");
	$getAccount->execute([
		':member_no' => $member_no,
		':depttype_code' => $depttype,
		':depttype_desc' => $depttype
	]);
	while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
		$arrAccount = array();
		$arrGroupAccount = array();
		$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
		$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
		$fetchAlias = $conmysql->prepare("SELECT alias_name,path_alias_img,date_format(update_date,'%Y%m%d%H%i%s') as update_date FROM gcdeptalias WHERE DEPTACCOUNT_NO = :account_no");
			$fetchAlias->execute([
				':account_no' => $rowAccount["DEPTACCOUNT_NO"]
			]);
		$rowAlias = $fetchAlias->fetch(PDO::FETCH_ASSOC);
		$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"] ?? 'บัญชี';
		if(isset($rowAlias["path_alias_img"])){
			$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
			$arrAccount["ALIAS_PATH_IMG_WEBP"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp?v='.$rowAlias["update_date"];
			$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$rowAlias["path_alias_img"].'?v='.$rowAlias["update_date"];
		}else{
			$arrAccount["ALIAS_PATH_IMG"] = null;
			$arrAccount["ALIAS_PATH_IMG_WEBP"]  = null;
		}
		$arrAccount["DEPTACCOUNT_NO"] = $account_no;
		$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
		$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccount["DEPTACCOUNT_NAME"]));
		$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
		$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
		$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
		$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
		$arrGroupAccount['DEPT_TYPE_CODE'] = $rowAccount["DEPTTYPE_CODE"];
		if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrdepositData,'TYPE_ACCOUNT')) === False){
			($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
			$arrdepositData[] = $arrGroupAccount;
		}else{
			($arrdepositData[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrdepositData,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
		}
		$groupDeposit = $arrdepositData;
	}
	$depositTypeGroup = $groupDeposit[0]["ACCOUNT"]??[];
	$datas = [];
	$datas["type"] = "flex";
	$datas["altText"] = ($groupDeposit[0]["TYPE_ACCOUNT"])??'ไม่พบข้อมูล';
	$datas["contents"]["type"] = "carousel";
   
	if(sizeof($depositTypeGroup)>0){
		$indexContents = 0;
		foreach($depositTypeGroup as $rowDeposit){
			$datas["contents"]["contents"][$indexContents]["type"] = "bubble";
			$datas["contents"]["contents"][$indexContents]["body"]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["text"] = ($rowDeposit["ALIAS_NAME"]??'บัญชี ').($indexContents+1);
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["size"] = "md";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["layout"] = "baseline";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = ($rowDeposit["LAST_OPERATE_DATE_FORMAT"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["size"] = "xxs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["gravity"] = "top";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["offsetEnd"] = "3px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "icon";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["url"] = "https://cdn.thaicoop.co/icon/time.png";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["size"] = "xxs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["text"] = ($rowDeposit["DEPTACCOUNT_NO"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["offsetEnd"] = "0px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["text"] = ($rowDeposit["DEPTACCOUNT_NAME"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["color"] = "#000000";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["wrap"] = true;
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["offsetEnd"] = "0px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["text"] = "คงเหลือ";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["text"] = ($rowDeposit["BALANCE"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["size"] = "lg";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["color"] = "#12B8D5FF";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["offsetStart"] = "20px";

			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["margin"] = "lg";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["type"] = "button";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["action"]["type"] = "message";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["action"]["label"] = "ดูรายการเคลื่อนไหว";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["action"]["text"] = "รายการเคลื่อนไหวเงินฝาก:".($rowDeposit["DEPTACCOUNT_NO"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["style"] = "primary";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["color"] = ($themeColor??"#000000");
			$indexContents++;

		}
		$arrPostData["messages"][0] = $datas;
		$arrPostData["replyToken"] = $reply_token; 
	
	}else{
		$messageResponse = "ไม่พบข้อมูล".$depttype;
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}

}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>