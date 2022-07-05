<?php
$anonymous = '';
require_once('../autoload.php');

if(!$anonymous){
	$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	$user_type = $payload["user_type"];
	$permission = array();
	$arrayResult = array();
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
				$account_no = preg_replace('/-/','',$dataComing["home_deposit_account"]);
				$fetchMenuDep = $conoracle->prepare("SELECT dp.prncbal as BALANCE, dp.deptaccount_no,dp.deptclose_status, dt.depttype_desc, (SELECT COUNT(deptaccount_no) 
														FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status = 0) as C_ACCOUNT
														FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
														WHERE deptaccount_no = :account_no");
				$fetchMenuDep->execute([
					':member_no' => $member_no,
					':account_no' => $account_no
				]);
				$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
				$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
				if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
					$arrMenuDep["ACCOUNT_NO"] = $lib->formataccount($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
					$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
					$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
					$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
				}else{
					$arrMenuDep["ACCOUNT_NO"] = "close";
				}
			}else {
				$fetchMenuDep = $conoracle->prepare("SELECT SUM(prncbal) as BALANCE,COUNT(deptaccount_no) as C_ACCOUNT FROM dpdeptmaster 
												WHERE member_no = :member_no and deptclose_status = 0");
				$fetchMenuDep->execute([
					':member_no' => $member_no
				]);
				$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
				$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
				$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
			}
			$arrMenuDep["LAST_STATEMENT"] = TRUE;
			$arrayResult['MENU_DEPOSIT'] = $arrMenuDep;
		}else if($dataComing["menu_component"] == "LoanInfo"){
			$arrMenuLoan = array();
			if(isset($dataComing["home_loan_account"])) {
				$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
				$contract_no = preg_replace('/./','',$contract_no);
				$contract_no = preg_replace('/-/','',$contract_no);
				$fetchMenuLoan = $conoracle->prepare("SELECT ln.PRINCIPAL_BALANCE as BALANCE, lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.contract_status, 
														(SELECT COUNT(loancontract_no) FROM lncontmaster WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8) as C_CONTRACT
														FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
														WHERE loancontract_no = :contract_no");
				$fetchMenuLoan->execute([
					':member_no' => $member_no,
					':contract_no' => $contract_no
				]);
				$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
				$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
				if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
					$contract_no = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
					if(mb_stripos($contract_no,'.') === FALSE){
						$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
						if(mb_strlen($contract_no) == 10){
							$arrMenuLoan["CONTRACT_NO"] = $loan_format;
						}else if(mb_strlen($contract_no) == 11){
							$arrMenuLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
						}
					}else{
						$arrMenuLoan["CONTRACT_NO"] = $contract_no;
					}
					$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
					$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
					$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
				}else{
					$arrMenuLoan["CONTRACT_NO"] = 'close';
				}
			}else {
				$fetchMenuLoan = $conoracle->prepare("SELECT SUM(PRINCIPAL_BALANCE) as BALANCE,COUNT(loancontract_no) as C_CONTRACT FROM lncontmaster 
													WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8");
				$fetchMenuLoan->execute([
					':member_no' => $member_no
				]);
				$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
				$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
				$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
			}
			$arrMenuLoan["LAST_STATEMENT"] = TRUE;
			$arrayResult['MENU_LOAN'] = $arrMenuLoan;
		}
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
		
	}else{
		if(isset($dataComing["menu_parent"])){
			if($user_type == '5' || $user_type == '9'){
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = :menu_parent
												and (menu_channel = :channel OR 1=1)
												ORDER BY menu_order ASC");
			}else if($user_type == '1'){
				$fetch_menu = $conmysql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") and gm.menu_parent = :menu_parent
												and gm.menu_status IN('0','1') and (gm2.menu_status IN('0','1') OR gm.menu_parent = '0')
												and (gm.menu_channel = :channel OR 1=1) ORDER BY gm.menu_order ASC");
			}else{
				$fetch_menu = $conmysql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") and gm.menu_parent = :menu_parent 
												and gm.menu_status = '1' and (gm2.menu_status = '1' OR gm.menu_parent = '0')
												and (gm.menu_channel = :channel OR gm.menu_channel = 'both') ORDER BY gm.menu_order ASC");
			}
			$fetch_menu->execute([
				':menu_parent' => $dataComing["menu_parent"],
				':channel' => $dataComing["channel"]
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
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") 
												and (menu_channel = :channel OR 1=1)
												ORDER BY menu_order ASC");
			}else if($user_type == '1'){
				$fetch_menu = $conmysql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).")
												and gm.menu_status IN('0','1') and (gm2.menu_status IN('0','1') OR gm.menu_parent = '0')
												and (gm.menu_channel = :channel OR 1=1) ORDER BY gm.menu_order ASC");
			}else{
				$fetch_menu = $conmysql->prepare("SELECT gm.id_menu,gm.menu_name,gm.menu_name_en,gm.menu_icon_path,gm.menu_component,
												gm.menu_parent,gm.menu_status,gm.menu_version 
												FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu
												WHERE gm.menu_permission IN (".implode(',',$permission).") 
												and gm.menu_status = '1' and (gm2.menu_status = '1' OR gm.menu_parent = '0')
												and (gm.menu_channel = :channel OR gm.menu_channel = 'both') ORDER BY gm.menu_order ASC");
			}
			$fetch_menu->execute([
				':channel' => $dataComing["channel"]
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
						if($rowMenu["menu_parent"] == '0'){
							$arrayGroupMenu["ID_PARENT"] = $rowMenu["menu_parent"];
							$arrayGroupMenu["MENU"][] = $arrMenu;
						}else if($rowMenu["menu_parent"] == '24'){
							$arrayMenuSetting[] = $arrMenu;
						}else if($rowMenu["menu_parent"] == '18'){
							$arrayMenuTransaction["ID_PARENT"] = $rowMenu["menu_parent"];
							$getMenuParentStatus = $conmysql->prepare("SELECT menu_status FROM gcmenu WHERE id_menu = 18");
							$getMenuParentStatus->execute();
							$rowStatus = $getMenuParentStatus->fetch(PDO::FETCH_ASSOC);
							$arrayMenuTransaction["MENU_STATUS"] = $rowStatus["menu_status"];
							$arrayMenuTransaction["MENU"][] = $arrMenu;
						}
						if($rowMenu["menu_component"] == "DepositInfo"){
							if(isset($dataComing["home_deposit_account"])) {
								$account_no = preg_replace('/-/','',$dataComing["home_deposit_account"]);
								$fetchMenuDep = $conoracle->prepare("SELECT dp.prncbal as BALANCE, dp.deptaccount_no,dp.deptclose_status, dt.depttype_desc, (SELECT COUNT(deptaccount_no) 
																		FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status = 0) as C_ACCOUNT
																		FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
																		WHERE deptaccount_no = :account_no");
								$fetchMenuDep->execute([
									':member_no' => $member_no,
									':account_no' => $account_no
								]);
								$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
								$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
								if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
									$arrMenuDep["ACCOUNT_NO"] = $lib->formataccount($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
									$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
									$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
									$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
								}else{
									$arrMenuDep["ACCOUNT_NO"] = "close";
								}
							}else {
								$fetchMenuDep = $conoracle->prepare("SELECT SUM(prncbal) as BALANCE,COUNT(deptaccount_no) as C_ACCOUNT FROM dpdeptmaster 
																WHERE member_no = :member_no and deptclose_status = 0");
								$fetchMenuDep->execute([
									':member_no' => $member_no
								]);
								$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
								$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
								$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
							}
							$arrMenuDep["LAST_STATEMENT"] = TRUE;
						}else if($rowMenu["menu_component"] == "LoanInfo"){
							if(isset($dataComing["home_loan_account"])) {
								$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
								$contract_no = preg_replace('/./','',$contract_no);
								$contract_no = preg_replace('/-/','',$contract_no);
								$fetchMenuLoan = $conoracle->prepare("SELECT ln.PRINCIPAL_BALANCE as BALANCE, lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.contract_status, 
																		(SELECT COUNT(loancontract_no) FROM lncontmaster WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8) as C_CONTRACT
																		FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
																		WHERE loancontract_no = :contract_no");
								$fetchMenuLoan->execute([
									':member_no' => $member_no,
									':contract_no' => $contract_no
								]);
								$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
								$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
								if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
									$contract_no = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
									if(mb_stripos($contract_no,'.') === FALSE){
										$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
										if(mb_strlen($contract_no) == 10){
											$arrMenuLoan["CONTRACT_NO"] = $loan_format;
										}else if(mb_strlen($contract_no) == 11){
											$arrMenuLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
										}
									}else{
										$arrMenuLoan["CONTRACT_NO"] = $contract_no;
									}
									$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
									$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
									$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
								}else{
									$arrMenuLoan["CONTRACT_NO"] = 'close';
								}
							}else {
								$fetchMenuLoan = $conoracle->prepare("SELECT SUM(PRINCIPAL_BALANCE) as BALANCE,COUNT(loancontract_no) as C_CONTRACT FROM lncontmaster 
																	WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8");
								$fetchMenuLoan->execute([
									':member_no' => $member_no
								]);
								$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
								$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
								$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
							}
							$arrMenuLoan["LAST_STATEMENT"] = TRUE;
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
						if(isset($dataComing["home_deposit_account"])) {
							$account_no = preg_replace('/-/','',$dataComing["home_deposit_account"]);
							$fetchMenuDep = $conoracle->prepare("SELECT dp.prncbal as BALANCE, dp.deptaccount_no,dp.deptclose_status, dt.depttype_desc, (SELECT COUNT(deptaccount_no) 
																	FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status = 0) as C_ACCOUNT
																	FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
																	WHERE deptaccount_no = :account_no");
							$fetchMenuDep->execute([
								':member_no' => $member_no,
								':account_no' => $account_no
							]);
							$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
							$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
							if($rowMenuDep["DEPTCLOSE_STATUS"] != '1'){
								$arrMenuDep["ACCOUNT_NO"] = $lib->formataccount($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
								$arrMenuDep["ACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($rowMenuDep["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
								$arrMenuDep["ACCOUNT_DESC"] = $rowMenuDep["DEPTTYPE_DESC"];
								$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
							}else{
								$arrMenuDep["ACCOUNT_NO"] = "close";
							}
						}else {
							$fetchMenuDep = $conoracle->prepare("SELECT SUM(prncbal) as BALANCE,COUNT(deptaccount_no) as C_ACCOUNT FROM dpdeptmaster 
															WHERE member_no = :member_no and deptclose_status = 0");
							$fetchMenuDep->execute([
								':member_no' => $member_no
							]);
							$rowMenuDep = $fetchMenuDep->fetch(PDO::FETCH_ASSOC);
							$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
							$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
						}
						$arrMenuDep["LAST_STATEMENT"] = TRUE;
					}else if($rowMenu["menu_component"] == "LoanInfo"){
						if(isset($dataComing["home_loan_account"])) {
							$contract_no = preg_replace('/\//','',$dataComing["home_loan_account"]);
							$contract_no = preg_replace('/./','',$contract_no);
							$contract_no = preg_replace('/-/','',$contract_no);
							$fetchMenuLoan = $conoracle->prepare("SELECT ln.PRINCIPAL_BALANCE as BALANCE, lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.contract_status, 
																	(SELECT COUNT(loancontract_no) FROM lncontmaster WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8) as C_CONTRACT
																	FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
																	WHERE loancontract_no = :contract_no");
							$fetchMenuLoan->execute([
								':member_no' => $member_no,
								':contract_no' => $contract_no
							]);
							$rowMenuLoan = $fetchMenuLoan->fetch(PDO::FETCH_ASSOC);
							$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
							if($rowMenuLoan["CONTRACT_STATUS"] > '0' && $rowMenuLoan["CONTRACT_STATUS"] != "8"){
								$contract_no = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
								if(mb_stripos($contract_no,'.') === FALSE){
									$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
									if(mb_strlen($contract_no) == 10){
										$arrMenuLoan["CONTRACT_NO"] = $loan_format;
									}else if(mb_strlen($contract_no) == 11){
										$arrMenuLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
									}
								}else{
									$arrMenuLoan["CONTRACT_NO"] = $contract_no;
								}
								$arrMenuLoan["CONTRACT_NO"] = preg_replace('/\//','',$rowMenuLoan["LOANCONTRACT_NO"]);
								$arrMenuLoan["CONTRACT_DESC"] = $rowMenuLoan["LOAN_TYPE"];
								$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
							}else{
								$arrMenuLoan["CONTRACT_NO"] = 'close';
							}
						}else {
							$fetchMenuLoan = $conoracle->prepare("SELECT SUM(PRINCIPAL_BALANCE) as BALANCE,COUNT(loancontract_no) as C_CONTRACT FROM lncontmaster 
																WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8");
							$fetchMenuLoan->execute([
								':member_no' => $member_no
							]);
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
			$fetchMenuFav = $conmysql->prepare("SELECT fav_refno,name_fav,destination,flag_trans FROM gcfavoritelist WHERE member_no = :member_no");
			$fetchMenuFav->execute([':member_no' => $payload["member_no"]]);
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
				$checkElection = $conoracle->prepare("SELECT NVL(POST_NO,'-99') as POST_NO FROM MBMEMBELECTION WHERE ELECTION_YEAR = EXTRACT(YEAR FROM SYSDATE) + 543 AND MEMBER_NO = :member_no");
				$checkElection->execute([':member_no' => $payload["member_no"]]);
				$rowElec = $checkElection->fetch(PDO::FETCH_ASSOC);
				if($rowElec["POST_NO"] == '-99' || $payload["member_no"] == 'etnmode2'){
					$arrayResult['ONPERIOD_REGISTER_ELECTION'] = FALSE;
					$arrayResult['ONPERIOD_ELECTION'] = FALSE;
				}else{
					$arrayResult['ONREGISTERED_ELECTION'] = FALSE;
					$arrayResult['ONPERIOD_REGISTER_ELECTION'] = FALSE;
					if($rowElec["POST_NO"] == '3' || $payload["member_no"] == 'etnmode3'){
						$getElection = $conmysql->prepare("SELECT id_election FROM gcelection WHERE member_no = :member_no and year_election = YEAR(NOW()) + 543");
						$getElection->execute([':member_no' => $payload["member_no"]]);
						if($getElection->rowCount() > 0){
							$arrayResult['ONREGISTERED_ELECTION'] = FALSE;
							$arrayResult['ONPERIOD_ELECTION'] = FALSE;
						}else{
							if(preg_replace('/\./','',$dataComing["app_version"]) >= '1162' || $dataComing["channel"] == 'web'){
								$arrayResult['ONPERIOD_ELECTION'] = FALSE;
								$arrElectionData["WELCOME_TITLE"] = "การสรรหาคณะกรรมการดำเนินการ ประจำปี 2565 ทางอิเล็กทรอนิกส์";
								//$arrElectionData["WELCOME_SUBTITLE"] = "";
								//$arrElectionData["PASSCODE_TITLE"] = "";
								$arrElectionData["PASSCODE_SUBTITLE"] = "กรุณากรอกรหัสผ่าน (Key code) ที่ได้รับข้อความ (SMS) จาก Thaicoop ในวันแจ้งความประสงค์สรรหาส่งไปยังหมายเลขของท่าน หากไม่ทราบรหัสผ่าน(Key code) กรุณากดลืมรหัสผ่าน";
								$arrayResult["ELECTION_DATA"] = $arrElectionData;
							}else{
								$arrayResult['ONPERIOD_ELECTION'] = FALSE;	
							}
						}
					}else{
						$arrayResult['ONPERIOD_ELECTION'] = FALSE;
					}
					
					// registered
					$getRegisteredData = $conoracle->prepare("SELECT * FROM MBMEMBELECTION WHERE ELECTION_YEAR = EXTRACT(YEAR FROM SYSDATE) + 543 AND POST_NO IN('3','2','1') AND MEMBER_NO = :member_no");
					$getRegisteredData->execute([':member_no' => $member_no]);
					$rowRegisteredData = $getRegisteredData->fetch(PDO::FETCH_ASSOC);
					if(isset($rowRegisteredData["MEMBER_NO"])){
						$newArr = array();
						$newArr["POST_NO"] = $rowRegisteredData["POST_NO"];
						if($rowRegisteredData["POST_NO"] == "3"){
							$newArr["POST_REMARK"] = "สมาชิกได้แจ้งความประสงค์ลงคะแนนสรรหาทาง";
							$newArr["POST_REMARK_SUFFIX"] = "ไว้เเล้ว";
							$newArr["POST_REMARK_TYPE"] = " E-Vote ";
							$newArr["POST_REMARK_DESC"] = "โปรดลงคะแนนสรรหา วันที่ 13 ธ.ค. 2564 ตั้งแต่เวลา 00.01 น. ถึงวันที่ 16 ธ.ค. 2564 เวลา 15.30 น.";
						}else if($rowRegisteredData["POST_NO"] == "2"){
							$newArr["POST_REMARK"] = "สมาชิกรับบัตรลงคะแนนสรรหาทาง";
							$newArr["POST_REMARK_TYPE"] = "ไปรษณีย์";
							$newArr["POST_REMARK_DESC"] = "เมื่อลงคะแนนแล้ว โปรดส่งกลับสหกรณ์ก่อนวันที่ 16 ธ.ค. 2564 ภายในเวลา 15.30 น.";
						}else if($rowRegisteredData["POST_NO"] == "1"){
							$newArr["POST_REMARK"] = "สมาชิกรับบัตรลงคะแนนสรรหา ณ สหกรณ์";
							if($rowRegisteredData["SELECT_NO"] == "1"){
								$newArr["POST_REMARK_TYPE"] = "สำนักงานใหญ่ ศิริราช";
							}else if($rowRegisteredData["SELECT_NO"] == "2"){
								$newArr["POST_REMARK_TYPE"] = "สาขารามาธิบดี";
							}else if($rowRegisteredData["SELECT_NO"] == "3"){
								$newArr["POST_REMARK_TYPE"] = "สาขาเขตร้อน";
							}else if($rowRegisteredData["SELECT_NO"] == "4"){
								$newArr["POST_REMARK_TYPE"] = "สาขาศาลายา";
							}else if($rowRegisteredData["SELECT_NO"] == "5"){
								$newArr["POST_REMARK_TYPE"] = "สาขาจักรี";
							}
							
							if($rowRegisteredData["SELECT_NO"] == "5"){
								$newArr["POST_REMARK_DESC"] = "วันที่ 15 - 30 พ.ย. 2564 (เวลา 9.30 น. - 14.30 น.) โปรดหย่อนบัตรลงคะแนน วันที่ 7 - 16 ธ.ค. 2564 (เวลา 9.30 น. - 14.30 น.)";
							}else{
								$newArr["POST_REMARK_DESC"] = "วันที่ 15 - 30 พ.ย. 2564 (เวลา 8.30 น. - 15.00 น.) โปรดหย่อนบัตรลงคะแนน วันที่ 7 - 16 ธ.ค. 2564 (เวลา 8.30 น. - 15.30 น.)";
							}
						}
						$arrayResult['REGISTERED_DATA'] = $newArr;
					}
				}
				$fetchLimitTrans = $conmysql->prepare("SELECT limit_amount_transaction FROM gcmemberaccount WHERE member_no = :member_no");
				$fetchLimitTrans->execute([':member_no' => $member_no]);
				$rowLimitTrans = $fetchLimitTrans->fetch(PDO::FETCH_ASSOC);
				$arrayResult['LIMIT_AMOUNT_TRANSACTION'] = $rowLimitTrans["limit_amount_transaction"];
				$arrayResult['LIMIT_AMOUNT_TRANSACTION_COOP'] = $func->getConstant("limit_withdraw");
				if(preg_replace('/\./','',$dataComing["app_version"]) >= '1164' || $dataComing["channel"] == 'web'){
					$arrayResult["APP_CONFIG"]["LOGIN_REQ_CARD_PERSON"] = TRUE;
					$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_PHONE"] = TRUE;
					$arrayResult["APP_CONFIG"]["PRIVACY_POLICY_URL"] = "https://policy.thaicoop.co/privacy.html?coop=mhd";
				}
				$arrayResult['rowRegisteredData'] = isset($rowRegisteredData["MEMBER_NO"]);
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
		$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_name_en,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_parent IN ('-1','-2') and (menu_channel = :channel OR menu_channel = 'both')");
		$fetch_menu->execute([
			':channel' => $arrPayload["PAYLOAD"]["channel"]
		]);
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
			if(preg_replace('/\./','',$dataComing["app_version"]) >= '1164' || $dataComing["channel"] == 'web'){
				$arrayResult["APP_CONFIG"]["LOGIN_REQ_CARD_PERSON"] = TRUE;
				$arrayResult["APP_CONFIG"]["REGISTER_VERIFY_PHONE"] = TRUE;
				$arrayResult["APP_CONFIG"]["PRIVACY_POLICY_URL"] = "https://policy.thaicoop.co/privacy.html?coop=mhd";
			}
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