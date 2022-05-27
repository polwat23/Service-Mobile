<?php
$anonymous = '';
require_once('../autoload.php');

if(!$anonymous){
	$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	$user_type = $payload["user_type"];
	$permission = array();
	
	$arrayAllMenu = array();
	$arrayMenuSetting = array();
	switch($user_type){
		case '0' : 
			$permission[] = "'0'";
			break;
		case '1' : 
			$permission[] = "'0'";
			$permission[] = "'1'";
			break;
		case '5' : 
			$permission[] = "'0'";
			$permission[] = "'1'";
			$permission[] = "'2'";
			break;
		case '9' : 
			$permission[] = "'0'";
			$permission[] = "'1'";
			$permission[] = "'2'";
			$permission[] = "'3'";
			break;
		default : $permission[] = '0';
			break;
	}
	if(isset($dataComing["id_menu"])){
		if($dataComing["menu_component"] == "DepositInfo"){
			$arrMenuDep = array();
			if(isset($dataComing["home_deposit_account"])) {
				$account_no = $dataComing["home_deposit_account"];
				$fetchMenuDep = $conmssqlcoop->prepare("SELECT dp.deposit_id as DEPTACCOUNT_NO,dp.status as DEPTCLOSE_STATUS, dt.description as DEPTTYPE_DESC, 
													ISNULL(stm.BALANCE,0) as  BALANCE, ISNULL(stm.DEPOSIT,0) as DEPOSIT,
													(SELECT COUNT(deposit_id) FROM codeposit_master WHERE member_id = ? and status = 'A') as C_ACCOUNT
													FROM codeposit_master dp LEFT JOIN codeposit_type dt ON dp.deposit_type = dt.deposit_type
													LEFT JOIN codeposit_transaction stm  ON dp.lastseq = stm.transaction_seq AND dp.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
													WHERE dp.deposit_id = ? ");
				$fetchMenuDep->execute([$member_no, $account_no]);
				$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
				$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
				if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
					$arrMenuDep["ACCOUNT_NO"] = $rowMenuDep["DEPTACCOUNT_NO"];
					$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $rowMenuDep["DEPTACCOUNT_NO"];
					$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
					$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
				}else{
					$arrMenuDep["ACCOUNT_NO"] = "close";
				}
			}else {
				$fetchMenuDep = $conmssqlcoop->prepare("SELECT SUM(stm.BALANCE) as BALANCE,COUNT(dm.deposit_id) as C_ACCOUNT FROM codeposit_master dm LEFT JOIN codeposit_transaction stm  ON dm.lastseq = stm.transaction_seq AND dm.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
												WHERE dm.member_id = ? and status = 'A'");
				$fetchMenuDep->execute([$member_no]);
				$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
				$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
				$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
			}
			$arrMenuDep["LAST_STATEMENT"] = FALSE;
			$arrayResult['MENU_DEPOSIT'] = $arrMenuDep;
		}else if($dataComing["menu_component"] == "LoanInfo"){
			$arrMenuLoan = array();
			if(isset($dataComing["home_loan_account"])) {
				//$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
				$contract_no = $dataComing["home_loan_account"];
				$fetchMenuLoan = $conmssqlcoop->prepare("SELECT (isnull(lm.amount,0) - isnull(lm.principal_actual,0)) as BALANCE, cd.description AS LOAN_TYPE , lm.doc_no AS LOANCONTRACT_NO, 
														lm.status as CONTRACT_STATUS, 
														(SELECT COUNT(doc_no) FROM coloanmember WHERE member_id = ? and status IN ('A','N')) as C_CONTRACT
														FROM coloanmember lm LEFT JOIN cointerestrate_desc cd ON lm.Type = cd.Type	
														WHERE lm.doc_no = ?");
				$fetchMenuLoan->execute([$member_no, $contract_no]);
				$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
				$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
				if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
					$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
					$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
					$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
				}else{
					$arrMenuLoan["CONTRACT_NO"] = 'close';
				}
			}else {
				$fetchMenuLoan = $conmssqlcoop->prepare("SELECT SUM((isnull(amount,0) - isnull(principal_actual,0))) as BALANCE,COUNT(doc_no) as C_CONTRACT FROM coloanmember 
														 WHERE member_id = ? and status  IN ('A','N') ");
				$fetchMenuLoan->execute([$member_no]);
				$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
				$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
				$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
			}
			$arrMenuLoan["LAST_STATEMENT"] = FALSE;
			$arrayResult['MENU_LOAN'] = $arrMenuLoan;
		}
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
		
	}else{
		if(isset($dataComing["menu_parent"])){
			if($user_type == '5' || $user_type == '9'){
				$fetch_menu = $conmssql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = ?
												and (menu_channel = ? OR 1=1)
												ORDER BY menu_order ASC");
			}else if($user_type == '1'){
				$fetch_menu = $conmssql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") and gm.menu_parent = ?
												and gm.menu_status IN('0','1') and (gm2.menu_status IN('0','1') OR gm.menu_parent = '0')
												and (gm.menu_channel = ? OR 1=1) ORDER BY gm.menu_order ASC");
			}else{
				$fetch_menu = $conmssql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") and gm.menu_parent = ? 
												and gm.menu_status = '1' and (gm2.menu_status = '1' OR gm.menu_parent = '0')
												and (gm.menu_channel = ? OR gm.menu_channel = 'both') ORDER BY gm.menu_order ASC");
			}
			$fetch_menu->execute([ 
								$dataComing["menu_parent"], $dataComing["channel"]
			]);
			while($rowMenu = $fetch_menu->fetch(PDO::FETCH_ASSOC)){
				if($dataComing["channel"] == 'mobile_app'){
					if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
						$arrMenu = array();
						$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
						$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
						$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
						$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
						$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
						$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
						$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
						$arrayAllMenu[] = $arrMenu;
					}
				}else{
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}
			$arrayResult['MENU'] = $arrayAllMenu;
			if($dataComing["menu_parent"] == '0'){
				$arrayResult['REFRESH_MENU'] = "MENU_HOME";
			}else if($dataComing["menu_parent"] == '24'){
				$arrayResult['REFRESH_MENU'] = "MENU_SETTING";
			}else if($dataComing["menu_parent"] == '18'){
				if($dataComing["channel"] == 'mobile_app'){
					$arrayResult['REFRESH_MENU'] = "MENU_HOME";
				}else{
					$arrayResult['REFRESH_MENU'] = "MENU_TRANSACTION";
				}
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrMenuDep = array();
			$arrMenuLoan = array();
			$arrayGroupMenu = array();
			$arrayMenuTransaction = array();
			if($user_type == '5' || $user_type == '9'){
				$fetch_menu = $conmssql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") 
												and (menu_channel = ? OR 1=1)
												ORDER BY menu_order ASC");
			}else if($user_type == '1'){
				$fetch_menu = $conmssql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).")
												and gm.menu_status IN('0','1') and (gm2.menu_status IN('0','1') OR gm.menu_parent = '0')
												and (gm.menu_channel = ? OR 1=1) ORDER BY gm.menu_order ASC");
			}else{
				$fetch_menu = $conmssql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") 
												and gm.menu_status = '1' and (gm2.menu_status = '1' OR gm.menu_parent = '0')
												and (gm.menu_channel = ? OR gm.menu_channel = 'both') ORDER BY gm.menu_order ASC");
			}
			$fetch_menu->execute([$dataComing["channel"]]);
			while($rowMenu = $fetch_menu->fetch(PDO::FETCH_ASSOC)){
				if($dataComing["channel"] == 'mobile_app'){
					if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
						$arrMenu = array();
						$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
						$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
						$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
						$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
						$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
						$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
						$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
						if($rowMenu["menu_parent"] == '0'){
							$arrayGroupMenu["ID_PARENT"] = $rowMenu["menu_parent"];
							$arrayGroupMenu["MENU"][] = $arrMenu;
						}else if($rowMenu["menu_parent"] == '24'){
							$arrayMenuSetting[] = $arrMenu;
						}else if($rowMenu["menu_parent"] == '18'){
							$arrayMenuTransaction["ID_PARENT"] = $rowMenu["menu_parent"];
							$getMenuParentStatus = $conmssql->prepare("SELECT menu_status FROM gcmenu WHERE id_menu = 18");
							$getMenuParentStatus->execute();
							$rowStatus = $getMenuParentStatus->fetch(PDO::FETCH_ASSOC);
							$arrayMenuTransaction["MENU_STATUS"] = $rowStatus["menu_status"];
							$arrayMenuTransaction["MENU"][] = $arrMenu;
						}
						if($rowMenu["menu_component"] == "DepositInfo"){
							$arrMenuDep = array();
							if(isset($dataComing["home_deposit_account"])) {
								$account_no = $dataComing["home_deposit_account"];
								$fetchMenuDep = $conmssqlcoop->prepare("SELECT dp.deposit_id as DEPTACCOUNT_NO,dp.status as DEPTCLOSE_STATUS, dt.description as DEPTTYPE_DESC, 
															ISNULL(stm.balance,0) as  BALANCE, ISNULL(stm.DEPOSIT,0) as DEPOSIT,
															(SELECT COUNT(deposit_id) FROM codeposit_master WHERE member_id = ? and status = 'A') as C_ACCOUNT
															FROM codeposit_master dp LEFT JOIN codeposit_type dt ON dp.deposit_type = dt.deposit_type
															LEFT JOIN codeposit_transaction stm  ON dp.lastseq = stm.transaction_seq AND dp.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
															WHERE dp.deposit_id = ? ");
								$fetchMenuDep->execute([$member_no , $account_no]);
								$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
								$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
								if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
									$arrMenuDep["ACCOUNT_NO"] = $rowMenuDep["DEPTACCOUNT_NO"];
									$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $rowMenuDep["DEPTACCOUNT_NO"];
									$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
									$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
								}else{
									$arrMenuDep["ACCOUNT_NO"] = "close";
								}
							}else {
								$fetchMenuDep = $conmssqlcoop->prepare("SELECT SUM(stm.balance) as BALANCE,COUNT(dm.deposit_id) as C_ACCOUNT FROM codeposit_master dm LEFT JOIN codeposit_transaction stm  ON dm.lastseq = stm.transaction_seq AND dm.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
																	WHERE dm.member_id = ? and status = 'A'");
								$fetchMenuDep->execute([$member_no]);
								$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
								$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
								$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
							}
							$arrMenuDep["LAST_STATEMENT"] = FALSE;
						}else if($rowMenu["menu_component"] == "LoanInfo"){
							$arrMenuLoan = array();
							if(isset($dataComing["home_loan_account"])) {
								$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
								$fetchMenuLoan = $conmssqlcoop->prepare("SELECT (isnull(lm.amount,0) - isnull(lm.principal_actual,0)) as BALANCE, cd.description AS LOAN_TYPE , lm.doc_no AS LOANCONTRACT_NO, 
																		lm.status as CONTRACT_STATUS, 
																		(SELECT COUNT(doc_no) FROM coloanmember WHERE member_id = ? and status = 'A') as C_CONTRACT
																		FROM coloanmember lm LEFT JOIN cointerestrate_desc cd ON lm.Type = cd.Type	
																		WHERE lm.doc_no = ?");
								$fetchMenuLoan->execute([ $member_no, $contract_no]);
								$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
								$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
								if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
									$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
									$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
									$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
								}else{
									$arrMenuLoan["CONTRACT_NO"] = 'close';
								}
							}else {
								$fetchMenuLoan = $conmssqlcoop->prepare("SELECT SUM((isnull(amount,0) - isnull(principal_actual,0))) as BALANCE,COUNT(doc_no) as C_CONTRACT FROM coloanmember 
																		 WHERE member_id = ? and status  = 'A'");
								$fetchMenuLoan->execute([ $member_no]);
								$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
								$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
								$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
							}
							$arrMenuLoan["LAST_STATEMENT"] = FALSE;
						}			
					}
				}else{
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					if($rowMenu["menu_parent"] == '0'){
						$arrayAllMenu[] = $arrMenu;
					}else if($rowMenu["menu_parent"] == '24'){
						$arrayMenuSetting[] = $arrMenu;
					}else if($rowMenu["menu_parent"] == '18'){
						$arrayMenuTransaction[] = $arrMenu;
					}
					if($rowMenu["menu_component"] == "DepositInfo"){
						$arrMenuDep = array();
						if(isset($dataComing["home_deposit_account"])) {
							$account_no = $dataComing["home_deposit_account"];
							$fetchMenuDep = $conmssqlcoop->prepare("SELECT dp.deposit_id as DEPTACCOUNT_NO,dp.status as DEPTCLOSE_STATUS, dt.description as DEPTTYPE_DESC, 
																ISNULL(stm.balance,0) as  BALANCE, ISNULL(stm.DEPOSIT,0) as DEPOSIT,
																(SELECT COUNT(deposit_id) FROM codeposit_master WHERE member_id = ? and status = 'A') as C_ACCOUNT
																FROM codeposit_master dp LEFT JOIN codeposit_type dt ON dp.deposit_type = dt.deposit_type
																LEFT JOIN codeposit_transaction stm  ON dp.lastseq = stm.transaction_seq AND dp.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
																WHERE dp.deposit_id = ? ");
							$fetchMenuDep->execute([$member_no, $account_no]);
							$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
							$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
							if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
								$arrMenuDep["ACCOUNT_NO"] = $rowMenuDep["DEPTACCOUNT_NO"];
								$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $rowMenuDep["DEPTACCOUNT_NO"];
								$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
								$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
							}else{
								$arrMenuDep["ACCOUNT_NO"] = "close";
							}
						}else {
							$fetchMenuDep = $conmssqlcoop->prepare("SELECT SUM(stm.balance) as BALANCE,COUNT(dm.deposit_id) as C_ACCOUNT FROM codeposit_master dm LEFT JOIN codeposit_transaction stm  ON dm.lastseq = stm.transaction_seq AND dm.deposit_id = stm.deposit_id  AND  stm.transaction_subseq = 0
																WHERE dm.member_id = ? and status = 'A'");
							$fetchMenuDep->execute([ $member_no]);
							$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
							$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
							$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
						}
						$arrMenuDep["LAST_STATEMENT"] = TRUE;
					}else if($rowMenu["menu_component"] == "LoanInfo"){
						$arrMenuLoan = array();
						if(isset($dataComing["home_loan_account"])) {
							$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
							$fetchMenuLoan = $conmssqlcoop->prepare("SELECT (isnull(lm.amount,0) - isnull(lm.principal_actual,0)) as BALANCE, cd.description AS LOAN_TYPE , lm.doc_no AS LOANCONTRACT_NO, 
																	lm.status as CONTRACT_STATUS, 
																	(SELECT COUNT(doc_no) FROM coloanmember WHERE member_id = ? and status = 'A') as C_CONTRACT
																	FROM coloanmember lm LEFT JOIN cointerestrate_desc cd ON lm.Type = cd.Type	
																	WHERE lm.doc_no = ?");
							$fetchMenuLoan->execute([$member_no, $contract_no]);
							$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
							$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
							if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
								$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
								$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
								$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
							}else{
								$arrMenuLoan["CONTRACT_NO"] = 'close';
							}
						}else {
							$fetchMenuLoan = $conmssqlcoop->prepare("SELECT SUM((isnull(amount,0) - isnull(principal_actual,0))) as BALANCE,COUNT(doc_no) as C_CONTRACT FROM coloanmember 
																	 WHERE member_id = ? and status  = 'A'");
							$fetchMenuLoan->execute([$member_no]);
							$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
							$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
							$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
						}
						$arrMenuLoan["LAST_STATEMENT"] = TRUE;
					}
				}
			}
			if($dataComing["channel"] == 'mobile_app'){
				$arrayGroupMenu["TEXT_HEADER"] = "ทั่วไป";
				$arrayMenuTransaction["TEXT_HEADER"] = "ธุรกรรม";
				$arrayMenuTransaction["ID_PARENT"] = "18";
				$arrayGroupAllMenu[] = $arrayMenuTransaction;
				$arrayGroupAllMenu[] = $arrayGroupMenu;
				$arrayAllMenu = $arrayGroupAllMenu;
			}
			$arrFavMenuGroup = array();
			$fetchMenuFav = $conmssql->prepare("SELECT fav_refno,name_fav,destination,flag_trans FROM gcfavoritelist WHERE member_no = ? ");
			$fetchMenuFav->execute([$payload["member_no"]]);
			while($rowMenuFav = $fetchMenuFav->fetch(PDO::FETCH_ASSOC)){
				$arrFavMenu = array();
				$arrFavMenu["NAME_FAV"] = $rowMenuFav["name_fav"];
				$arrFavMenu["FAV_REFNO"] = $rowMenuFav["fav_refno"];
				$arrFavMenu["FLAG_TRANS"] = $rowMenuFav["flag_trans"];
				$arrFavMenu["DESTINATION"] = $rowMenuFav["destination"];
				$arrFavMenuGroup[] = $arrFavMenu;
			}
			if(sizeof($arrayAllMenu) > 0 || sizeof($arrayMenuSetting) > 0){
				if($dataComing["channel"] == 'mobile_app'){
					$arrayResult['MENU_HOME'] = $arrayAllMenu;
					$arrayResult['MENU_SETTING'] = $arrayMenuSetting;
					$arrayResult['MENU_FAVORITE'] = $arrFavMenuGroup;
					$arrayResult['MENU_DEPOSIT'] = $arrMenuDep;
					$arrayResult['MENU_LOAN'] = $arrMenuLoan;
				}else{
					$arrayResult['MENU_HOME'] = $arrayAllMenu;
					$arrayResult['MENU_SETTING'] = $arrayMenuSetting;
					$arrayResult['MENU_TRANSACTION'] = $arrayMenuTransaction;
					$arrayResult['MENU_FAVORITE'] = $arrFavMenuGroup;
					$arrayResult['MENU_DEPOSIT'] = $arrMenuDep ?? [];
					$arrayResult['MENU_LOAN'] = $arrMenuLoan ?? [];
				}
				$fetchLimitTrans = $conmssql->prepare("SELECT limit_amount_transaction FROM gcmemberaccount WHERE member_no = ? ");
				$fetchLimitTrans->execute([$member_no]);
				$rowLimitTrans = $fetchLimitTrans->fetch(PDO::FETCH_ASSOC);
				$arrayResult['LIMIT_AMOUNT_TRANSACTION'] = $rowLimitTrans["limit_amount_transaction"];
				$arrayResult['LIMIT_AMOUNT_TRANSACTION_COOP'] = $func->getConstant("limit_withdraw");
				/*$getRule = $conmssql->prepare("SELECT rule_name,rule_url FROM gcrulecooperative WHERE is_use = '1'");
				$getRule->execute();
				$arrGrpRule = array();
				while($rowRule = $getRule->fetch(PDO::FETCH_ASSOC)){
					$arrRule = array();
					$arrRule["RULES_URL"] = $rowRule["rule_url"];
					$arrRule["RULES_DESC"] = $rowRule["rule_name"];
					$arrGrpRule[] = $arrRule;
				}*/
				$arrayResult["APP_CONFIG"]["REGISTER_REQ_PHONE"] = TRUE;
				$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_SMS"] = TRUE;
				$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_EMAIL"] = TRUE;
				$arrayResult["APP_CONFIG"]["COOP_TEL"] = "tel:028539000,7215,7238,7226";
				//$arrayResult["RULES"] = $arrGrpRule;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				http_response_code(204);
				
			}
		}
	}
}else{
	if($lib->checkCompleteArgument(['api_token'],$dataComing)){
		$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
		if(!$arrPayload["VALIDATE"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0001",
				":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$arrayResult['RESPONSE_CODE'] = "WS0001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(401);
			require_once('../../include/exit_footer.php');
			
		}
		$arrayAllMenu = array();
		$fetch_menu = $conmssql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_parent IN ('-1','-2') and (menu_channel = ? OR menu_channel = 'both')");
		$fetch_menu->execute([ $arrPayload["PAYLOAD"]["channel"]]);
		while($rowMenu = $fetch_menu->fetch(PDO::FETCH_ASSOC)){
			if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
				if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"])){
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}else{
				$arrMenu = array();
				$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_NAME_EN"] = $rowMenu["menu_name_en"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
				$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
				$arrayAllMenu[] = $arrMenu;
			}
		}
		if(isset($arrayAllMenu)){
			$arrayResult["APP_CONFIG"]["REGISTER_REQ_PHONE"] = TRUE;
			$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_SMS"] = TRUE;
			$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_EMAIL"] = TRUE;
			$arrayResult["APP_CONFIG"]["COOP_TEL"] = "tel:028539000,7215,7238,7226";
			$arrayResult['MENU'] = $arrayAllMenu;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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
}
?>