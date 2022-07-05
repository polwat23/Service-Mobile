<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$fetchAccountBeenBind = $conoracle->prepare("SELECT gba.deptaccount_no_bank,gpl.type_palette,gpl.color_deg,gpl.color_text,gpl.color_main,gba.id_bindaccount,gba.deptaccount_no_coop,gba.sigma_key,
													gpl.color_secon,csb.bank_short_name,csb.bank_short_ename,csb.bank_logo_path,csb.bank_format_account,csb.bank_format_account_hide,gba.bindaccount_status,
													gba.bank_account_name,gba.bank_account_name_en,gba.bank_code
													FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
													LEFT JOIN gcpalettecolor gpl ON csb.id_palette = gpl.id_palette and gpl.is_use = '1'
													WHERE gba.member_no = :member_no and gba.bindaccount_status NOT IN('8','-9','9')");
		$fetchAccountBeenBind->execute([
			':member_no' => $payload["member_no"]
		]);
		$arrBindAccount = array();
		while($rowAccountBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC)){
			$fetchAccountBeenAllow = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no and is_use <> '-9'");
			$fetchAccountBeenAllow->execute([':deptaccount_no' =>  $rowAccountBind["DEPTACCOUNT_NO_COOP"]]);
			$rowAccountBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC);
			if(isset($rowAccountBeenAllow["DEPTACCOUNT_NO"])){
				$getDetailAcc = $conoracle->prepare("SELECT deptaccount_name FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no and deptclose_status = 0");
				$getDetailAcc->execute([':deptaccount_no' => $rowAccountBind["DEPTACCOUNT_NO_COOP"]]);
				$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
				if(isset($rowDetailAcc["DEPTACCOUNT_NAME"])){
					$arrAccount = array();
					if($rowAccountBind["BANK_CODE"] == '025'){
						$arrAccount["DEPTACCOUNT_NO_BANK"] = $rowAccountBind["DEPTACCOUNT_NO_BANK"];
						$arrAccount["DEPTACCOUNT_NO_BANK_HIDE"] = $rowAccountBind["DEPTACCOUNT_NO_BANK"];
					}else{
						$arrAccount["DEPTACCOUNT_NO_BANK"] = $lib->formataccount($rowAccountBind["DEPTACCOUNT_NO_BANK"],$rowAccountBind["BANK_FORMAT_ACCOUNT"]);
						$arrAccount["DEPTACCOUNT_NO_BANK_HIDE"] = $lib->formataccount_hidden($rowAccountBind["DEPTACCOUNT_NO_BANK"],$rowAccountBind["BANK_FORMAT_ACCOUNT_HIDE"]);
					}
					if(isset($rowAccountBind["TYPE_PALETTE"])){
						if($rowAccountBind["TYPE_PALETTE"] == '2'){
							$arrAccount["BANNER_COLOR"] = $rowAccountBind["COLOR_DEG"]."|".$rowAccountBind["COLOR_MAIN"].",".$rowAccountBind["COLOR_SECON"];
						}else{
							$arrAccount["BANNER_COLOR"] = "90|".$rowAccountBind["COLOR_MAIN"].",".$rowAccountBind["COLOR_MAIN"];
						}
						$arrAccount["ACCOUNT_TEXT_COLOR"] = $rowAccountBind["COLOR_TEXT"];
					}else{
						$arrAccount["BANNER_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
						$arrAccount["ACCOUNT_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
					}
					$arrAccount["ICON_BANK"] = $config['URL_SERVICE'].$rowAccountBind["BANK_LOGO_PATH"];
					$explodePathBankLOGO = explode('.',$rowAccountBind["BANK_LOGO_PATH"]);
					$arrAccount["ICON_BANK_WEBP"] = $config['URL_SERVICE'].$explodePathBankLOGO[0].'.webp';
					$arrAccount["BANK_CODE"] = $rowAccountBind["BANK_CODE"];
					$arrAccount["BANK_NAME"] = $rowAccountBind["BANK_SHORT_NAME"];
					$arrAccount["BANK_SHORT_NAME"] = $rowAccountBind["BANK_SHORT_ENAME"];
					$arrAccount["ID_BINDACCOUNT"] = $rowAccountBind["ID_BINDACCOUNT"];
					$arrAccount["SIGMA_KEY"] = $rowAccountBind["SIGMA_KEY"];
					$arrAccount["ALLOW_DESC_COLOR"] = "#000000";
					$arrAccount["ALLOW_DESC"] = $configError["BIND_ACCOUNT_DESC"][0][$rowAccountBind["BINDACCOUNT_STATUS"]][0][$lang_locale] ?? null;
					$arrAccount["DEPTACCOUNT_NO_COOP"] = $lib->formataccount($rowAccountBind["DEPTACCOUNT_NO_COOP"],$func->getConstant('dep_format'));
					$arrAccount["DEPTACCOUNT_NO_COOP_HIDE"] = $lib->formataccount_hidden($rowAccountBind["DEPTACCOUNT_NO_COOP"],$func->getConstant('hidden_dep'));
					$arrAccount["BIND_STATUS"] = $rowAccountBind["BINDACCOUNT_STATUS"];
					$arrAccount["ACCOUNT_COOP_NAME"] = $lang_locale == 'th' ? $rowAccountBind["BANK_ACCOUNT_NAME"] : $rowAccountBind["BANK_ACCOUNT_NAME_EN"];
					$arrBindAccount[] = $arrAccount;
				}
			}
		}
		$arrayBankGrp = array();
		$getBankAllow = $conoracle->prepare("SELECT bank_code,bank_name,bank_short_name,bank_short_ename,bank_logo_path
											FROM csbankdisplay");
		$getBankAllow->execute();
		while($rowAllow = $getBankAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayBank = array();
			$arrayBank["IS_BIND"] = FALSE;
			$checkRegis = $conoracle->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank,bank_account_name,bank_account_name_en FROM gcbindaccount 
											WHERE bank_code = :bank_code and member_no = :member_no and bindaccount_status IN('1','7')");
			$checkRegis->execute([
				':bank_code' => $rowAllow["BANK_CODE"],
				':member_no' => $payload["member_no"]
			]);
			$rowRegis = $checkRegis->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRegis["DEPTACCOUNT_NO_COOP"])){			
				$arrayBank["IS_BIND"] = TRUE;
				$arrayBank["COOP_ACCOUNT_NO"] = $rowRegis["DEPTACCOUNT_NO_COOP"];
				$arrayBank["BANK_ACCOUNT_NO"] = $rowRegis["DEPTACCOUNT_NO_BANK"];
				if($lang_locale == 'th'){
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["BANK_ACCOUNT_NAME"];
				}else{
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["BANK_ACCOUNT_NAME_EN"];
				}
			}
			$arrayBank["BANK_CODE"] = $rowAllow["BANK_CODE"];
			$arrayBank["BANK_NAME"] = $rowAllow["BANK_NAME"];
			$arrayBank["BANK_SHORT_NAME"] = $rowAllow["BANK_SHORT_NAME"];
			$arrayBank["BANK_SHORT_ENAME"] = $rowAllow["BANK_SHORT_ENAME"];
			$arrayBank["BANK_LOGO_PATH"] = $config["URL_SERVICE"].$rowAllow["BANK_LOGO_PATH"];
			$arrPic = explode('.',$rowAllow["BANK_LOGO_PATH"]);
			$arrayBank["BANK_LOGO_PATH_WEBP"] = $config["URL_SERVICE"].$arrPic[0].'.webp';
			$arrayBankGrp[] = $arrayBank;
		}
		$arrayResult['BANK_LIST'] = $arrayBankGrp;
		$arrayResult['BIND_ACCOUNT'] = $arrBindAccount;
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