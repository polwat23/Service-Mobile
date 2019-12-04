<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,gpl.type_palette,gpl.color_deg,gpl.color_text,gpl.color_main,gba.id_bindaccount,gba.deptaccount_no_coop,
													gpl.color_secon,csb.bank_short_name,csb.bank_logo_path,csb.bank_format_account,csb.bank_format_account_hide,gba.bindaccount_status
													FROM gcbindaccount gba LEFT JOIN gcconstantbankpalette gcpl ON gba.id_bankpalette = gcpl.id_bankpalette and gcpl.is_use = '1'
													LEFT JOIN gcpalettecolor gpl ON gcpl.id_palette = gpl.id_palette and gpl.is_use = '1'
													LEFT JOIN csbankdisplay csb ON gcpl.bank_code = csb.bank_code
													WHERE gba.member_no = :member_no and gba.bindaccount_status <> '-9'");
		$fetchAccountBeenBind->execute([
			':member_no' => $member_no
		]);
		if($fetchAccountBeenBind->rowCount() > 0){
			$arrBindAccount = array();
			while($rowAccountBind = $fetchAccountBeenBind->fetch()){
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
				$arrAccount["ICON_BANK"] = $rowAccountBind["bank_logo_path"];
				$explodePathBankLOGO = explode('.',$rowAccountBind["bank_logo_path"]);
				$arrAccount["ICON_BANK_WEBP"] = $explodePathBankLOGO[0].'.webp';
				$arrAccount["BANK_NAME"] = $rowAccountBind["bank_short_name"];
				$arrAccount["ID_BINDACCOUNT"] = $rowAccountBind["id_bindaccount"];
				$arrAccount["DEPTACCOUNT_NO_COOP"] = $lib->formataccount($rowAccountBind["deptaccount_no_coop"],$func->getConstant('dep_format'));
				$arrAccount["DEPTACCOUNT_NO_COOP_HIDE"] = $lib->formataccount_hidden($rowAccountBind["deptaccount_no_coop"],$func->getConstant('hidden_dep'));
				$arrAccount["BIND_STATUS"] = $rowAccountBind["bindaccount_status"];
				$fetchAccountCoop = $conoracle->prepare("SELECT deptaccount_name,depttype_code,membcat_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$fetchAccountCoop->execute([
					':deptaccount_no' => $rowAccountBind["deptaccount_no_coop"]
				]);
				$rowAccountCoop = $fetchAccountCoop->fetch();
				$getBannerColorCoop = $conmysql->prepare("SELECT gpc.color_deg,gpc.color_main,gpc.color_secon,gpc.type_palette,gpc.color_text
															FROM gcconstantaccountdept gca LEFT JOIN gcpalettecolor gpc ON gca.id_palette = gpc.id_palette and gpc.is_use = '1'
															WHERE gca.dept_type_code = :depttype_code and gca.member_cate_code = :membcat_code and gca.is_use = '1'");
				$getBannerColorCoop->execute([
					':depttype_code' => $rowAccountCoop["DEPTTYPE_CODE"],
					':membcat_code' => $rowAccountCoop["MEMBCAT_CODE"]
				]);
				$rowBanner = $getBannerColorCoop->fetch();
				if(isset($rowBanner["type_palette"])){
					if($rowBanner["type_palette"] == '2'){
						$arrAccount["ACCOUNT_COOP_COLOR"] = $rowBanner["color_deg"]."|".$rowBanner["color_main"].",".$rowBanner["color_secon"];
					}else{
						$arrAccount["ACCOUNT_COOP_COLOR"] = "90|".$rowBanner["color_main"].",".$rowBanner["color_main"];
					}
					$arrAccount["ACCOUNT_COOP_TEXT_COLOR"] = $rowBanner["color_text"];
				}else{
					$arrAccount["ACCOUNT_COOP_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
					$arrAccount["ACCOUNT_COOP_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
				}
				$arrAccount["ACCOUNT_COOP_NAME"] = preg_replace('/\"/','',$rowAccountCoop["DEPTACCOUNT_NAME"]);
				$arrBindAccount[] = $arrAccount;
			}
			if(sizeof($arrBindAccount) > 0 || isset($new_token)){
				$arrayResult['BIND_ACCOUNT'] = $arrBindAccount;
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