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
		$arrGroupAccAllow = array();
		$fetchAccountBeenAllow = $conmysql->prepare("SELECT deptaccount_no,is_use FROM gcuserallowacctransaction WHERE member_no = :member_no");
		$fetchAccountBeenAllow->execute([':member_no' => $member_no]);
		if($fetchAccountBeenAllow->rowCount() > 0){
			while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch()){
				$arrAccBeenAllow = array();
				$getDetailAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc,dpm.depttype_code,dpm.membcat_code
														FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
														and dpm.membcat_code = dpt.membcat_code
														WHERE dpm.deptaccount_no = :deptaccount_no");
				$getDetailAcc->execute([':deptaccount_no' => $rowAccBeenAllow["deptaccount_no"]]);
				$rowDetailAcc = $getDetailAcc->fetch();
				$getBannerColorCoop = $conmysql->prepare("SELECT gpc.color_deg,gpc.color_main,gpc.color_secon,gpc.type_palette,gpc.color_text
															FROM gcconstantaccount gca LEFT JOIN gcpalettecolor gpc ON gca.id_palette = gpc.id_palette and gpc.is_use = '1'
															WHERE gca.dept_type_code = :depttype_code and gca.member_cate_code = :membcat_code and gca.is_use = '1'");
				$getBannerColorCoop->execute([
					':depttype_code' => $rowDetailAcc["DEPTTYPE_CODE"],
					':membcat_code' => $rowDetailAcc["MEMBCAT_CODE"]
				]);
				$rowBanner = $getBannerColorCoop->fetch();
				if(isset($rowBanner["type_palette"])){
					if($rowBanner["type_palette"] == '2'){
						$arrAccBeenAllow["ACCOUNT_COOP_COLOR"] = $rowBanner["color_deg"]."|".$rowBanner["color_main"].",".$rowBanner["color_secon"];
					}else{
						$arrAccBeenAllow["ACCOUNT_COOP_COLOR"] = "90|".$rowBanner["color_main"].",".$rowBanner["color_main"];
					}
					$arrAccBeenAllow["ACCOUNT_COOP_TEXT_COLOR"] = $rowBanner["color_text"];
				}else{
					$arrAccBeenAllow["ACCOUNT_COOP_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
					$arrAccBeenAllow["ACCOUNT_COOP_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
				}				
				$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDetailAcc["DEPTACCOUNT_NAME"]);
				$arrAccBeenAllow["DEPT_TYPE"] = $rowDetailAcc["DEPTTYPE_DESC"];
				$arrAccBeenAllow["DEPTACCOUNT_NO"] = $rowAccBeenAllow["deptaccount_no"];
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBeenAllow["deptaccount_no"],$func->getConstant('dep_format'));
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBeenAllow["deptaccount_no"],$func->getConstant('hidden_dep'));
				$arrAccBeenAllow["STATUS_ALLOW"] = $rowAccBeenAllow["is_use"];
				$arrGroupAccAllow[] = $arrAccBeenAllow;
			}
			if(sizeof($arrGroupAccAllow) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
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
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>