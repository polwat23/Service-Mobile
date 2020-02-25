<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','name_fav','show_menu','destination','flag_trans'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$fav_refno = substr(time(),0,3).(date("Y") + 543).substr($payload["member_no"],4).date("i").date("s").$lib->randomText("all",2)."FAV";
		$insertFavAccount = $conmysql->prepare("INSERT INTO gcfavoritelist(fav_refno,name_fav,flag_trans,destination,member_no,show_menu)
											VALUES(:fav_refno,:name_fav,:flag_trans,:destination,:member_no,:show_menu)");
		if($insertFavAccount->execute([
			':fav_refno' => $fav_refno,
			':name_fav' => $dataComing["name_fav"],
			':flag_trans' => $dataComing["flag_trans"],
			':destination' => $dataComing["destination"],
			':member_no' => $payload["member_no"],
			':show_menu' => $dataComing["show_menu"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':fav_refno' => $fav_refno,
				':name_fav' => $dataComing["name_fav"],
				':flag_trans' => $dataComing["flag_trans"],
				':destination' => $dataComing["destination"],
				':member_no' => $payload["member_no"],
				':show_menu' => $dataComing["show_menu"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertFavAccount;
			$arrError["ERROR_CODE"] = 'WS1029';
			$lib->addLogtoTxt($arrError,'fav_error');
			$arrayResult['RESPONSE_CODE'] = "WS1029";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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