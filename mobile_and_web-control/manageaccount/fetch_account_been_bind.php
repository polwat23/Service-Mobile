<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,gpl.type_palette,gpl.color_deg,gpl.color_text,gpl.color_main,gba.id_bindaccount,gba.deptaccount_no_coop,gba.sigma_key,
													gpl.color_secon,csb.bank_short_name,csb.bank_logo_path,csb.bank_format_account,csb.bank_format_account_hide,gba.bindaccount_status,
													gba.bank_account_name,gba.bank_account_name_en
													FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
													LEFT JOIN gcpalettecolor gpl ON csb.id_palette = gpl.id_palette and gpl.is_use = '1'
													WHERE gba.member_no = :member_no and gba.bindaccount_status NOT IN('8','-9')");
		$fetchAccountBeenBind->execute([
			':member_no' => $payload["member_no"]
		]);
		if($fetchAccountBeenBind->rowCount() > 0){
			$arrBindAccount = array();
			while($rowAccountBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC)){
				$fetchAccountBeenAllow = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no and is_use <> '-9'");
				$fetchAccountBeenAllow->execute([':deptaccount_no' =>  $rowAccountBind["deptaccount_no_coop"]]);
				if($fetchAccountBeenAllow->rowCount() > 0){
					$getDetailAcc = $conoracle->prepare("SELECT deptaccount_name FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no and deptclose_status = 0");
					$getDetailAcc->execute([':deptaccount_no' => $rowAccountBind["deptaccount_no_coop"]]);
					$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
					if(isset($rowDetailAcc["DEPTACCOUNT_NAME"])){
						$arrAccount = array();
						$arrAccount["DEPTACCOUNT_NO_BANK"] = $lib->formataccount($rowAccountBind["deptaccount_no_bank"],$rowAccountBind["bank_format_account"]);
						$arrAccount["DEPTACCOUNT_NO_BANK_HIDE"] = $lib->formataccount_hidden($rowAccountBind["deptaccount_no_bank"],$rowAccountBind["bank_format_account_hide"]);
						if(isset($rowAccountBind["type_palette"])){
							if($rowAccountBind["type_palette"] == '2'){
								$arrAccount["BANNER_COLOR"] = $rowAccountBind["color_deg"]."|".$rowAccountBind["color_main"].",".$rowAccountBind["color_secon"];
							}else{
								$arrAccount["BANNER_COLOR"] = "90|".$rowAccountBind["color_main"].",".$rowAccountBind["color_main"];
							}
							$arrAccount["BANNER_TEXT_COLOR"] = $rowAccountBind["color_text"];
						}else{
							$arrAccount["BANNER_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
							$arrAccount["BANNER_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
						}
						$arrAccount["ICON_BANK"] = $config['URL_SERVICE'].$rowAccountBind["bank_logo_path"];
						$explodePathBankLOGO = explode('.',$rowAccountBind["bank_logo_path"]);
						$arrAccount["ICON_BANK_WEBP"] = $config['URL_SERVICE'].$explodePathBankLOGO[0].'.webp';
						$arrAccount["BANK_NAME"] = $rowAccountBind["bank_short_name"];
						$arrAccount["ID_BINDACCOUNT"] = $rowAccountBind["id_bindaccount"];
						$arrAccount["SIGMA_KEY"] = $rowAccountBind["sigma_key"];
						$arrAccount["DEPTACCOUNT_NO_COOP"] = $lib->formataccount($rowAccountBind["deptaccount_no_coop"],$func->getConstant('dep_format'));
						$arrAccount["DEPTACCOUNT_NO_COOP_HIDE"] = $lib->formataccount_hidden($rowAccountBind["deptaccount_no_coop"],$func->getConstant('hidden_dep'));
						$arrAccount["BIND_STATUS"] = $rowAccountBind["bindaccount_status"];
						$arrAccount["ACCOUNT_COOP_NAME"] = $lang_locale == 'th' ? $rowAccountBind["bank_account_name"] : $rowAccountBind["bank_account_name_en"];
						$arrBindAccount[] = $arrAccount;
					}
				}
			}
			$arrayResult['BIND_ACCOUNT'] = $arrBindAccount;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
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