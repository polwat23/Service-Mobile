<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$getListFavAcc = $conmysql->prepare("SELECT name_fav,destination,flag_trans,fav_refno,show_menu FROM gcfavoritelist WHERE member_no = :member_no and is_use = '1'");
		$getListFavAcc->execute([':member_no' => $payload["member_no"]]);
		$arrGrpFav = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		while($rowListFav = $getListFavAcc->fetch(PDO::FETCH_ASSOC)){
			$arrListFav = array();
			$arrListFav["NAME_FAV"] = $rowListFav["name_fav"];
			if($rowListFav["flag_trans"] == 'TRN'){
				$arrListFav["DESTINATION"] = $lib->formataccount($rowListFav["destination"],$formatDept);
				$arrListFav["DESTINATION_HIDDEN"] = $lib->formataccount($arrListFav["DESTINATION"],$formatDeptHidden);
			}else{
				$arrListFav["DESTINATION"] = $rowListFav["destination"];
			}
			$arrListFav["FLAG_TRANS"] = $rowListFav["flag_trans"];
			$arrListFav["SHOW_MENU"] = $rowListFav["show_menu"];
			$arrListFav["FAV_REFNO"] = $rowListFav["fav_refno"];
			$arrGrpFav[$rowListFav["flag_trans"]][] = $arrListFav;
		}
		$arrayResult['FAV_ACCOUNT'] = $arrGrpFav;
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