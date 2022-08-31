<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','name_fav','fav_refno'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$updateFavAccount = $conmysql->prepare("UPDATE gcfavoritelist SET name_fav = :name_fav,show_menu = :show_menu,is_use = :is_use
											WHERE fav_refno = :fav_refno and member_no = :member_no");
		if($updateFavAccount->execute([
			':name_fav' => $dataComing["name_fav"],
			':show_menu' => $dataComing["show_menu"],
			':is_use' => ($dataComing["is_use"] ?? '1'),
			':fav_refno' => $dataComing["fav_refno"],
			':member_no' => $payload["member_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1029",
				":error_desc" => "แก้ไขรายการโปรดไม่ได้ไม่สามารถ UPDATE gcfavoritelist ได้"."\n"."Query => ".$updateFavAccount->queryString."\n".json_encode([
					':name_fav' => $dataComing["name_fav"],
					':fav_refno' => $dataComing["fav_refno"],
					':member_no' => $payload["member_no"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "แก้ไขรายการโปรดไม่ได้ไม่สามารถ UPDATE gcfavoritelist ได้"."\n"."Query => ".$updateFavAccount->queryString."\n".json_encode([
				':name_fav' => $dataComing["name_fav"],
				':fav_refno' => $dataComing["fav_refno"],
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1029";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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
