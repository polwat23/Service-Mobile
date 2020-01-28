<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		if($payload["member_no"] == 'dev@mode' || $payload["member_no"] == "etnmode1" || $payload["member_no"] == "etnmode2" || $payload["member_no"] == "etnmode3"){
			$member_no = $config["MEMBER_NO_DEV_DEPOSIT"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_DEPOSIT"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrAllAccount = array();
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no");
		$getSumAllAccount->execute([':member_no' => $member_no]);
		$rowSumbalance = $getSumAllAccount->fetch();
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$getAccount = $conoracle->prepare("SELECT dp.depttype_code,dp.membcat_code,dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = dp.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code and dp.membcat_code = dt.membcat_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1 ORDER BY dp.deptaccount_no ASC");
		$getAccount->execute([':member_no' => $member_no]);
		while($rowAccount = $getAccount->fetch()){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrayHeaderAcc = array();
			if($dataComing["channel"] == 'mobile_app'){
				$fetchAlias = $conmysql->prepare("SELECT alias_name,path_alias_img FROM gcdeptalias WHERE deptaccount_no = :account_no");
				$fetchAlias->execute([
					':account_no' => $rowAccount["DEPTACCOUNT_NO"]
				]);
				$rowAlias = $fetchAlias->fetch();
				$arrAccount["ALIAS_NAME"] = $rowAlias["alias_name"];
				if(isset($rowAlias["path_alias_img"])){
					$explodePathAliasImg = explode('.',$rowAlias["path_alias_img"]);
					$arrAccount["ALIAS_PATH_IMG"] = $config["URL_SERVICE"].$explodePathAliasImg[0].'.webp';
				}else{
					$arrAccount["ALIAS_PATH_IMG"] = null;
				}
			}else{
				if(file_exists(__DIR__.'/../../resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'_'.$rowAccount["MEMBCAT_CODE"].'.jpg')){
					$arrAccount["COVER_IMG"] = $config["URL_SERVICE"].'resource/cover-dept/'.$rowAccount["DEPTTYPE_CODE"].'_'.$rowAccount["MEMBCAT_CODE"].'.jpg';
				}else{
					$arrAccount["COVER_IMG"] = null;
				}
			}
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccount["DEPTACCOUNT_NAME"]);
			$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
			$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'y-n-d');
			$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccount["LAST_OPERATE_DATE"],'D m Y');
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			$arrGroupAccount['DEPT_TYPE_CODE'] = $rowAccount["DEPTTYPE_CODE"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
		$arrayResult['RESULT'] = TRUE;
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