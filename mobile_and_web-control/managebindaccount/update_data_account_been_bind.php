<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no','id_token'],$payload) && $lib->checkCompleteArgument(['menu_component','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ManagementBankAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,gpl.type_palette,gpl.color_deg,gpl.color_main,
													gpl.color_secon,csb.bank_short_name,csb.bank_logo_path,csb.bank_format_account,csb.bank_format_account_hide
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
				if($rowAccountBind["type_palette"] == '2'){
					$arrAccount["BANNER_COLOR"] = "(".$rowAccountBind["color_deg"]."deg,".$rowAccountBind["color_main"].",".$rowAccountBind["color_secon"].")";
				}else{
					$arrAccount["BANNER_COLOR"] = $rowAccountBind["color_main"];
				}
				$arrAccount["ICON_BANK"] = $rowAccountBind["bank_logo_path"];
				$explodePathBankLOGO = explode('.',$rowAccountBind["bank_logo_path"]);
				$arrAccount["ICON_BANK_WEBP"] = $explodePathBankLOGO[0].'.webp';
				$arrAccount["BANK_NAME"] = $rowAccountBind["bank_short_name"];
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